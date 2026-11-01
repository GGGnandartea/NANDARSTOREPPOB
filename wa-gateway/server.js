process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';

const express = require('express');
const cors = require('cors');
const QRCode = require('qrcode');
const fs = require('fs');
const path = require('path');
const pino = require('pino');
const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion
} = require('@whiskeysockets/baileys');

const app = express();
app.use(cors());
app.use(express.json());

const PORT = process.env.PORT || 5001;
const GATEWAY_VERSION = '2.3.0';
const SESSION_DIR = path.join(__dirname, 'session');
const DETECTED_CODES_FILE = path.join(__dirname, 'detected_codes.json');

// Simpan kode verifikasi yang baru masuk di memori & file disk (selama 60 menit)
// agar Laravel bisa melakukan pull check via GET /api/check-code/:code maupun baca file langsung
const recentDetectedCodes = new Map();

function loadDetectedCodesFromDisk() {
    try {
        if (fs.existsSync(DETECTED_CODES_FILE)) {
            const raw = fs.readFileSync(DETECTED_CODES_FILE, 'utf8');
            const parsed = JSON.parse(raw);
            const cutoff = Date.now() - (60 * 60 * 1000);
            if (parsed && typeof parsed === 'object') {
                for (const [k, v] of Object.entries(parsed)) {
                    if (v && v.timestamp && v.timestamp >= cutoff) {
                        recentDetectedCodes.set(k.toUpperCase(), v);
                    }
                }
            }
        }
    } catch (e) {}
}

function saveDetectedCodesToDisk() {
    try {
        const cutoff = Date.now() - (60 * 60 * 1000);
        const obj = {};
        for (const [k, v] of recentDetectedCodes.entries()) {
            if (v.timestamp >= cutoff) {
                obj[k] = v;
            } else {
                recentDetectedCodes.delete(k);
            }
        }
        fs.writeFileSync(DETECTED_CODES_FILE, JSON.stringify(obj, null, 2), { mode: 0o666 });
        try { fs.chmodSync(DETECTED_CODES_FILE, 0o666); } catch (e) {}
    } catch (e) {
        console.warn('[WA-BOT] Gagal menyimpan detected_codes.json:', e.message);
    }
}

loadDetectedCodesFromDisk();

function rememberDetectedCode(code, payload = {}) {
    if (!code) return;
    const upper = String(code).trim().toUpperCase();
    const record = {
        code: upper,
        sender: payload.sender || '',
        raw_sender: payload.raw_sender || '',
        sender_mismatch: Boolean(payload.sender_mismatch),
        claimed_phone: payload.claimed_phone || '',
        replied_by_bot: Boolean(payload.replied_by_bot),
        message: payload.message || '',
        timestamp: Date.now()
    };
    recentDetectedCodes.set(upper, record);

    // Simpan juga di bawah alias PK-XXXXX dan VERIF-XXXXX jika memiliki digit yang sama
    const digits = upper.replace(/[^0-9]/g, '');
    if (digits.length >= 4 && (upper.startsWith('PK-') || upper.startsWith('VERIF-') || upper.startsWith('NS-'))) {
        recentDetectedCodes.set(`PK-${digits}`, { ...record, code: `PK-${digits}` });
        recentDetectedCodes.set(`VERIF-${digits}`, { ...record, code: `VERIF-${digits}` });
    }

    saveDetectedCodesToDisk();
}

function normalizeIndoPhone(raw) {
    if (!raw) return '';
    const str = String(raw).trim();
    if (str.includes('@lid')) return '';
    let clean = str.split('@')[0].split(':')[0].replace(/[^0-9]/g, '');
    if (!clean || /^5390\d+/.test(clean)) return '';
    if (clean.startsWith('0628')) {
        clean = '08' + clean.slice(4);
    } else if (clean.startsWith('628')) {
        clean = '08' + clean.slice(3);
    } else if (clean.startsWith('8') && clean.length >= 9 && clean.length <= 13) {
        clean = '08' + clean.slice(1);
    }
    if (!clean.startsWith('08') || clean.length < 9 || clean.length > 14) {
        return '';
    }
    return clean;
}

