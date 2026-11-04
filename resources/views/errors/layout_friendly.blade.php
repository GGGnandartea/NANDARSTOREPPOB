<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Server Sedang Sibuk — PremiumKita' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: radial-gradient(circle at top, #1e293b 0%, #090d16 70%);
            color: #f8fafc;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            width: 100%;
            max-width: 440px;
            background: rgba(17, 24, 39, 0.92);
            border: 1px solid rgba(249, 115, 22, 0.28);
            border-radius: 28px;
            padding: 34px 26px;
            text-align: center;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(12px);
        }
        .icon-wrap {
            width: 72px;
            height: 72px;
            margin: 0 auto 18px auto;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.2), rgba(245, 158, 11, 0.12));
            border: 1px solid rgba(249, 115, 22, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            position: relative;
        }
        .spinner-ring {
            position: absolute;
            inset: -4px;
            border-radius: 24px;
            border: 2.5px solid transparent;
            border-top-color: #f97316;
            border-right-color: #fbbf24;
            animation: spin 2.4s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 999px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fbbf24;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            margin-bottom: 14px;
        }
        .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #fbbf24;
            animation: pulse 1.4s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }
        h1 {
            font-size: 21px;
            font-weight: 900;
            color: #ffffff;
            line-height: 1.35;
            margin-bottom: 10px;
        }
        p {
            font-size: 13.5px;
            font-weight: 600;
            color: #94a3b8;
            line-height: 1.65;
            margin-bottom: 24px;
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn-primary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #ffffff;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 20px -4px rgba(249, 115, 22, 0.4);
            transition: transform 0.15s ease, opacity 0.15s ease;
        }
        .btn-primary:hover { transform: translateY(-1px); opacity: 0.96; }
        .btn-secondary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 18px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.15s ease;
        }
        .btn-secondary:hover { background: rgba(255, 255, 255, 0.11); }
        .footer-note {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">
            <div class="spinner-ring"></div>
            <span>{{ $icon ?? '⏳' }}</span>
        </div>
        <div class="badge">
            <span class="dot"></span>
            <span>{{ $badge ?? 'Antrean Server Sedang Padat' }}</span>
        </div>
        <h1>{{ $heading ?? 'Server Sedang Sibuk Sesaat' }}</h1>
        <p>{{ $message ?? 'Saat ini trafik layanan PremiumKita sedang tinggi. Silakan muat ulang halaman ini atau tunggu beberapa detik untuk melanjutkan aktivitas Anda.' }}</p>
        <div class="actions">
            <button type="button" onclick="window.location.reload()" class="btn-primary">
                🔄 Muat Ulang Halaman Sekarang
            </button>
            <a href="/" class="btn-secondary">
                🏠 Kembali ke Halaman Utama
            </a>
            <a href="https://wa.me/6282317458496" target="_blank" rel="noopener noreferrer" class="btn-secondary">
                💬 Hubungi Bantuan Admin WhatsApp
            </a>
        </div>
        <div class="footer-note">
            Sistem Keamanan &amp; Antrean Otomatis • PremiumKita
        </div>
    </div>
</body>
</html>

