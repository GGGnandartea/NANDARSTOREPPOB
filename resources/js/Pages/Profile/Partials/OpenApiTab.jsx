import React, { useState, useRef } from 'react';
import { Icon, fmt } from './ProfileIcons';

export default function OpenApiTab({
  user,
  isReseller,
  openApiData,
  onOpenTopup,
  onOpenUpgradeModal,
  showToast,
}) {
  const baseUrl = openApiData?.base_url || (typeof window !== 'undefined' ? `${window.location.origin}/api/v1` : '/api/v1');
  const policy = openApiData?.policy || {};
  const callerIp = openApiData?.caller_ip || '';
  const globalEnabled = openApiData?.global_enabled !== false;

  const [clientState, setClientState] = useState(openApiData?.client || null);
  const [recentLogs] = useState(openApiData?.recent_logs || []);

  // Sub-tabs: config | docs | logs
  const [activeSubTab, setActiveSubTab] = useState('config');
  const [codeLang, setCodeLang] = useState('php');

  // Newly generated / rotated one-time secrets
  const [freshSecrets, setFreshSecrets] = useState(null);
  const [revealSecret, setRevealSecret] = useState(false);
  const [rotateWebhookToo, setRotateWebhookToo] = useState(false);

  // Config form state
  const [ipWhitelist, setIpWhitelist] = useState(openApiData?.client?.ip_whitelist || '');
  const [webhookUrl, setWebhookUrl] = useState(openApiData?.client?.webhook_url || '');
  const [fieldErrors, setFieldErrors] = useState({});
  const [statusMessage, setStatusMessage] = useState(null);

  // Loading states
  const [isGenerating, setIsGenerating] = useState(false);
  const [isRotating, setIsRotating] = useState(false);
  const [isSavingConfig, setIsSavingConfig] = useState(false);
  const [isToggling, setIsToggling] = useState(false);
  const [isTestingWebhook, setIsTestingWebhook] = useState(false);

  const ipInputRef = useRef(null);
  const whInputRef = useRef(null);

  const csrfToken = () =>
    (typeof document !== 'undefined' && document.querySelector('meta[name="csrf-token"]')?.content) || '';

  const copyText = async (val, label = 'Teks') => {
    if (!val) return;
    try {
      await navigator.clipboard.writeText(val);
      if (showToast) showToast('success', `${label} berhasil disalin!`);
    } catch {
      if (showToast) showToast('error', `Gagal menyalin ${label}.`);
    }
  };

  // Validate fields on blur or submit (clear on input per modern-web-guidance forms rule)
  const validateWebhookField = (value) => {
    const trimmed = String(value || '').trim();
    if (!trimmed) return '';
    try {
      const parsed = new URL(trimmed);
      if (!['http:', 'https:'].includes(parsed.protocol)) {
        return 'Webhook URL wajib diawali https:// (atau http:// untuk pengujian).';
      }
      if (['localhost', '127.0.0.1', '0.0.0.0', '::1'].includes(parsed.hostname.toLowerCase())) {
        return 'Webhook URL tidak boleh mengarah ke localhost / IP lokal internal.';
      }
      return '';
    } catch {
      return 'Format URL tidak valid. Contoh: https://websiteanda.com/api/webhook/premiumkita';
    }
  };

  const validateIpWhitelistField = (value) => {
    const trimmed = String(value || '').trim();
    if (!trimmed) {
      if (policy?.require_ip_whitelist) {
        return 'Kebijakan keamanan mewajibkan minimal 1 IP Server didaftarkan.';
      }
      return '';
    }
    const entries = trimmed
      .split(/[\r\n,;]+/)
      .map((s) => s.trim())
      .filter(Boolean);
    if (entries.length > 25) {
      return 'Maksimal 25 baris IP / CIDR yang diperbolehkan.';
    }
    const ipv4OrCidr = /^(\d{1,3}\.){3}\d{1,3}(\/([89]|[12]\d|3[0-2]))?$/;
    const ipv6Loose = /^[0-9a-fA-F:]+(\/\d{2,3})?$/;
    for (const item of entries) {
      if (!ipv4OrCidr.test(item) && !ipv6Loose.test(item)) {
        return `Format IP/CIDR tidak dikenali: "${item}". Gunakan contoh 103.150.10.25 atau 103.150.10.0/24.`;
      }
    }
    return '';
  };

  const handleGenerate = async () => {
    setIsGenerating(true);
    setStatusMessage(null);
    try {
      const res = await fetch('/profile/open-api/generate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setClientState(data.client);
        setIpWhitelist(data.client?.ip_whitelist || '');
        setWebhookUrl(data.client?.webhook_url || '');
        setFreshSecrets({
          api_key: data.api_key,
          secret_key: data.secret_key,
          webhook_secret: data.webhook_secret,
        });
        setRevealSecret(true);
        setStatusMessage({ type: 'success', text: data.message });
        if (showToast) showToast('success', 'API Key & Secret Key berhasil dibuat!');
      } else {
        setStatusMessage({ type: 'error', text: data.message || 'Gagal membuat API Key.' });
      }
    } catch {
      setStatusMessage({ type: 'error', text: 'Gagal menghubungi server. Coba muat ulang halaman.' });
    }
    setIsGenerating(false);
  };

  const handleRotate = async () => {
    if (typeof window !== 'undefined' && !window.confirm('Rotasi kredensial sekarang? API Key & Secret Key lama di website Anda akan langsung berhenti berfungsi sampai Anda memperbaruinya.')) {
      return;
    }
    setIsRotating(true);
    setStatusMessage(null);
    try {
      const res = await fetch('/profile/open-api/rotate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ rotate_webhook_secret: rotateWebhookToo }),
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setClientState(data.client);
        setFreshSecrets({
          api_key: data.api_key,
          secret_key: data.secret_key,
          webhook_secret: data.webhook_secret,
        });
        setRevealSecret(true);
        setStatusMessage({ type: 'success', text: data.message });
        if (showToast) showToast('success', 'Kredensial API berhasil dirotasi!');
      } else {
        setStatusMessage({ type: 'error', text: data.message || 'Gagal merotasi API Key.' });
      }
    } catch {
      setStatusMessage({ type: 'error', text: 'Terjadi gangguan jaringan saat rotasi kunci.' });
    }
    setIsRotating(false);
  };

  const handleToggleClient = async () => {
    setIsToggling(true);
    try {
      const res = await fetch('/profile/open-api/toggle', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setClientState(data.client);
        if (showToast) showToast('success', data.message);
      } else if (showToast) {
        showToast('error', data.message || 'Gagal mengubah status API.');
      }
    } catch {
      if (showToast) showToast('error', 'Terjadi kesalahan jaringan.');
    }
    setIsToggling(false);
  };

  const handleSaveConfig = async (e) => {
    e.preventDefault();
    const ipErr = validateIpWhitelistField(ipWhitelist);
    const whErr = validateWebhookField(webhookUrl);
    if (ipErr || whErr) {
      setFieldErrors({ ip_whitelist: ipErr, webhook_url: whErr });
      if (ipErr && ipInputRef.current) ipInputRef.current.focus();
      else if (whErr && whInputRef.current) whInputRef.current.focus();
      return;
    }

    setIsSavingConfig(true);
    setStatusMessage(null);
    try {
      const res = await fetch('/profile/open-api/config', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({
          ip_whitelist: ipWhitelist,
          webhook_url: webhookUrl,
          is_enabled: clientState?.is_enabled ?? true,
        }),
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setClientState(data.client);
        setIpWhitelist(data.client?.ip_whitelist || '');
        setWebhookUrl(data.client?.webhook_url || '');
        setFieldErrors({});
        setStatusMessage({ type: 'success', text: data.message });
        if (showToast) showToast('success', 'Konfigurasi Open API tersimpan!');
      } else {
        if (data.field) {
          setFieldErrors((prev) => ({ ...prev, [data.field]: data.message }));
        }
        setStatusMessage({ type: 'error', text: data.message || 'Gagal menyimpan konfigurasi.' });
      }
    } catch {
      setStatusMessage({ type: 'error', text: 'Terjadi kesalahan jaringan saat menyimpan konfigurasi.' });
    }
    setIsSavingConfig(false);
  };

  const handleTestWebhook = async () => {
    setIsTestingWebhook(true);
    setStatusMessage(null);
    try {
      const res = await fetch('/profile/open-api/test-webhook', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
      });
      const data = await res.json();
      setStatusMessage({
        type: res.ok && data.status === 'success' ? 'success' : 'error',
        text: data.message || 'Pengujian selesai.',
      });
      if (showToast) {
        showToast(res.ok && data.status === 'success' ? 'success' : 'error', data.message || 'Uji webhook selesai.');
      }
    } catch {
      setStatusMessage({ type: 'error', text: 'Gagal melakukan test ping webhook.' });
    }
    setIsTestingWebhook(false);
  };

  const handleAppendCallerIp = () => {
    if (!callerIp) return;
    const currentList = ipWhitelist
      .split(/[\r\n,;]+/)
      .map((s) => s.trim())
      .filter(Boolean);
    if (!currentList.includes(callerIp)) {
      const nextVal = [...currentList, callerIp].join('\n');
      setIpWhitelist(nextVal);
      setFieldErrors((prev) => ({ ...prev, ip_whitelist: '' }));
    }
  };

  // =========================================================================
  // TAMPILAN TERKUNCI KHUSUS USER BIASA (NON-RESELLER)
  // =========================================================================
  if (!isReseller) {
    return (
      <div className="bg-white dark:bg-gray-800 rounded-3xl border border-amber-200 dark:border-amber-700/40 p-6 sm:p-8 shadow-sm">
        <div className="max-w-2xl mx-auto text-center space-y-4">
          <div className="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-amber-400 to-yellow-600 text-gray-950 flex items-center justify-center shadow-lg shadow-amber-500/20">
            <Icon.Lock className="w-8 h-8" />
          </div>
          <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 border border-amber-300/60 dark:border-amber-700">
            Eksklusif Mitra VIP Reseller PremiumKita
          </span>
          <h2 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">
            Integrasi Open API H2H PremiumKita Terkunci
          </h2>
          <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
            Fitur koneksi <strong>Open API PremiumKita</strong> hanya dapat diakses oleh akun berstatus <strong>VIP Reseller</strong>. Akun pengguna biasa tidak diizinkan membuat API Key maupun memanggil endpoint <code className="font-mono font-bold text-amber-600 dark:text-amber-400">/api/v1/*</code>.
          </p>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 text-left pt-2">
            <div className="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700">
              <p className="text-xs font-black text-gray-900 dark:text-white">Katalog Resmi PremiumKita</p>
              <p className="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                Tarik stok &amp; beli otomatis seluruh katalog Aplikasi Premium resmi PremiumKita dengan harga khusus VIP Reseller.
              </p>
            </div>
            <div className="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700">
              <p className="text-xs font-black text-gray-900 dark:text-white">Potong Saldo Otomatis</p>
              <p className="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                Transaksi 24 jam penuh menggunakan Saldo Akun tanpa scan QRIS manual + Auto-Refund 100% bila stok habis.
              </p>
            </div>
            <div className="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700">
              <p className="text-xs font-black text-gray-900 dark:text-white">Keamanan Standar H2H</p>
              <p className="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                Dilengkapi tanda tangan HMAC-SHA256, IP Whitelist CIDR, Idempotency Key, dan Webhook Callback.
              </p>
            </div>
          </div>

          <div className="pt-3">
            <button
              type="button"
              onClick={onOpenUpgradeModal}
              className="px-6 py-3.5 rounded-2xl bg-gradient-to-r from-amber-500 via-yellow-500 to-amber-600 hover:brightness-110 text-gray-950 font-black text-xs sm:text-sm shadow-lg shadow-amber-500/25 transition cursor-pointer"
            >
              Upgrade ke VIP Reseller untuk Membuka Open API
            </button>
          </div>
        </div>
      </div>
    );
  }

  // =========================================================================
  // CONTOH KODE DOKUMENTASI DINAMIS (SESUAI API KEY & BASE URL RESELLER)
  // =========================================================================
  const sampleApiKey = freshSecrets?.api_key || clientState?.api_key || 'pk_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
  const sampleSecretKey = freshSecrets?.secret_key || 'sk_live_ganti_dengan_secret_key_asli_anda';

  const phpSnippet = `<?php
$baseUrl   = '${baseUrl}';
$apiKey    = '${sampleApiKey}';
$secretKey = '${sampleSecretKey}';

function callPremiumKitaApi(string $method, string $endpointPath, array $payload = null): array {
    global $baseUrl, $apiKey, $secretKey;

    $method    = strtoupper($method);
    $fullPath  = '/api/v1/' . ltrim($endpointPath, '/'); // Contoh: /api/v1/orders
    $rawBody   = ($payload !== null) ? json_encode($payload, JSON_UNESCAPED_SLASHES) : '';
    $timestamp = (string) time();

    // Standar Signature: METHOD + "\\n" + PATH + "\\n" + TIMESTAMP + "\\n" + SHA256(RAW_BODY)
    $stringToSign = $method . "\\n" . $fullPath . "\\n" . $timestamp . "\\n" . hash('sha256', $rawBody);
    $signature    = hash_hmac('sha256', $stringToSign, $secretKey);

    $ch = curl_init(rtrim($baseUrl, '/') . '/' . ltrim($endpointPath, '/'));
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey,
            'X-Timestamp: ' . $timestamp,
            'X-Signature: ' . $signature,
        ],
    ]);

    if ($rawBody !== '') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
    }

    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response ?: '{}', true);
}

// 1. Cek Katalog Produk Aplikasi Premium Resmi PremiumKita
$catalog = callPremiumKitaApi('GET', '/products');

// 2. Order Aplikasi Premium (100% Potong Saldo Reseller + Anti-Dobel ref_id)
$order = callPremiumKitaApi('POST', '/orders', [
    'ref_id'         => 'INV-WEBANDA-20261111-0001',
    'product_id'     => 12,
    'quantity'       => 1,
    'customer_name'  => 'Budi Santoso',
    'customer_email' => 'pembeli@domainanda.com',
]);
print_r($order);`;

  const nodeSnippet = `import crypto from 'node:crypto';

const BASE_URL = '${baseUrl}';
const API_KEY = '${sampleApiKey}';
const SECRET_KEY = '${sampleSecretKey}';

export async function premiumKitaRequest(method, endpointPath, bodyObj = null) {
  const upperMethod = method.toUpperCase();
  const cleanEndpoint = endpointPath.replace(/^\\/+/, '');
  const canonicalPath = \`/api/v1/\${cleanEndpoint.split('?')[0]}\`;
  const rawBody = bodyObj ? JSON.stringify(bodyObj) : '';
  const timestamp = Math.floor(Date.now() / 1000).toString();

  const bodySha256 = crypto.createHash('sha256').update(rawBody).digest('hex');
  const payloadToSign = \`\${upperMethod}\\n\${canonicalPath}\\n\${timestamp}\\n\${bodySha256}\`;
  const signature = crypto.createHmac('sha256', SECRET_KEY).update(payloadToSign).digest('hex');

  const res = await fetch(\`\${BASE_URL}/\${cleanEndpoint}\`, {
    method: upperMethod,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-API-Key': API_KEY,
      'X-Timestamp': timestamp,
      'X-Signature': signature,
    },
    body: rawBody || undefined,
  });

  return res.json();
}

// Contoh Pembelian Aplikasi Premium (Potong Saldo Akun Reseller)
const result = await premiumKitaRequest('POST', 'orders', {
  ref_id: 'ORD-NODE-20261111-001',
  product_code: 'PK-APP-12',
  quantity: 1,
});
console.log(result);`;

  const curlSnippet = `API_KEY="${sampleApiKey}"
SECRET_KEY="${sampleSecretKey}"
TS=$(date +%s)
BODY='{"ref_id":"TRX-CURL-001","product_id":12,"quantity":1}'
BODY_HASH=$(printf '%s' "$BODY" | openssl dgst -sha256 | awk '{print $NF}')
SIGN_PAYLOAD=$(printf "POST\\n/api/v1/orders\\n%s\\n%s" "$TS" "$BODY_HASH")
SIGNATURE=$(printf '%s' "$SIGN_PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET_KEY" | awk '{print $NF}')

curl -X POST "${baseUrl}/orders" \\
  -H "Accept: application/json" \\
  -H "Content-Type: application/json" \\
  -H "X-API-Key: $API_KEY" \\
  -H "X-Timestamp: $TS" \\
  -H "X-Signature: $SIGNATURE" \\
  -d "$BODY"`;

  const webhookVerifySnippet = `<?php
// Contoh Endpoint Penerima Webhook di Website Pribadi Anda
$webhookSecret = 'whsec_ganti_dengan_webhook_secret_anda';
$rawBody       = file_get_contents('php://input');
$timestamp     = $_SERVER['HTTP_X_PK_TIMESTAMP'] ?? '';
$providedSig   = $_SERVER['HTTP_X_PK_SIGNATURE'] ?? ''; // Format: sha256=abcdef...

// 1. Validasi umur timestamp (maks 5 menit)
if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) {
    http_response_code(401);
    exit('Expired timestamp');
}

// 2. Verifikasi HMAC-SHA256 (timestamp + "." + raw_json_body)
$expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $webhookSecret);
if (!hash_equals($expected, $providedSig)) {
    http_response_code(401);
    exit('Invalid signature');
}

$payload = json_decode($rawBody, true);
$event   = $payload['event'] ?? ''; // "order.completed" atau "order.refunded"
$order   = $payload['data'] ?? [];

if ($event === 'order.completed') {
    // Simpan $order['account_details'] ke pesanan pelanggan di website Anda
} elseif ($event === 'order.refunded') {
    // Saldo Reseller Anda telah dikembalikan 100%, tandai pesanan gagal/refund di website Anda
}

http_response_code(200);
echo json_encode(['received' => true]);`;

  const currentCode = codeLang === 'php' ? phpSnippet : codeLang === 'node' ? nodeSnippet : curlSnippet;

  return (
    <div className="space-y-5">
      {/* HEADER RINGKASAN OPEN API & SALDO RESELLER */}
      <div className="bg-gradient-to-br from-[#111827] via-[#1e293b] to-[#0f172a] border border-amber-500/30 rounded-3xl p-5 sm:p-6 text-white shadow-lg">
        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div className="space-y-1.5">
            <div className="flex flex-wrap items-center gap-2">
              <span className="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase bg-amber-400 text-gray-950">
                VIP Reseller H2H Open API
              </span>
              {!globalEnabled ? (
                <span className="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase bg-rose-500/20 text-rose-300 border border-rose-500/40">
                  Maintenance Global
                </span>
              ) : clientState?.admin_suspended ? (
                <span className="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase bg-rose-500/20 text-rose-300 border border-rose-500/40">
                  Ditangguhkan Admin
                </span>
              ) : clientState?.is_enabled ? (
                <span className="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                  Koneksi API Aktif
                </span>
              ) : (
                <span className="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase bg-slate-700 text-slate-300">
                  Belum Aktif / Dijeda
                </span>
              )}
            </div>
            <h2 className="text-lg sm:text-xl font-black tracking-tight">
              Integrasi Website Pribadi — Open API PremiumKita
            </h2>
            <p className="text-xs text-slate-300 leading-relaxed max-w-2xl">
              Hubungkan website toko Anda langsung ke server resmi <strong>PremiumKita</strong>. Cakupan khusus katalog <strong>Aplikasi Premium Resmi PremiumKita</strong> dengan pemotongan otomatis dari <strong>Saldo Reseller VIP</strong> Anda.
            </p>
          </div>

          <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
            <div className="px-4 py-3 rounded-2xl bg-white/5 border border-white/10">
              <p className="text-[10px] font-extrabold uppercase tracking-wider text-amber-300">Saldo Penentu Transaksi API</p>
              <p className="text-lg font-black text-white mt-0.5">{fmt.rp(user?.balance || 0)}</p>
            </div>
            <button
              type="button"
              onClick={onOpenTopup}
              className="px-4 py-3 rounded-2xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:brightness-110 text-gray-950 font-black text-xs shadow-md transition cursor-pointer flex items-center justify-center gap-1.5"
            >
              <Icon.Plus className="w-4 h-4" />
              Top Up Saldo API
            </button>
          </div>
        </div>

        {/* Base URL Bar */}
        <div className="mt-4 pt-4 border-t border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
          <div className="flex items-center gap-2 min-w-0">
            <span className="text-slate-400 font-bold shrink-0">Base URL API:</span>
            <code className="px-2.5 py-1 rounded-lg bg-black/40 text-amber-300 font-mono font-bold truncate">
              {baseUrl}
            </code>
            <button
              type="button"
              onClick={() => copyText(baseUrl, 'Base URL API')}
              className="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-white text-[11px] font-bold cursor-pointer shrink-0"
            >
              Salin
            </button>
          </div>
          <div className="text-[11px] text-slate-300">
            Katalog Tersedia: <strong className="text-white">{policy.eligible_products_count ?? 0} Produk</strong> • Rate Limit: <strong className="text-white">{clientState?.custom_rate_limit || policy.rate_limit_rpm || 60} req/menit</strong>
          </div>
        </div>
      </div>

      {/* NAVIGASI SUB-TAB (SINGLE-ROW HORIZONTAL SCROLL + UNDERLINE SHAPE PER WCAG 1.4.1) */}
      <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 px-3 pt-1 shadow-sm">
        <div
          role="tablist"
          aria-label="Menu Open API Reseller"
          className="flex overflow-x-auto no-scrollbar gap-2"
        >
          {[
            { id: 'config', label: 'Konfigurasi & Kredensial API' },
            { id: 'docs', label: 'Dokumentasi API & Contoh Kode' },
            { id: 'logs', label: `Log Request API (${recentLogs.length})` },
          ].map((tab) => {
            const selected = activeSubTab === tab.id;
            return (
              <button
                key={tab.id}
                id={`openapi-tab-${tab.id}`}
                type="button"
                role="tab"
                aria-selected={selected}
                onClick={(e) => {
                  setActiveSubTab(tab.id);
                  e.currentTarget.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                }}
                className={`relative flex-none px-4 py-3 text-xs sm:text-sm font-bold whitespace-nowrap transition cursor-pointer ${
                  selected
                    ? 'text-amber-600 dark:text-amber-400 after:content-[""] after:absolute after:inset-x-0 after:bottom-0 after:border-b-[3px] after:border-amber-500'
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'
                }`}
              >
                {tab.label}
              </button>
            );
          })}
        </div>
      </div>

      {/* STATUS BANNER DINAMIS (ACCESSIBLE LIVE REGION) */}
      <div role="status" aria-live="polite">
        {statusMessage && (
          <div
            className={`p-4 rounded-2xl border text-xs font-bold ${
              statusMessage.type === 'success'
                ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300'
                : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300'
            }`}
          >
            {statusMessage.text}
          </div>
        )}
      </div>

      {/* =====================================================================
          PANEL 1: KONFIGURASI & KEAMANAN API
      ===================================================================== */}
      <div
        role="tabpanel"
        aria-labelledby="openapi-tab-config"
        hidden={activeSubTab !== 'config'}
      >
        <div className="space-y-5">
          {/* KARTU KREDENSIAL API KEY & SECRET KEY */}
          <div className="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/60 p-5 sm:p-6 shadow-sm space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700/60 pb-4">
              <div>
                <h3 className="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                  <Icon.Key className="w-5 h-5 text-amber-500" />
                  Kredensial Autentikasi Open API
                </h3>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                  Gunakan <code className="font-mono font-bold">X-API-Key</code> dan tanda tangan HMAC-SHA256 menggunakan <code className="font-mono font-bold">Secret Key</code> di backend server Anda.
                </p>
              </div>

              {clientState && (
                <button
                  type="button"
                  disabled={isToggling}
                  onClick={handleToggleClient}
                  className={`px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer ${
                    clientState.is_enabled
                      ? 'bg-rose-500/15 hover:bg-rose-500/25 text-rose-600 dark:text-rose-400'
                      : 'bg-emerald-600 hover:bg-emerald-500 text-white'
                  }`}
                >
                  {isToggling
                    ? 'Memproses...'
                    : clientState.is_enabled
                    ? 'Jeda Sementara API Saya'
                    : 'Aktifkan Kembali API Saya'}
                </button>
              )}
            </div>

            {!clientState ? (
              <div className="p-6 rounded-2xl bg-gray-50 dark:bg-gray-900/50 border border-dashed border-gray-200 dark:border-gray-700 text-center space-y-3">
                <p className="text-sm font-bold text-gray-800 dark:text-gray-200">
                  Anda belum memiliki API Key &amp; Secret Key Open API
                </p>
                <p className="text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                  Klik tombol di bawah untuk men-generate pasangan kunci kriptografi unik khusus akun VIP Reseller Anda.
                </p>
                <button
                  type="button"
                  disabled={isGenerating}
                  onClick={handleGenerate}
                  className="px-5 py-3 rounded-2xl bg-gradient-to-r from-amber-500 to-yellow-500 hover:brightness-110 text-gray-950 font-black text-xs sm:text-sm shadow-md cursor-pointer"
                >
                  {isGenerating ? 'Sedang Membuat Kredensial...' : 'Buat API Key & Secret Key Sekarang'}
                </button>
              </div>
            ) : (
              <div className="space-y-4">
                {/* KOTAK PERINGATAN ONE-TIME SECRET SAAT BARU GENERATE / ROTATE */}
                {freshSecrets && (
                  <div className="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border-2 border-amber-400 dark:border-amber-600 space-y-3">
                    <div className="flex items-start justify-between gap-2">
                      <div>
                        <p className="text-xs font-black uppercase text-amber-800 dark:text-amber-300">
                          Penting: Salin &amp; Simpan Secret Key Anda Sekarang!
                        </p>
                        <p className="text-[11px] text-amber-700 dark:text-amber-200/90 mt-0.5">
                          Demi standar keamanan, <strong>Secret Key</strong> hanya ditampilkan utuh satu kali saat dibuat. Setelah Anda menutup halaman ini, hanya potongan petunjuk yang ditampilkan.
                        </p>
                      </div>
                      <button
                        type="button"
                        aria-pressed={revealSecret}
                        onClick={() => setRevealSecret((v) => !v)}
                        className="px-2.5 py-1.5 rounded-lg bg-amber-200/80 dark:bg-amber-900 text-amber-950 dark:text-amber-200 text-[11px] font-black cursor-pointer shrink-0"
                      >
                        {revealSecret ? 'Sembunyikan' : 'Tampilkan'}
                      </button>
                    </div>

                    <div className="space-y-2">
                      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-amber-200 dark:border-amber-800">
                        <div className="min-w-0">
                          <span className="text-[10px] font-black uppercase text-gray-400 block">Secret Key (HMAC Signing Key)</span>
                          <code className="text-xs font-mono font-bold text-gray-900 dark:text-white break-all">
                            {revealSecret ? freshSecrets.secret_key : '•'.repeat(40)}
                          </code>
                        </div>
                        <button
                          type="button"
                          onClick={() => copyText(freshSecrets.secret_key, 'Secret Key')}
                          className="px-3 py-1.5 rounded-lg bg-gray-900 dark:bg-amber-500 text-white dark:text-gray-950 text-xs font-black cursor-pointer shrink-0"
                        >
                          Salin Secret Key
                        </button>
                      </div>

                      {freshSecrets.webhook_secret && (
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-amber-200 dark:border-amber-800">
                          <div className="min-w-0">
                            <span className="text-[10px] font-black uppercase text-gray-400 block">Webhook Signing Secret (X-PK-Signature)</span>
                            <code className="text-xs font-mono font-bold text-gray-900 dark:text-white break-all">
                              {revealSecret ? freshSecrets.webhook_secret : '•'.repeat(36)}
                            </code>
                          </div>
                          <button
                            type="button"
                            onClick={() => copyText(freshSecrets.webhook_secret, 'Webhook Secret')}
                            className="px-3 py-1.5 rounded-lg bg-gray-900 dark:bg-amber-500 text-white dark:text-gray-950 text-xs font-black cursor-pointer shrink-0"
                          >
                            Salin Webhook Secret
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                )}

                {/* INFO KUNCI AKTIF */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div className="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200/80 dark:border-gray-700">
                    <span className="text-[11px] font-extrabold uppercase text-gray-400 block">Public API Key (Header X-API-Key)</span>
                    <div className="mt-1.5 flex items-center justify-between gap-2">
                      <code className="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 break-all">
                        {clientState.api_key}
                      </code>
                      <button
                        type="button"
                        onClick={() => copyText(clientState.api_key, 'API Key')}
                        className="px-2.5 py-1 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-[11px] font-bold cursor-pointer shrink-0"
                      >
                        Salin
                      </button>
                    </div>
                  </div>

                  <div className="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200/80 dark:border-gray-700">
                    <span className="text-[11px] font-extrabold uppercase text-gray-400 block">Secret Key &amp; Webhook Secret Tersimpan</span>
                    <div className="mt-1.5 text-xs font-mono text-gray-700 dark:text-gray-300 space-y-0.5">
                      <div>Secret: <strong>{clientState.secret_key_hint}</strong></div>
                      <div>Webhook: <strong>{clientState.webhook_secret_hint || '-'}</strong></div>
                    </div>
                  </div>
                </div>

                {/* ROTASI KUNCI */}
                <div className="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-700/60">
                  <div className="flex items-center gap-2">
                    <input
                      type="checkbox"
                      id="rotate_wh_secret_chk"
                      checked={rotateWebhookToo}
                      onChange={(e) => setRotateWebhookToo(e.target.checked)}
                      className="w-4 h-4 accent-amber-500 rounded"
                    />
                    <label htmlFor="rotate_wh_secret_chk" className="text-xs font-semibold text-gray-600 dark:text-gray-300 cursor-pointer">
                      Ikut ganti (rotasi) Webhook Secret saat rotasi API Key
                    </label>
                  </div>

                  <button
                    type="button"
                    disabled={isRotating}
                    onClick={handleRotate}
                    className="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-extrabold text-gray-800 dark:text-gray-200 transition cursor-pointer flex items-center justify-center gap-1.5"
                  >
                    <Icon.Refresh className="w-3.5 h-3.5" />
                    {isRotating ? 'Merotasi Kunci...' : 'Rotasi (Ganti Baru) API Key'}
                  </button>
                </div>
              </div>
            )}
          </div>

          {/* FORM PENGATURAN IP WHITELIST & WEBHOOK CALLBACK */}
          {clientState && (
            <div className="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/60 p-5 sm:p-6 shadow-sm">
              <h3 id="reseller-api-security-form-title" className="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                <Icon.ShieldCheck className="w-5 h-5 text-emerald-500" />
                Pengaturan Keamanan Server (IP Whitelist &amp; Webhook Callback)
              </h3>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-5">
                Batasi akses API hanya dari IP VPS/Hosting website Anda dan atur URL penerima notifikasi otomatis saat pesanan selesai atau refund.
              </p>

              <form onSubmit={handleSaveConfig} aria-labelledby="reseller-api-security-form-title" className="space-y-5" noValidate>
                {/* IP WHITELIST */}
                <div>
                  <div className="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                    <label htmlFor="ip_whitelist_input" className="text-xs font-extrabold text-gray-800 dark:text-gray-200">
                      Daftar IP Whitelist Server {policy.require_ip_whitelist ? <span className="text-rose-500">* (Wajib Diisi)</span> : '(Opsional — Sangat Disarankan)'}
                    </label>
                    {callerIp && (
                      <button
                        type="button"
                        onClick={handleAppendCallerIp}
                        className="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 cursor-pointer"
                      >
                        + Tambah IP Saya Saat Ini ({callerIp})
                      </button>
                    )}
                  </div>
                  <textarea
                    ref={ipInputRef}
                    id="ip_whitelist_input"
                    name="ip_whitelist"
                    rows={3}
                    value={ipWhitelist}
                    onChange={(e) => {
                      setIpWhitelist(e.target.value);
                      if (fieldErrors.ip_whitelist) {
                        setFieldErrors((prev) => ({ ...prev, ip_whitelist: '' }));
                      }
                    }}
                    onBlur={(e) => {
                      const err = validateIpWhitelistField(e.target.value);
                      if (err) setFieldErrors((prev) => ({ ...prev, ip_whitelist: err }));
                    }}
                    autoCorrect="off"
                    autoCapitalize="none"
                    spellCheck={false}
                    aria-invalid={Boolean(fieldErrors.ip_whitelist)}
                    aria-describedby="ip-whitelist-help ip-whitelist-error"
                    placeholder={'103.150.10.25\n203.0.113.0/24'}
                    className="w-full px-3.5 py-2.5 rounded-2xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 font-mono text-xs text-gray-900 dark:text-white"
                  />
                  <p id="ip-whitelist-help" className="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                    Pisahkan dengan baris baru atau koma (maks. 25 entri). Mendukung IPv4, IPv6, dan subnet CIDR. Jika dikosongkan{policy.require_ip_whitelist ? ' (saat ini diwajibkan oleh Admin)' : ', semua IP diizinkan selama tanda tangan HMAC valid'}.
                  </p>
                  {fieldErrors.ip_whitelist && (
                    <p id="ip-whitelist-error" className="text-xs font-bold text-rose-600 dark:text-rose-400 mt-1">
                      {fieldErrors.ip_whitelist}
                    </p>
                  )}
                </div>

                {/* WEBHOOK URL */}
                <div>
                  <div className="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                    <label htmlFor="webhook_url_input" className="text-xs font-extrabold text-gray-800 dark:text-gray-200">
                      Webhook Callback URL (Opsional)
                    </label>
                    <button
                      type="button"
                      disabled={isTestingWebhook || !clientState.webhook_url}
                      onClick={handleTestWebhook}
                      className="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 disabled:opacity-50 cursor-pointer"
                    >
                      {isTestingWebhook ? 'Mengirim Ping...' : 'Kirim Test Ping Webhook'}
                    </button>
                  </div>
                  <input
                    ref={whInputRef}
                    id="webhook_url_input"
                    name="webhook_url"
                    type="url"
                    value={webhookUrl}
                    onChange={(e) => {
                      setWebhookUrl(e.target.value);
                      if (fieldErrors.webhook_url) {
                        setFieldErrors((prev) => ({ ...prev, webhook_url: '' }));
                      }
                    }}
                    onBlur={(e) => {
                      const err = validateWebhookField(e.target.value);
                      if (err) setFieldErrors((prev) => ({ ...prev, webhook_url: err }));
                    }}
                    autoCorrect="off"
                    autoCapitalize="none"
                    spellCheck={false}
                    aria-invalid={Boolean(fieldErrors.webhook_url)}
                    aria-describedby="webhook-url-help webhook-url-error"
                    placeholder="https://websiteanda.com/api/callback/premiumkita"
                    className="w-full px-3.5 py-2.5 rounded-2xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 font-mono text-xs text-gray-900 dark:text-white"
                  />
                  <p id="webhook-url-help" className="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                    Sistem akan mengirim HTTP POST JSON bertanda tangan <code className="font-mono font-bold">X-PK-Signature</code> saat pesanan berstatus <code className="font-mono">order.completed</code> atau <code className="font-mono">order.refunded</code>. Simpan URL terlebih dahulu sebelum menekan tombol Test Ping.
                  </p>
                  {fieldErrors.webhook_url && (
                    <p id="webhook-url-error" className="text-xs font-bold text-rose-600 dark:text-rose-400 mt-1">
                      {fieldErrors.webhook_url}
                    </p>
                  )}
                </div>

                <div className="flex justify-end pt-1">
                  <button
                    type="submit"
                    disabled={isSavingConfig}
                    className="px-6 py-3 rounded-2xl bg-gradient-to-r from-amber-500 to-yellow-500 hover:brightness-110 text-gray-950 font-black text-xs sm:text-sm shadow-md transition cursor-pointer disabled:opacity-60"
                  >
                    {isSavingConfig ? 'Menyimpan...' : 'Simpan Konfigurasi Keamanan API'}
                  </button>
                </div>
              </form>
            </div>
          )}
        </div>
      </div>

      {/* =====================================================================
          PANEL 2: DOKUMENTASI LENGKAP OPEN API & CONTOH KODE
      ===================================================================== */}
      <div
        role="tabpanel"
        aria-labelledby="openapi-tab-docs"
        hidden={activeSubTab !== 'docs'}
      >
        <div className="space-y-5">
          {/* 1. ATURAN BISNIS & KEAMANAN STANDAR */}
          <div className="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/60 p-5 sm:p-6 shadow-sm space-y-4">
            <h3 className="text-base sm:text-lg font-black text-gray-900 dark:text-white">
              1. Standar Integrasi, Cakupan Produk &amp; Keamanan Open API v1
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
              <div className="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200/70 dark:border-gray-700 space-y-1">
                <p className="font-black text-gray-900 dark:text-white">Cakupan Eksklusif Aplikasi Premium PremiumKita</p>
                <p className="text-gray-600 dark:text-gray-300 leading-relaxed">
                  Endpoint <code className="font-mono font-bold">/api/v1/products</code> dan <code className="font-mono font-bold">/api/v1/orders</code> khusus melayani katalog <strong>Aplikasi Premium Resmi PremiumKita</strong>. Layanan PPOB maupun produk di luar kategori Aplikasi Premium tidak tersedia melalui Open API ini.
                </p>
              </div>
              <div className="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200/70 dark:border-gray-700 space-y-1">
                <p className="font-black text-gray-900 dark:text-white">Transaksi Murni Saldo Akun + Idempotency</p>
                <p className="text-gray-600 dark:text-gray-300 leading-relaxed">
                  Setiap order langsung memotong <strong>Saldo Reseller VIP</strong> secara atomik (tanpa QRIS). Sertakan <code className="font-mono font-bold">ref_id</code> unik dari website Anda: jika server Anda mengirim ulang <code className="font-mono font-bold">ref_id</code> yang sama saat timeout, saldo dijamin <strong>tidak pernah terpotong dua kali</strong>.
                </p>
              </div>
            </div>

            {/* FORMULA TANDA TANGAN HMAC-SHA256 */}
            <div className="p-4 rounded-2xl bg-slate-900 text-slate-100 space-y-2 text-xs">
              <p className="font-black text-amber-300 uppercase tracking-wider text-[11px]">
                Formula Standar Header Keamanan HMAC-SHA256 (Mode Aktif: {policy.auth_mode || 'hmac_strict'})
              </p>
              <p className="text-slate-300">
                Sertakan 3 header utama pada setiap HTTP request dari backend website Anda:
              </p>
              <pre className="p-3 rounded-xl bg-black/50 text-[11px] font-mono overflow-x-auto text-emerald-300 leading-relaxed">
{`X-API-Key: ${sampleApiKey}
X-Timestamp: 1762830000  (Unix Timestamp detik, toleransi ±${policy.timestamp_tolerance || 300} detik)
X-Signature: <hex_hmac_sha256>

// Cara menghitung X-Signature:
CanonicalString = UPPERCASE(HTTP_METHOD) + "\\n"
                + REQUEST_PATH_TANPA_QUERY (contoh: "/api/v1/orders") + "\\n"
                + X_TIMESTAMP + "\\n"
                + LOWERCASE_HEX(SHA256(RAW_JSON_BODY_ATAU_STRING_KOSONG))

X-Signature     = LOWERCASE_HEX(HMAC_SHA256(CanonicalString, SECRET_KEY))`}
              </pre>
            </div>
          </div>

          {/* 2. TABEL REFERENSI ENDPOINT */}
          <div className="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/60 p-5 sm:p-6 shadow-sm space-y-4">
            <h3 id="openapi-endpoints-caption" className="text-base sm:text-lg font-black text-gray-900 dark:text-white">
              2. Daftar Endpoint REST API (<code className="font-mono text-indigo-600 dark:text-indigo-400">{baseUrl}</code>)
            </h3>

            <div tabIndex={0} role="region" aria-labelledby="openapi-endpoints-caption" className="overflow-auto rounded-2xl border border-gray-200 dark:border-gray-700">
              <table className="w-full text-left border-separate border-spacing-0 text-xs">
                <thead className="sticky top-0 z-10 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 uppercase text-[11px] font-black">
                  <tr>
                    <th className="px-4 py-3 border-b border-gray-200 dark:border-gray-700 whitespace-nowrap">Method &amp; Path</th>
                    <th className="px-4 py-3 border-b border-gray-200 dark:border-gray-700 whitespace-nowrap">Fungsi Utama</th>
                    <th className="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Parameter Query / JSON Body</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 dark:divide-gray-700/60">
                  <tr>
                    <th scope="row" className="px-4 py-3 font-mono font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap bg-white dark:bg-gray-800">GET /api/v1/ping</th>
                    <td className="px-4 py-3 whitespace-nowrap font-semibold text-gray-800 dark:text-gray-200">Tes Koneksi &amp; Auth</td>
                    <td className="px-4 py-3 text-gray-600 dark:text-gray-300">Tanpa parameter. Mengembalikan status server &amp; timestamp Unix untuk kalibrasi jam server.</td>
                  </tr>
                  <tr>
                    <th scope="row" className="px-4 py-3 font-mono font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap bg-white dark:bg-gray-800">GET /api/v1/balance</th>
                    <td className="px-4 py-3 whitespace-nowrap font-semibold text-gray-800 dark:text-gray-200">Cek Saldo Reseller</td>
                    <td className="px-4 py-3 text-gray-600 dark:text-gray-300">Alias <code className="font-mono">/api/v1/profile</code>. Mengembalikan nominal <code className="font-mono">data.balance</code> (IDR) aktif.</td>
                  </tr>
                  <tr>
                    <th scope="row" className="px-4 py-3 font-mono font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap bg-white dark:bg-gray-800">GET /api/v1/products</th>
                    <td className="px-4 py-3 whitespace-nowrap font-semibold text-gray-800 dark:text-gray-200">Katalog Aplikasi Premium</td>
                    <td className="px-4 py-3 text-gray-600 dark:text-gray-300">Opsional query: <code className="font-mono">in_stock=1</code>, <code className="font-mono">delivery_type=instant|manual</code>, <code className="font-mono">category=...</code>, <code className="font-mono">search=...</code></td>
                  </tr>
                  <tr>
                    <th scope="row" className="px-4 py-3 font-mono font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap bg-white dark:bg-gray-800">GET /api/v1/products/{'{id}'}</th>
                    <td className="px-4 py-3 whitespace-nowrap font-semibold text-gray-800 dark:text-gray-200">Detail &amp; Stok Realtime</td>
                    <td className="px-4 py-3 text-gray-600 dark:text-gray-300">Gunakan <code className="font-mono">id</code> numerik (misal <code className="font-mono">12</code>) atau <code className="font-mono">PK-APP-12</code>.</td>
                  </tr>
                  <tr>
                    <th scope="row" className="px-4 py-3 font-mono font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap bg-white dark:bg-gray-800">POST /api/v1/orders</th>
                    <td className="px-4 py-3 whitespace-nowrap font-semibold text-gray-800 dark:text-gray-200">Beli Aplikasi (Potong Saldo)</td>
                    <td className="px-4 py-3 text-gray-600 dark:text-gray-300">
                      JSON wajib: <code className="font-mono">ref_id</code>, <code className="font-mono">product_id</code> (atau <code className="font-mono">product_code</code>). Opsional: <code className="font-mono">quantity</code> (1-{policy.max_qty_per_order || 10}), <code className="font-mono">customer_name</code>, <code className="font-mono">customer_email</code>, <code className="font-mono">callback_url</code>.
                    </td>
                  </tr>
                  <tr>
                    <th scope="row" className="px-4 py-3 font-mono font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap bg-white dark:bg-gray-800">GET /api/v1/orders/{'{ref}'}</th>
                    <td className="px-4 py-3 whitespace-nowrap font-semibold text-gray-800 dark:text-gray-200">Cek Status &amp; Akun</td>
                    <td className="px-4 py-3 text-gray-600 dark:text-gray-300">Bisa diisi <code className="font-mono">invoice_number</code> (<code className="font-mono">API-...</code>) maupun <code className="font-mono">ref_id</code> website Anda. Juga tersedia via <code className="font-mono">POST /api/v1/orders/status</code>.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            {/* CONTOH JSON RESPONSE ORDER */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 pt-2">
              <div>
                <p className="text-xs font-extrabold text-gray-800 dark:text-gray-200 mb-1.5">Contoh Request Body <code className="font-mono">POST /api/v1/orders</code></p>
                <pre className="p-3.5 rounded-2xl bg-slate-900 text-sky-300 font-mono text-[11px] overflow-x-auto leading-relaxed">
{`{
  "ref_id": "TRX-TOKOANDA-20261111-0091",
  "product_id": 12,
  "quantity": 1,
  "customer_name": "Andi Pratama",
  "customer_email": "andi@emailpembeli.com"
}`}
                </pre>
              </div>
              <div>
                <p className="text-xs font-extrabold text-gray-800 dark:text-gray-200 mb-1.5">Contoh Response Sukses (<code className="font-mono">HTTP 200 OK</code>)</p>
                <pre className="p-3.5 rounded-2xl bg-slate-900 text-emerald-300 font-mono text-[11px] overflow-x-auto leading-relaxed">
{`{
  "success": true,
  "message": "Transaksi berhasil! Detail akun aplikasi premium siap digunakan.",
  "data": {
    "invoice_number": "API-8F92K1LA-1762830120",
    "ref_id": "TRX-TOKOANDA-20261111-0091",
    "product_id": 12,
    "product_code": "PK-APP-12",
    "product_name": "Netflix Premium 1 Bulan Private",
    "quantity": 1,
    "unit_price": 28000,
    "total_amount": 28000,
    "currency": "IDR",
    "payment_method": "balance",
    "order_status": "completed",
    "is_ready": true,
    "account_details": "Email: akun@domain.com\\nPassword: Secret123",
    "balance_remaining": 472000
  }
}`}
                </pre>
              </div>
            </div>
          </div>

          {/* 3. CONTOH IMPLEMENTASI KODE SIAP PAKAI */}
          <div className="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/60 p-5 sm:p-6 shadow-sm space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <h3 className="text-base sm:text-lg font-black text-gray-900 dark:text-white">
                3. Contoh Kode Integrasi Backend (Siap Pakai)
              </h3>
              <div className="flex items-center gap-1.5">
                {[
                  { id: 'php', label: 'PHP / Laravel' },
                  { id: 'node', label: 'Node.js' },
                  { id: 'curl', label: 'cURL Bash' },
                ].map((lang) => (
                  <button
                    key={lang.id}
                    type="button"
                    onClick={() => setCodeLang(lang.id)}
                    className={`px-3 py-1.5 rounded-xl text-xs font-extrabold cursor-pointer transition ${
                      codeLang === lang.id
                        ? 'bg-gray-900 dark:bg-amber-500 text-white dark:text-gray-950'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                    }`}
                  >
                    {lang.label}
                  </button>
                ))}
                <button
                  type="button"
                  onClick={() => copyText(currentCode, 'Contoh Kode')}
                  className="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-extrabold cursor-pointer"
                >
                  Salin Kode
                </button>
              </div>
            </div>

            <pre className="p-4 rounded-2xl bg-slate-950 text-slate-100 font-mono text-[11px] overflow-x-auto leading-relaxed">
              {currentCode}
            </pre>

            <div className="pt-2 space-y-2">
              <div className="flex items-center justify-between">
                <h4 className="text-xs sm:text-sm font-black text-gray-900 dark:text-white">
                  Contoh Verifikasi Webhook Callback (<code className="font-mono">X-PK-Signature</code>) di Website Anda
                </h4>
                <button
                  type="button"
                  onClick={() => copyText(webhookVerifySnippet, 'Kode Verifikasi Webhook')}
                  className="px-3 py-1 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-[11px] font-bold cursor-pointer"
                >
                  Salin Contoh Webhook
                </button>
              </div>
              <pre className="p-4 rounded-2xl bg-slate-950 text-emerald-200 font-mono text-[11px] overflow-x-auto leading-relaxed">
                {webhookVerifySnippet}
              </pre>
            </div>
          </div>

          {/* 4. REFERENSI KODE ERROR STANDAR */}
          <div className="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/60 p-5 sm:p-6 shadow-sm space-y-3">
            <h3 id="openapi-errors-caption" className="text-base font-black text-gray-900 dark:text-white">
              4. Standar Kode Error (<code className="font-mono">error_code</code>)
            </h3>
            <div tabIndex={0} role="region" aria-labelledby="openapi-errors-caption" className="overflow-auto rounded-2xl border border-gray-200 dark:border-gray-700">
              <table className="w-full text-left border-separate border-spacing-0 text-xs">
                <thead className="sticky top-0 z-10 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 uppercase text-[11px] font-black">
                  <tr>
                    <th className="px-4 py-2.5 border-b border-gray-200 dark:border-gray-700 whitespace-nowrap">Kode Error</th>
                    <th className="px-4 py-2.5 border-b border-gray-200 dark:border-gray-700 whitespace-nowrap">HTTP</th>
                    <th className="px-4 py-2.5 border-b border-gray-200 dark:border-gray-700">Penyebab &amp; Solusi Penanganan</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 dark:divide-gray-700/60">
                  {[
                    ['RESELLER_ONLY', '403', 'Akun bukan VIP Reseller aktif. Hanya akun Reseller yang diizinkan mengakses Open API.'],
                    ['INVALID_SIGNATURE', '401', 'X-Signature HMAC-SHA256 tidak cocok. Pastikan urutan METHOD, PATH (/api/v1/...), TIMESTAMP, dan SHA256(RAW_BODY) tepat.'],
                    ['EXPIRED_TIMESTAMP', '401', 'Selisih X-Timestamp dengan jam server melebihi batas toleransi keamanan. Sinkronkan NTP pada VPS Anda.'],
                    ['IP_NOT_WHITELISTED', '403', 'IP publik server pemanggil belum didaftarkan di daftar IP Whitelist Anda.'],
                    ['INSUFFICIENT_BALANCE', '402', 'Saldo akun Reseller kurang dari total harga order. Lakukan Top Up Saldo Reseller terlebih dahulu.'],
                    ['OUT_OF_STOCK', '400', 'Stok produk sedang kosong / kurang dari quantity yang diminta. Saldo aman tidak terpotong.'],
                    ['PRODUCT_NOT_AVAILABLE', '404', 'Produk tidak aktif atau tidak termasuk dalam katalog Aplikasi Premium resmi PremiumKita.'],
                    ['REF_ID_CONFLICT', '409', 'Parameter ref_id pernah dipakai untuk produk/quantity berbeda. Gunakan ref_id unik untuk setiap transaksi baru.'],
                    ['RATE_LIMIT_EXCEEDED', '429', 'Frekuensi request melebihi kuota RPM per menit. Patuhi header Retry-After sebelum mengulang request.'],
                  ].map(([code, http, desc]) => (
                    <tr key={code}>
                      <th scope="row" className="px-4 py-2.5 font-mono font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap bg-white dark:bg-gray-800">{code}</th>
                      <td className="px-4 py-2.5 font-bold whitespace-nowrap text-gray-700 dark:text-gray-300">{http}</td>
                      <td className="px-4 py-2.5 text-gray-600 dark:text-gray-300">{desc}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      {/* =====================================================================
          PANEL 3: LOG AKTIVITAS REQUEST API RESELLER
      ===================================================================== */}
      <div
        role="tabpanel"
        aria-labelledby="openapi-tab-logs"
        hidden={activeSubTab !== 'logs'}
      >
        <div className="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/60 p-5 sm:p-6 shadow-sm space-y-4">
          <div>
            <h3 id="user-api-logs-caption" className="text-base font-black text-gray-900 dark:text-white">
              20 Log Request Open API Terakhir Akun Anda
            </h3>
            <p className="text-xs text-gray-500 dark:text-gray-400">
              Gunakan catatan ini untuk memantau koneksi server website pribadi Anda dan mendiagnosis kode error secara cepat.
            </p>
          </div>

          <div tabIndex={0} role="region" aria-labelledby="user-api-logs-caption" className="overflow-auto max-h-[460px] rounded-2xl border border-gray-200 dark:border-gray-700">
            <table className="w-full text-left border-separate border-spacing-0 text-xs">
              <thead className="sticky top-0 z-10 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 uppercase text-[11px] font-black">
                <tr>
                  <th className="px-4 py-3 border-b border-gray-200 dark:border-gray-700 whitespace-nowrap">Waktu (WIB)</th>
                  <th className="px-4 py-3 border-b border-gray-200 dark:border-gray-700 whitespace-nowrap">Endpoint</th>
                  <th className="px-4 py-3 border-b border-gray-200 dark:border-gray-700 whitespace-nowrap">IP Pemanggil</th>
                  <th className="px-4 py-3 border-b border-gray-200 dark:border-gray-700 whitespace-nowrap">Status</th>
                  <th className="px-4 py-3 border-b border-gray-200 dark:border-gray-700 whitespace-nowrap">Keterangan</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100 dark:divide-gray-700/60">
                {recentLogs.length > 0 ? (
                  recentLogs.map((log) => (
                    <tr key={log.id}>
                      <th scope="row" className="px-4 py-2.5 font-mono text-[11px] text-gray-600 dark:text-gray-300 whitespace-nowrap bg-white dark:bg-gray-800">
                        {log.created_at || '-'} <span className="text-gray-400">({log.response_time_ms}ms)</span>
                      </th>
                      <td className="px-4 py-2.5 font-mono text-[11px] whitespace-nowrap">
                        <span className="font-black text-indigo-600 dark:text-indigo-400">{log.method}</span>{' '}
                        <span className="text-gray-800 dark:text-gray-200">{log.endpoint}</span>
                      </td>
                      <td className="px-4 py-2.5 font-mono text-[11px] text-gray-500 whitespace-nowrap">
                        {log.ip_address || '-'}
                      </td>
                      <td className="px-4 py-2.5 whitespace-nowrap">
                        <span
                          className={`px-2 py-0.5 rounded font-black text-[11px] ${
                            Number(log.status_code) < 400
                              ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                              : 'bg-rose-500/15 text-rose-600 dark:text-rose-400'
                          }`}
                        >
                          {log.status_code}
                        </span>
                        {log.error_code && (
                          <span className="ml-1.5 font-mono text-[10px] text-rose-500">{log.error_code}</span>
                        )}
                      </td>
                      <td className="px-4 py-2.5 text-[11px] text-gray-600 dark:text-gray-300 max-w-sm truncate" title={log.summary || ''}>
                        {log.invoice_number ? `[${log.invoice_number}] ` : ''}
                        {log.ref_id ? `(Ref: ${log.ref_id}) ` : ''}
                        {log.summary || '-'}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={5} className="px-4 py-8 text-center text-gray-400 font-semibold">
                      Belum ada riwayat panggilan API dari server Anda.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