function resolvePhoneFromSessionLidFiles(lidUser) {
    if (!lidUser) return '';
    try {
        // 1. Cek file reverse mapping langsung: lid-mapping-<lidUser>_reverse.json
        const reverseFile = path.join(SESSION_DIR, `lid-mapping-${lidUser}_reverse.json`);
        if (fs.existsSync(reverseFile)) {
            const raw = JSON.parse(fs.readFileSync(reverseFile, 'utf8'));
            const phone = normalizeIndoPhone(raw);
            if (phone) return phone;
        }

        // 2. Scan file forward mapping: lid-mapping-<phone>.json
        if (fs.existsSync(SESSION_DIR)) {
            const files = fs.readdirSync(SESSION_DIR);
            for (const f of files) {
                const m = f.match(/^lid-mapping-(\d+)\.json$/);
                if (m) {
                    try {
                        const val = JSON.parse(fs.readFileSync(path.join(SESSION_DIR, f), 'utf8'));
                        const mappedLid = String(val || '').split('@')[0].split(':')[0].replace(/[^0-9]/g, '');
                        if (mappedLid && mappedLid === lidUser) {
                            const phone = normalizeIndoPhone(m[1]);
                            if (phone) return phone;
                        }
                    } catch (e) {}
                }
            }
        }
    } catch (e) {}
    return '';
}

async function resolveSenderDetails(sockInstance, msg, senderJid, text) {
    const isLid = String(senderJid || '').includes('@lid');
    const claimedMatch = String(text || '').match(/\*?Nomor (?:Profil|WA|Terdaftar)\*?:\*?\s*([0-9+]+)/i);
    const claimedProfilePhone = claimedMatch ? normalizeIndoPhone(claimedMatch[1]) : '';

    let senderPhone = '';
    let senderMismatch = false;

    // 1. Cek field senderPn / participantPn / remoteJidAlt dari Baileys v7
    const pnCandidates = [
        msg.key?.senderPn,
        msg.key?.participantPn,
        msg.key?.remoteJidAlt,
        !isLid ? senderJid : ''
    ];
    for (const cand of pnCandidates) {
        if (cand && !String(cand).includes('@lid')) {
            const norm = normalizeIndoPhone(cand);
            if (norm) {
                senderPhone = norm;
                break;
            }
        }
    }

    // 2. Jika senderJid adalah @lid dan nomor belum ditemukan, cek lidMapping Baileys & file session
    if (!senderPhone && isLid) {
        const lidUser = String(senderJid).split('@')[0].split(':')[0].replace(/[^0-9]/g, '');

        try {
            if (sockInstance?.signalRepository?.lidMapping?.getPNForLID) {
                const mappedPn = await sockInstance.signalRepository.lidMapping.getPNForLID(senderJid);
                if (mappedPn) {
                    senderPhone = normalizeIndoPhone(mappedPn);
                }
            }
        } catch (e) {}

        if (!senderPhone && lidUser) {
            senderPhone = resolvePhoneFromSessionLidFiles(lidUser);
        }

        // 3. Jika masih belum terpetakan dan pesan mencantumkan Nomor Profil, cek apakah LID Nomor Profil cocok
        if (!senderPhone && claimedProfilePhone && lidUser && sockInstance?.onWhatsApp) {
            try {
                // Cek dulu dari file lid-mapping-<claimedPhone>.json di disk jika ada
                const clean62 = '62' + claimedProfilePhone.slice(1);
                const forwardFile = path.join(SESSION_DIR, `lid-mapping-${clean62}.json`);
                if (fs.existsSync(forwardFile)) {
                    const expectedLid = String(JSON.parse(fs.readFileSync(forwardFile, 'utf8')) || '')
                        .split('@')[0].split(':')[0].replace(/[^0-9]/g, '');
                    if (expectedLid) {
                        if (expectedLid === lidUser) {
                            senderPhone = claimedProfilePhone;
                        } else {
                            senderMismatch = true;
                        }
                    }
                }

                if (!senderPhone && !senderMismatch) {
                    const waCheck = await sockInstance.onWhatsApp(`${clean62}@s.whatsapp.net`);
                    const info = Array.isArray(waCheck) ? waCheck[0] : null;
                    if (info?.lid) {
                        const expectedLid = String(info.lid).split('@')[0].split(':')[0].replace(/[^0-9]/g, '');
                        if (expectedLid) {
                            if (expectedLid === lidUser) {
                                senderPhone = claimedProfilePhone;
                            } else {
                                senderMismatch = true;
                            }
                        }
                    }
                }
            } catch (e) {}
        }
    }

    if (senderPhone && claimedProfilePhone && senderPhone !== claimedProfilePhone) {
        senderMismatch = true;
    }

    return {
        senderPhone,
        claimedProfilePhone,
        senderMismatch,
        isLid
    };
}

