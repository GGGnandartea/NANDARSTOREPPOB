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

function resolveLaravelWebhookUrl() {
    if (process.env.LARAVEL_WEBHOOK_URL) {
        return process.env.LARAVEL_WEBHOOK_URL;
    }
    try {
        const envPath = path.join(__dirname, '..', '.env');
        if (fs.existsSync(envPath)) {
            const envContent = fs.readFileSync(envPath, 'utf8');
            const match = envContent.match(/^APP_URL=(.+)$/m);
            if (match && match[1]) {
                const appUrl = match[1].trim().replace(/^["']|["']$/g, '').replace(/\/+$/, '');
                if (appUrl && appUrl !== 'http://localhost') {
                    return `${appUrl}/api/webhook/wa-incoming`;
                }
            }
        }
    } catch (e) {}
    return 'http://127.0.0.1:8000/api/webhook/wa-incoming';
}

const LARAVEL_WEBHOOK_URL = resolveLaravelWebhookUrl();
const SESSION_DIR = path.join(__dirname, 'session');

let sock = null;
let latestQrDataUrl = null;
let connectionStatus = 'initializing'; // initializing, qr_ready, connecting, connected, disconnected
let botPhoneNumber = null;

// Helper: Format nomor telepon menjadi jid WhatsApp (contoh: 08990738216 -> 628990738216@s.whatsapp.net)
function formatToJid(number) {
    if (!number) return null;
    let clean = number.toString().replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
        clean = '62' + clean.slice(1);
    } else if (clean.startsWith('8')) {
        clean = '62' + clean;
    }
    return clean + '@s.whatsapp.net';
}

function extractMessageText(m) {
    if (!m) return '';
    if (m.conversation) return m.conversation;
    if (m.extendedTextMessage?.text) return m.extendedTextMessage.text;
    if (m.imageMessage?.caption) return m.imageMessage.caption;
    if (m.videoMessage?.caption) return m.videoMessage.caption;
    if (m.documentMessage?.caption) return m.documentMessage.caption;
    if (m.ephemeralMessage?.message) return extractMessageText(m.ephemeralMessage.message);
    if (m.viewOnceMessage?.message) return extractMessageText(m.viewOnceMessage.message);
    if (m.viewOnceMessageV2?.message) return extractMessageText(m.viewOnceMessageV2.message);
    return '';
}

function formatFromJid(jid) {
    if (!jid) return '';
    let clean = jid.split('@')[0];
    if (clean.startsWith('62')) {
        return '0' + clean.slice(2);
    }
    return clean;
}

// Inisialisasi WhatsApp Socket dengan Baileys
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
        browser: ['PremiumKita WA Gateway', 'Desktop', '2.0.0'],
        syncFullHistory: false
    });

    // Simpan credentials saat ada update
    sock.ev.on('creds.update', saveCreds);

    // Monitor perubahan status koneksi & QR code
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

    // Set untuk mencegah pemrosesan pesan ganda dari event Baileys
    const processedMsgIds = new Set();

    // Event Listener Pesan Masuk (Upsert)
    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type !== 'notify') return;

        for (const msg of messages) {
            try {
                // Abaikan pesan dari diri sendiri atau status broadcast
                if (msg.key.fromMe) continue;
                const remoteJid = msg.key.remoteJid || '';
                if (remoteJid.includes('@broadcast') || remoteJid.includes('@g.us')) continue;

                // Cegah pemrosesan duplikat ID pesan yang sama
                const msgId = msg.key?.id;
                if (msgId) {
                    if (processedMsgIds.has(msgId)) {
                        continue;
                    }
                    processedMsgIds.add(msgId);
                    if (processedMsgIds.size > 2000) {
                        const first = processedMsgIds.values().next().value;
                        processedMsgIds.delete(first);
                    }
                }

                const text = extractMessageText(msg.message);

                const senderJid = remoteJid;
                const isLid = senderJid.includes('@lid');
                let senderPhone = '';
                const textPhoneMatch = text.match(/\*Nomor WA:\*\s*([0-9+]+)/i);
                if (textPhoneMatch) {
                    senderPhone = textPhoneMatch[1].replace(/[^0-9]/g, '');
                } else if (!isLid) {
                    senderPhone = formatFromJid(senderJid);
                }
                const rawSender = senderJid.split('@')[0];

                console.log(`[WA-BOT] 📥 Pesan masuk dari ${senderPhone || senderJid} (${rawSender}): "${text.trim()}"`);

                // Deteksi Kode Verifikasi PK-XXXXX / PK-XXXXX, VERIF-XXXXX, OTP-XXXXX, atau RESET-XXXXX
                const match = text.match(/(PK-\d{4,6}|NS-\d{4,6}|VERIF-\d{4,6}|OTP-[A-Z0-9]+|RESET-[A-Z0-9]+)/i);
                if (match) {
                    const code = match[1].toUpperCase();
                    console.log(`[WA-BOT] 🔍 Terdeteksi kode: ${code} dari ${senderPhone || senderJid}`);

                    // Teruskan ke Laravel Webhook
                    try {
                        const response = await fetch(LARAVEL_WEBHOOK_URL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                message: text,
                                code: code,
                                sender: senderPhone,
                                raw_sender: rawSender
                            })
                        });

                        const resData = await response.json();
                        console.log('[WA-BOT] Respon Webhook Laravel:', resData);

                        // Helper pengiriman balasan HANYA 1 KALI (tidak dobel)
                        const sendSingleReply = async (replyMsg) => {
                            let delivered = false;

                            // 1. Coba kirim langsung ke senderJid (ruang obrolan saat ini)
                            if (senderJid) {
                                try {
                                    await sock.sendMessage(senderJid, { text: replyMsg });
                                    console.log(`[WA-BOT] 📤 Terkirim (1x) ke sender JID: ${senderJid}`);
                                    delivered = true;
                                } catch (err) {
                                    console.warn(`[WA-BOT] Gagal kirim ke sender JID ${senderJid}:`, err.message);
                                }
                            }

                            // 2. Fallback HANYA JIKA pengiriman ke senderJid gagal
                            if (!delivered) {
                                const verifiedPhone = resData.whatsapp || senderPhone;
                                if (verifiedPhone) {
                                    const phoneJid = formatToJid(verifiedPhone);
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

                            return delivered;
                        };

                        if (resData.status === 'send_otp') {
                            const userName = resData.user_name || 'Pelanggan';
                            const otpCode = resData.otp_code;
                            const replyMessage = `🔐 *KODE OTP RESET PASSWORD PREMIUMKITA*\n\n` +
                                                 `Halo *${userName}*!\n` +
                                                 `Kode OTP untuk mereset kata sandi akun Anda adalah:\n\n` +
                                                 `*KODE:* ${otpCode}\n\n` +
                                                 `⏱️ _Berlaku 15 menit & otomatis hangus jika sudah 1x digunakan._\n` +
                                                 `🔒 _Jangan berikan kode ini kepada siapapun demi keamanan._`;

                            await sendSingleReply(replyMessage);
                            console.log(`[WA-BOT] 🔑 OTP ${otpCode} selesai dikirim (1x)`);
                        } else if (resData.status === 'otp_expired') {
                            const expMessage = `⚠️ *KODE OTP TELAH HANGUS*\n\n` +
                                                 `Kode permintaan reset password Anda telah hangus karena telah melewati batas waktu 15 menit.\n\n` +
                                                 `Silakan buka kembali website PremiumKita untuk membuat permintaan kode baru.`;
                            await sendSingleReply(expMessage);
                        } else if (resData.status === 'otp_already_used' || resData.status === 'otp_used_or_invalid') {
                            const usedMessage = `⚠️ *KODE SUDAH HANGUS / SUDAH DIGUNAKAN*\n\n` +
                                                  `Kode verifikasi ini sudah pernah digunakan atau sudah hangus.\n\n` +
                                                  `Silakan kunjungi website PremiumKita untuk meminta kode baru jika diperlukan.`;
                            await sendSingleReply(usedMessage);
                        } else if (resData.status === 'verified') {
                            const userName = resData.user_name || 'Kak';
                            const confirmedPhone = resData.whatsapp || senderPhone || 'Akun Anda';
                            const replyMessage = `✅ *VERIFIKASI NOMOR BERHASIL*\n\n` +
                                                 `Halo ${userName}!\n` +
                                                 `Nomor WhatsApp Anda (*${confirmedPhone}*) telah berhasil divalidasi dan terverifikasi di *PremiumKita*.\n\n` +
                                                 `🎉 *Manfaat Akun Terverifikasi:*\n` +
                                                 `• Notifikasi pesanan instan ke WhatsApp\n` +
                                                 `• Detail akun premium (Email & Password) otomatis dikirim ke chat ini\n` +
                                                 `• Akses promo khusus reseller\n\n` +
                                                 `Terima kasih telah berbelanja di PremiumKita! 🛒✨`;

                            await sendSingleReply(replyMessage);
                            console.log(`[WA-BOT] ✅ Balasan verifikasi berhasil dikirim (1x) ke ${confirmedPhone}`);
                        } else if (resData.status === 'already_verified') {
                            const alreadyMessage = `ℹ️ *INFORMASI*\n\nNomor WhatsApp Anda sudah berstatus terverifikasi di PremiumKita.\nJika butuh bantuan, silakan hubungi admin kami.`;
                            await sendSingleReply(alreadyMessage);
                        } else {
                            const notFoundMessage = `⚠️ *KODE TIDAK DITEMUKAN / HANGUS*\n\nKode *${code}* tidak ditemukan, sudah kadaluarsa, atau sudah hangus. Silakan periksa kembali instruksi di website PremiumKita.`;
                            await sendSingleReply(notFoundMessage);
                        }
                    } catch (webhookErr) {
                        console.error('[WA-BOT] Gagal menghubungi Webhook Laravel:', webhookErr.message);
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

// 1. Cek Status Bot & QR Code
app.get('/api/status', (req, res) => {
    res.json({
        status: connectionStatus,
        connected: connectionStatus === 'connected',
        phone: botPhoneNumber,
        qr: latestQrDataUrl
    });
});

// 2. Kirim Pesan WhatsApp
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

// 3. Logout / Reset Sesi (Untuk Scan Ulang Nomor Lain)
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

// Jalankan Express Server
app.listen(PORT, '0.0.0.0', () => {
    console.log(`=================================================`);
    console.log(`🤖 PREMIUMKITA WHATSAPP GATEWAY SERVICE AKTIF!`);
    console.log(`📡 Port API      : http://127.0.0.1:${PORT}`);
    console.log(`🔗 Webhook Tujuan : ${LARAVEL_WEBHOOK_URL}`);
    console.log(`=================================================`);
    startWhatsAppBot();
});