function getCandidateWebhookTargets() {
    const targets = [];

    // 1. Prioritaskan koneksi lokal loopback (127.0.0.1) dengan header Host agar TIDAK terblokir Cloudflare WAF
    const domains = ['premiumkita.com', 'v2.premiumkita.com'];
    try {
        const envPath = path.join(__dirname, '..', '.env');
        if (fs.existsSync(envPath)) {
            const envContent = fs.readFileSync(envPath, 'utf8');
            const match = envContent.match(/^APP_URL=(.+)$/m);
            if (match && match[1]) {
                const appUrl = match[1].trim().replace(/^["']|["']$/g, '').replace(/\/+$/, '');
                try {
                    const parsedUrl = new URL(appUrl);
                    if (parsedUrl.hostname && parsedUrl.hostname !== 'localhost' && parsedUrl.hostname !== '127.0.0.1') {
                        domains.unshift(parsedUrl.hostname);
                    }
                } catch (e) {}
            }
        }
    } catch (e) {}

    const uniqueDomains = [...new Set(domains)];
    for (const dom of uniqueDomains) {
        targets.push({ url: 'http://127.0.0.1/api/webhook/wa-incoming', host: dom });
        targets.push({ url: 'https://127.0.0.1/api/webhook/wa-incoming', host: dom });
    }

    targets.push({ url: 'http://127.0.0.1:8000/api/webhook/wa-incoming', host: null });
    for (const dom of uniqueDomains) {
        targets.push({ url: `https://${dom}/api/webhook/wa-incoming`, host: null });
    }

    return targets;
}

let sock = null;
let latestQrDataUrl = null;
let connectionStatus = 'initializing';
let botPhoneNumber = null;

function formatToJid(number) {
    if (!number) return null;
    const str = String(number).trim();
    if (str.includes('@lid') || str.includes('@s.whatsapp.net')) {
        return str;
    }
    let clean = str.replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
        clean = '62' + clean.slice(1);
    } else if (clean.startsWith('8')) {
        clean = '62' + clean;
    }
    return clean + '@s.whatsapp.net';
}

function extractMessageText(m) {
    if (!m) return '';
    if (typeof m.conversation === 'string' && m.conversation) return m.conversation;
    if (m.extendedTextMessage?.text) return m.extendedTextMessage.text;
    if (m.imageMessage?.caption) return m.imageMessage.caption;
    if (m.videoMessage?.caption) return m.videoMessage.caption;
    if (m.documentMessage?.caption) return m.documentMessage.caption;
    if (m.documentWithCaptionMessage?.message) return extractMessageText(m.documentWithCaptionMessage.message);
    if (m.ephemeralMessage?.message) return extractMessageText(m.ephemeralMessage.message);
    if (m.viewOnceMessage?.message) return extractMessageText(m.viewOnceMessage.message);
    if (m.viewOnceMessageV2?.message) return extractMessageText(m.viewOnceMessageV2.message);
    if (m.viewOnceMessageV2Extension?.message) return extractMessageText(m.viewOnceMessageV2Extension.message);
    if (m.deviceSentMessage?.message) return extractMessageText(m.deviceSentMessage.message);
    if (m.editedMessage?.message?.protocolMessage?.editedMessage) {
        return extractMessageText(m.editedMessage.message.protocolMessage.editedMessage);
    }
    if (m.editedMessage?.message) return extractMessageText(m.editedMessage.message);
    if (m.buttonsResponseMessage?.selectedDisplayText) return m.buttonsResponseMessage.selectedDisplayText;
    if (m.templateButtonReplyMessage?.selectedDisplayText) return m.templateButtonReplyMessage.selectedDisplayText;
    try {
        const rawJson = JSON.stringify(m);
        const match = rawJson.match(/(PK-\d{4,6}|NS-\d{4,6}|VERIF-\d{4,6}|OTP-[A-Z0-9]+|RESET-[A-Z0-9]+)/i);
        if (match) return rawJson;
    } catch (e) {}
    return '';
}

async function postToLaravelWebhook(payload) {
    const targets = getCandidateWebhookTargets();
    let lastError = null;
    for (const target of targets) {
        try {
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'User-Agent': `PremiumKita-WA-Gateway/${GATEWAY_VERSION}`
            };
            if (target.host) {
                headers['Host'] = target.host;
                headers['X-Forwarded-Host'] = target.host;
                headers['X-Forwarded-Proto'] = 'https';
            }

            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), 3500);

            const response = await fetch(target.url, {
                method: 'POST',
                headers,
                body: JSON.stringify(payload),
                signal: controller.signal
            });
            clearTimeout(timer);

            if (response.ok) {
                const textBody = await response.text();
                if (textBody.trim() === 'HANDLED_BY_LARAVEL') {
                    return { url: target.url, resData: { status: 'handled_by_laravel' } };
                }
                const resData = JSON.parse(textBody);
                if (resData && resData.status && resData.status !== 'ignored') {
                    return { url: `${target.url}${target.host ? ` (${target.host})` : ''}`, resData };
                }
                if (!lastError) {
                    lastError = { url: target.url, resData };
                }
            }
        } catch (err) {
            // Lanjut ke kandidat URL berikutnya
        }
    }
    return lastError;
}

async function startWhatsAppBot() {
    console.log('[WA-BOT] Memulai koneksi WhatsApp...');
    connectionStatus = 'connecting';

    if (!fs.existsSync(SESSION_DIR)) {
        fs.mkdirSync(SESSION_DIR, { recursive: true });
    }

    const { state, saveCreds } = await useMultiFileAuthState(SESSION_DIR);
    const { version, isLatest } = await fetchLatestBaileysVersion();
    console.log(`[WA-BOT] Menggunakan Baileys versi: ${version.join('.')} (Latest: ${isLatest})`);

    sock = makeWASocket({
        version,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false,
        auth: state,
        browser: ['PremiumKita WA Gateway', 'Desktop', GATEWAY_VERSION],
        syncFullHistory: false
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            console.log('[WA-BOT] 📱 QR Code baru telah di-generate, siap di-scan dari HP!');
            connectionStatus = 'qr_ready';
            try {
                latestQrDataUrl = await QRCode.toDataURL(qr, {
                    width: 300,
                    margin: 2,
                    color: { dark: '#000000', light: '#ffffff' }
                });
            } catch (err) {
                console.error('[WA-BOT] Gagal mengonversi QR ke DataURL:', err);
            }
        }

        if (connection === 'connecting') {
            connectionStatus = 'connecting';
            console.log('[WA-BOT] ⏳ Menghubungkan ke WhatsApp Server...');
        } else if (connection === 'open') {
            connectionStatus = 'connected';
            latestQrDataUrl = null;
            const userJid = sock.user?.id || '';
            botPhoneNumber = userJid.split(':')[0] || userJid.split('@')[0];
            console.log(`[WA-BOT] 🟢 KONEKSI BERHASIL TERSAMBUNG! Nomor Bot: ${botPhoneNumber}`);
        } else if (connection === 'close') {
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

            console.log(`[WA-BOT] 🔴 Koneksi terputus. Status Code: ${statusCode}, Reconnect: ${shouldReconnect}`);
            connectionStatus = 'disconnected';
            latestQrDataUrl = null;

            if (statusCode === DisconnectReason.loggedOut) {
                console.log('[WA-BOT] Akun logout. Menghapus sesi lama...');
                fs.rmSync(SESSION_DIR, { recursive: true, force: true });
                setTimeout(() => startWhatsAppBot(), 2000);
            } else {
                console.log('[WA-BOT] Mencoba menyambung ulang dalam 5 detik...');
                setTimeout(() => startWhatsAppBot(), 5000);
            }
        }
    });

    const processedMsgIds = new Set();

    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type && type !== 'notify' && type !== 'append') return;

        for (const msg of messages) {
            try {
                if (!msg || !msg.message) continue;

                const remoteJid = msg.key?.remoteJid || '';
                if (remoteJid.includes('@broadcast') || remoteJid.includes('@g.us')) continue;

                const text = extractMessageText(msg.message);
                if (!text) continue;

                if (msg.key?.fromMe) {
                    const isUserVerificationRequest = /ingin verifikasi nomor WhatsApp|Permintaan Kode:/i.test(text) && !/VERIFIKASI NOMOR BERHASIL|VERIFIKASI NOMOR GAGAL|VERIFIKASI GAGAL|KODE OTP RESET PASSWORD/i.test(text);
                    if (!isUserVerificationRequest) continue;
                }

                const msgId = msg.key?.id;
                if (msgId) {
                    if (processedMsgIds.has(msgId)) continue;
                    processedMsgIds.add(msgId);
                    if (processedMsgIds.size > 2000) {
                        const first = processedMsgIds.values().next().value;
                        processedMsgIds.delete(first);
                    }
                }

                const senderJid = remoteJid;
                const { senderPhone, claimedProfilePhone, senderMismatch } = await resolveSenderDetails(sock, msg, senderJid, text);

                console.log(`[WA-BOT] 📥 Pesan masuk dari JID=${senderJid} | Phone=${senderPhone || '-'} | Claimed=${claimedProfilePhone || '-'} | Mismatch=${senderMismatch}: "${text.trim().slice(0, 120)}"`);

                const match = text.match(/(PK-\d{4,6}|NS-\d{4,6}|VERIF-\d{4,6}|OTP-[A-Z0-9]+|RESET-[A-Z0-9]+|PKR-[A-Z0-9]+)/i);
                if (match) {
                    const code = match[1].toUpperCase();
                    console.log(`[WA-BOT] 🔍 Terdeteksi kode: ${code} dari ${senderPhone || senderJid}`);

                    rememberDetectedCode(code, {
                        sender: senderPhone,
                        raw_sender: senderJid,
                        sender_mismatch: senderMismatch,
                        claimed_phone: claimedProfilePhone,
                        replied_by_bot: false,
                        message: text
                    });

                    const sendSingleReply = async (replyMsg) => {
                        let delivered = false;
                        if (senderJid) {
                            try {
                                await sock.sendMessage(senderJid, { text: replyMsg });
                                console.log(`[WA-BOT] 📤 Terkirim (1x) ke sender JID: ${senderJid}`);
                                delivered = true;
                            } catch (err) {
                                console.warn(`[WA-BOT] Gagal kirim ke sender JID ${senderJid}:`, err.message);
                            }
                        }

                        if (!delivered) {
                            const fallbackPhone = senderPhone || claimedProfilePhone;
                            if (fallbackPhone) {
                                const phoneJid = formatToJid(fallbackPhone);
                                if (phoneJid && phoneJid !== senderJid) {
                                    try {
                                        await sock.sendMessage(phoneJid, { text: replyMsg });
                                        console.log(`[WA-BOT] 📤 Terkirim (1x fallback) ke nomor WhatsApp: ${phoneJid}`);
                                        delivered = true;
                                    } catch (err) {
                                        console.warn(`[WA-BOT] Gagal kirim fallback ke ${phoneJid}:`, err.message);
                                    }
                                }
                            }
                        }

                        if (delivered) {
                            rememberDetectedCode(code, {
                                sender: senderPhone,
                                raw_sender: senderJid,
                                sender_mismatch: senderMismatch,
                                claimed_phone: claimedProfilePhone,
                                replied_by_bot: true,
                                message: text
                            });
                        }

                        return delivered;
                    };

                    try {
                        const webhookResult = await postToLaravelWebhook({
                            message: text,
                            code: code,
                            sender: senderPhone,
                            raw_sender: senderJid,
                            sender_mismatch: senderMismatch
                        });

                        const resData = webhookResult?.resData || null;
                        console.log(`[WA-BOT] Respon Webhook Laravel (${webhookResult?.url || 'offline-fallback'}):`, resData);

                        if (resData && resData.status === 'handled_by_laravel') {
                            rememberDetectedCode(code, {
                                sender: senderPhone,
                                raw_sender: senderJid,
                                sender_mismatch: senderMismatch,
                                claimed_phone: claimedProfilePhone,
                                replied_by_bot: true,
                                message: text
                            });
                            console.log(`[WA-BOT] ✅ Balasan untuk kode ${code} telah dikirim oleh Laravel.`);
                        } else if (resData && resData.reply_message) {
                            await sendSingleReply(resData.reply_message);
                        } else if (resData && resData.status === 'send_otp') {
                            const userName = resData.user_name || 'Pelanggan';
                            const otpCode = resData.otp_code;
                            const replyMessage = `🔐 *KODE OTP RESET PASSWORD PREMIUMKITA*\n\n` +
                                                 `Halo *${userName}*!\n` +
                                                 `Kode OTP untuk mereset kata sandi akun Anda adalah:\n\n` +
                                                 `*KODE:* ${otpCode}\n\n` +
                                                 `⏱️ _Berlaku 15 menit & otomatis hangus jika sudah 1x digunakan._\n` +
                                                 `🔒 _Jangan berikan kode ini kepada siapapun demi keamanan._`;
                            await sendSingleReply(replyMessage);
                        } else if (resData && resData.status === 'verified') {
                            const userName = resData.user_name || 'Kak';
                            const confirmedPhone = resData.whatsapp || claimedProfilePhone || senderPhone || 'Akun Anda';
                            const replyMessage = `✅ *VERIFIKASI NOMOR BERHASIL*\n\n` +
                                                 `Halo *${userName}*!\n` +
                                                 `Nomor WhatsApp Anda (*${confirmedPhone}*) telah berhasil divalidasi dan terverifikasi di *PremiumKita*.\n\n` +
                                                 `🎉 *Manfaat Akun Terverifikasi:*\n` +
                                                 `• Notifikasi pesanan instan ke WhatsApp\n` +
                                                 `• Detail akun premium (Email & Password) otomatis dikirim ke chat ini\n` +
                                                 `• Akses promo khusus reseller\n\n` +
                                                 `Terima kasih telah berbelanja di *PremiumKita*!`;
                            await sendSingleReply(replyMessage);
                        } else if (!resData && (code.startsWith('PK-') || code.startsWith('VERIF-') || code.startsWith('NS-'))) {
                            // Fallback langsung jika HTTP webhook lokal belum merespons: tetap kirim balasan instan ke WA
                            const nameMatch = text.match(/\*?Nama Akun\*?:\*?\s*([^\r\n*]+)/i);
                            const extractedName = nameMatch ? nameMatch[1].trim() : 'Pelanggan';
                            if (senderMismatch) {
                                const detectedDisp = senderPhone || 'Nomor Berbeda';
                                const expectedDisp = claimedProfilePhone || 'Nomor Profil';
                                const failedReplyMsg = `❌ *VERIFIKASI NOMOR GAGAL*\n\n` +
                                    `Halo *${extractedName}*!\n` +
                                    `*VERIFIKASI GAGAL, SILAHKAN KIRIM PESAN DARI NOMOR WHATSAPP YANG DIMASUKAN DI WEBSITE*\n\n` +
                                    `⚠️ *Detail Pengecekan Sistem:*\n` +
                                    `• Nomor Terdaftar di Website: *${expectedDisp}*\n` +
                                    `• Nomor Pengirim Saat Ini: *${detectedDisp}*\n\n` +
                                    `📌 *Cara Mengatasi:*\n` +
                                    `• Silakan kirim pesan verifikasi menggunakan WhatsApp dengan nomor *${expectedDisp}*\n` +
                                    `• Atau ganti nomor WhatsApp di profil website *PremiumKita* menjadi *${detectedDisp}*\n\n` +
                                    `Terima kasih telah berbelanja di *PremiumKita*!`;
                                await sendSingleReply(failedReplyMsg);
                            } else {
                                const confirmedPhone = claimedProfilePhone || senderPhone || 'Akun Anda';
                                const verifiedMsg = `✅ *VERIFIKASI NOMOR BERHASIL*\n\n` +
                                    `Halo *${extractedName}*!\n` +
                                    `Nomor WhatsApp Anda (*${confirmedPhone}*) telah berhasil divalidasi dan terverifikasi di *PremiumKita*.\n\n` +
                                    `🎉 *Manfaat Akun Terverifikasi:*\n` +
                                    `• Notifikasi pesanan instan ke WhatsApp\n` +
                                    `• Detail akun premium (Email & Password) otomatis dikirim ke chat ini\n` +
                                    `• Akses promo khusus reseller\n\n` +
                                    `Terima kasih telah berbelanja di *PremiumKita*!`;
                                await sendSingleReply(verifiedMsg);
                            }
                        }
                    } catch (webhookErr) {
                        console.error('[WA-BOT] Error saat memproses webhook:', webhookErr.message);
                    }
                }
            } catch (err) {
                console.error('[WA-BOT] Error saat memproses pesan masuk:', err);
            }
        }
    });
}

// ============================================================
// REST API ENDPOINTS UNTUK LARAVEL & ADMIN PANEL
// ============================================================

app.get('/api/status', (req, res) => {
    res.json({
        version: GATEWAY_VERSION,
        status: connectionStatus,
        connected: connectionStatus === 'connected',
        phone: botPhoneNumber,
        qr: latestQrDataUrl
    });
});

app.get('/api/check-code/:code', (req, res) => {
    loadDetectedCodesFromDisk();
    const code = String(req.params.code || '').trim().toUpperCase();
    const found = recentDetectedCodes.get(code);
    if (found) {
        return res.json({
            status: 'success',
            found: true,
            data: found
        });
    }
    return res.json({
        status: 'waiting',
        found: false
    });
});

app.post(['/api/send-wa', '/api/send', '/send-message'], async (req, res) => {
    const { target, message } = req.body;

    if (!target || !message) {
        return res.status(400).json({ status: 'error', message: 'Target (nomor telepon) dan message wajib diisi!' });
    }

    if (connectionStatus !== 'connected' || !sock) {
        return res.status(503).json({
            status: 'error',
            message: 'WhatsApp Bot belum terhubung! Silakan scan QR code terlebih dahulu di Admin Panel.'
        });
    }

    try {
        const jid = formatToJid(target);
        console.log(`[WA-BOT] 📤 Mengirim pesan ke ${jid}...`);

        const sent = await sock.sendMessage(jid, { text: message });
        res.json({
            status: 'success',
            target: target,
            jid: jid,
            messageId: sent.key.id
        });
    } catch (err) {
        console.error('[WA-BOT] Gagal mengirim pesan:', err);
        res.status(500).json({ status: 'error', message: err.message });
    }
});

app.post('/api/logout', async (req, res) => {
    try {
        console.log('[WA-BOT] 🔄 Request logout diterima, mereset sesi...');
        if (sock) {
            try { await sock.logout(); } catch (e) {}
        }
        if (fs.existsSync(SESSION_DIR)) {
            fs.rmSync(SESSION_DIR, { recursive: true, force: true });
        }
        connectionStatus = 'disconnected';
        botPhoneNumber = null;
        latestQrDataUrl = null;

        setTimeout(() => startWhatsAppBot(), 1500);

        res.json({ status: 'success', message: 'Sesi berhasil direset. Silakan scan QR baru.' });
    } catch (err) {
        res.status(500).json({ status: 'error', message: err.message });
    }
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`=================================================`);
    console.log(`🤖 PREMIUMKITA WHATSAPP GATEWAY SERVICE v${GATEWAY_VERSION} AKTIF!`);
    console.log(`📡 Port API      : http://127.0.0.1:${PORT}`);
    console.log(`=================================================`);
    startWhatsAppBot();
});
