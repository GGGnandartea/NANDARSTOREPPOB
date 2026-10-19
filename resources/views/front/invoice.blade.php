<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $order->invoice_number }} - NandarStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    @php
        $siteFavicon = \App\Models\Setting::where('key', 'store_favicon')->value('value') ?: (\App\Models\Setting::where('key', 'store_logo')->value('value') ?: null);
    @endphp
    @if($siteFavicon)
    <link rel="icon" href="{{ $siteFavicon }}">
    @endif
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 30px 15px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .container {
            max-width: 780px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }
        .actions-bar {
            max-width: 780px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-print {
            background-color: #f97316;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.25);
        }
        .btn-print:hover { background-color: #ea580c; }
        .btn-back {
            background-color: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-back:hover { background-color: #f8fafc; color: #0f172a; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 24px;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 28px;
        }
        .brand-title {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #0f172a;
        }
        .brand-title span { color: #f97316; }
        .brand-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
            font-weight: 500;
        }
        .invoice-title-block {
            text-align: right;
        }
        .invoice-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
        }
        .invoice-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
        }
        .badge-paid {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 12px;
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 9999px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
        }
        .info-card-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 4px 0;
        }
        .info-label { color: #64748b; font-weight: 500; }
        .info-value { color: #0f172a; font-weight: 700; text-align: right; }
        .table-container {
            margin-bottom: 28px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        td {
            padding: 14px 16px;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .td-product { font-weight: 700; color: #0f172a; }
        .td-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
        .td-right { text-align: right; font-weight: 700; }
        .total-box {
            background: #f8fafc;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 28px;
            border: 1px solid #e2e8f0;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            padding: 4px 0;
        }
        .total-final {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .total-final span:last-child {
            color: #f97316;
            font-size: 18px;
        }
        .credential-box {
            background: #0b1121;
            color: #ffffff;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #1e293b;
        }
        .credential-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .credential-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #f97316;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .credential-body {
            background: #020617;
            border: 1px solid #1e293b;
            border-radius: 10px;
            padding: 14px 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: #38bdf8;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .btn-copy {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-copy:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #f97316;
            display: inline-block;
            animation: live-pulse 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        @keyframes live-pulse {
            0% { transform: scale(0.9); opacity: 0.8; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.8; }
        }
        @keyframes live-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes live-progress {
            0% { transform: translateX(-40%); }
            100% { transform: translateX(110%); }
        }
        .notes {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 12px;
            color: #92400e;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .footer {
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            padding-top: 16px;
            border-top: 1px dashed #e2e8f0;
        }
        @media print {
            body { background: #ffffff; padding: 0; }
            .actions-bar { display: none !important; }
            .container { box-shadow: none; border: none; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="actions-bar">
        <a href="/" class="btn btn-back">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Beranda
        </a>
        <a href="/profile?tab=orders" class="btn btn-back">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Riwayat Transaksi Saya
        </a>
        <button onclick="window.print()" class="btn btn-print">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak / Simpan PDF
        </button>
    </div>

@php
    $storeLogo = \App\Models\Setting::where('key', 'store_logo')->value('value');
    $storeName = \App\Models\Setting::where('key', 'store_name')->value('value') ?: 'NandarStore';
    $storeTagline = \App\Models\Setting::where('key', 'store_tagline')->value('value') ?: 'Platform Akun Premium & Produk Digital Otomatis';
    $adminWa = \App\Models\Setting::where('key', 'admin_whatsapp')->value('value') ?: '082317458496';
    $adminWaClean = preg_replace('/[^0-9]/', '', $adminWa);
    if (str_starts_with($adminWaClean, '0')) {
        $adminWaClean = '62' . substr($adminWaClean, 1);
    }
    $waGroup = \App\Models\Setting::where('key', 'whatsapp_group_link')->value('value') ?: 'https://chat.whatsapp.com/KfdDhTYboOlLX1PBOkV36H';
@endphp

    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div style="display: flex; align-items: center; gap: 14px;">
                @if(!empty($storeLogo))
                    <img src="{{ $storeLogo }}" alt="{{ $storeName }}" style="width: 48px; height: 48px; object-fit: contain; border-radius: 12px; background: rgba(255,255,255,0.05); padding: 4px;">
                @endif
                <div>
                    <div class="brand-title">{{ $storeName }}</div>
                </div>
            </div>
            <div class="invoice-title-block">
                <div class="invoice-label">Official Invoice</div>
                <div class="invoice-number">{{ $order->invoice_number }}</div>
                @if(strtolower($order->order_status ?? '') === 'refunded')
                    <div style="display: inline-block; padding: 6px 14px; border-radius: 999px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #ef4444; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px;">
                        ↩ DANA TELAH DI-REFUND
                    </div>
                @else
                    <div class="badge-paid">✓ {{ strtoupper($order->payment_status ?? 'LUNAS') }}</div>
                @endif
            </div>
        </div>

        <!-- INFO GRID -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-title">Informasi Pesanan</div>
                <div class="info-row">
                    <span class="info-label">Tanggal</span>
                    <span class="info-value">{{ $order->created_at ? $order->created_at->format('d M Y, H:i') : now()->format('d M Y, H:i') }} WIB</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Metode Bayar</span>
                    <span class="info-value">{{ strtoupper($order->payment_method ?? 'QRIS Otomatis') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status Layanan</span>
                    <span class="info-value">{{ strtoupper($order->order_status ?? 'SELESAI') }}</span>
                </div>
            </div>
            <div class="info-card">
                <div class="info-card-title">Data Pembeli</div>
                <div class="info-row">
                    <span class="info-label">Nama</span>
                    <span class="info-value" style="font-weight: 700; color: #f97316;">{{ $order->buyer_name ?: ($order->user ? $order->user->name : 'Pelanggan') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value">{{ $order->email ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">WhatsApp</span>
                    <span class="info-value">{{ $order->whatsapp ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">ID Pelanggan</span>
                    <span class="info-value">{{ $order->user_id ? 'Member #' . $order->user_id : 'Tamu (Guest)' }}</span>
                </div>
            </div>
        </div>

        <!-- ITEM TABLE -->
        @php
            $qty = (int) ($order->quantity ?? 1);
            if ($qty < 1) $qty = 1;
            $unitPrice = (int) ($order->price ?? ($order->total_amount / $qty));
            $subtotalProduk = $unitPrice * $qty;
        @endphp
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 45%;">Produk &amp; Layanan</th>
                        <th style="width: 15%;">Kategori</th>
                        <th class="td-right" style="width: 15%;">Harga</th>
                        <th style="width: 10%; text-align: center;">Qty</th>
                        <th class="td-right" style="width: 15%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="td-product">{{ $order->product_name }}</div>
                        </td>
                        @php
                            $isPpob = ($order->product_type === 'ppob') 
                                || (isset($order->product) && $order->product->type === 'ppob') 
                                || str_starts_with($order->invoice_number, 'PPOB-');

                            if ($isPpob) {
                                $cat = 'PPOB';
                            } else {
                                $cat = $order->product->category ?? null;
                                if (!$cat || in_array(strtolower($cat), ['api', 'local', 'otomatis', 'manual'])) {
                                    $cat = 'Akun Premium';
                                }
                            }
                        @endphp
                        <td>{{ $cat }}</td>
                        <td class="td-right">Rp {{ number_format($unitPrice, 0, ',', '.') }}</td>
                        <td style="text-align: center; font-weight: 700;">{{ $qty }}x</td>
                        <td class="td-right">Rp {{ number_format($subtotalProduk, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TOTALS -->
        <div class="total-box">
            <div class="total-row">
                <span class="info-label">Subtotal Produk ({{ $qty }} item)</span>
                <span class="info-value">Rp {{ number_format($subtotalProduk, 0, ',', '.') }}</span>
            </div>
            @if(!empty($order->discount_amount) && $order->discount_amount > 0)
            <div class="total-row">
                <span class="info-label">Diskon Voucher ({{ $order->voucher_code ?? 'PROMO' }})</span>
                <span class="info-value" style="color: #ef4444;">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="total-row total-final">
                <span>Total Lunas</span>
                <span>Rp {{ number_format($order->total_amount ?? ($subtotalProduk - ($order->discount_amount ?? 0)), 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- DETAIL PPOB / AKUN KREDENSIAL -->
        @php
            $rawAccount = $order->sn_or_account ?: ($order->account_details ?: '');
            $isWaiting = \App\Services\OrderFulfillmentService::isWaitingText($rawAccount);
        @endphp

        @if($isPpob)
            {{-- TAMPILAN KHUSUS PPOB: Tanpa tulisan Kredensial Akun & Salin Akun --}}
            @if(!empty($rawAccount) && !$isWaiting)
            <div class="credential-box" id="credential-container" style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 16px; padding: 18px 20px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; color: #166534;">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Serial Number (SN) / Kode Token Pengisian</span>
                    </div>
                    <button type="button" id="btn-copy-account" onclick="copyAccountText()" class="btn-copy" style="background: #16a34a; border-color: #15803d; color: #ffffff;">
                        📋 Salin SN
                    </button>
                </div>
                <div class="credential-body" id="credential-text" style="color: #14532d; font-family: 'JetBrains Mono', monospace; font-size: 14px; font-weight: 700; word-break: break-all; background: #ffffff; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px 14px;">{{ $rawAccount }}</div>
            </div>
            @endif

            <!-- CATATAN TRANSAKSI PPOB -->
            <div class="notes" style="background: #f0fdf4; border-color: #bbf7d0; color: #166534;">
                <strong style="color: #15803d;">✅ Informasi Layanan PPOB:</strong><br>
                • Transaksi PPOB diproses secara instan dan otomatis langsung ke nomor / ID tujuan.<br>
                • Simpan nomor invoice ini sebagai bukti sah transaksi Anda.<br>
                • Layanan bantuan: Hubungi CS {{ $storeName }} di WhatsApp <a href="https://wa.me/{{ $adminWaClean }}" target="_blank" style="color: #15803d; font-weight: 800; text-decoration: underline;">+62 823-1745-8496</a> atau gabung <a href="{{ $waGroup }}" target="_blank" style="color: #15803d; font-weight: 800; text-decoration: underline;">Grup Komunitas WhatsApp</a>.
            </div>
        @elseif(strtolower($order->order_status ?? '') === 'refunded')
            <!-- CARD PEMBERITAHUAN KHUSUS ORDER DI-REFUND -->
            <div style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(245, 158, 11, 0.05) 100%); border: 2px solid rgba(239, 68, 68, 0.35); border-radius: 20px; padding: 32px 24px; text-align: center; margin-bottom: 26px; box-shadow: 0 10px 30px -10px rgba(239, 68, 68, 0.15);">
                <div style="width: 64px; height: 64px; margin: 0 auto 16px auto; border-radius: 50%; background: rgba(239, 68, 68, 0.15); display: flex; align-items: center; justify-content: center; font-size: 28px; color: #ef4444;">
                    ↩
                </div>
                <div style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 999px; color: #ef4444; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 12px;">
                    Dana Telah Di-refund 100%
                </div>
                <h3 style="font-size: 20px; font-weight: 900; color: #0f172a; margin-bottom: 8px; line-height: 1.35;">
                    Stok Produk Habis Terbeli Bersamaan
                </h3>
                <p style="font-size: 14px; font-weight: 600; color: #64748b; margin-bottom: 20px; max-width: 480px; margin-left: auto; margin-right: auto; line-height: 1.6;">
                    Mohon maaf, saat Anda menyelesaikan pembayaran, sisa stok produk ini baru saja habis terbeli bersamaan oleh pembeli lain. Dana Anda sebesar <strong>Rp {{ number_format($order->total_amount ?: ($order->price ?: 0), 0, ',', '.') }}</strong> telah otomatis kami kembalikan <strong>100% ke Saldo Akun NandarStore</strong> Anda.
                </p>
                <a href="/profile" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 14px; background: #ea580c; color: #ffffff; font-size: 13px; font-weight: 800; text-decoration: none; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25);">
                    <span>Cek Saldo di Menu Profil &amp; Beli Produk Lain</span>
                    <span>→</span>
                </a>
            </div>
        @else
            <!-- PROMINENT BIG LOADING CARD JIKA AKUN MASIH MENUNGGU -->
            @if($isWaiting)
            <div id="credential-waiting-card" style="background: linear-gradient(135deg, rgba(249, 115, 22, 0.08) 0%, rgba(245, 158, 11, 0.05) 100%); border: 2px solid rgba(249, 115, 22, 0.35); border-radius: 20px; padding: 32px 24px; text-align: center; margin-bottom: 26px; box-shadow: 0 10px 30px -10px rgba(249, 115, 22, 0.15);">
                <div style="width: 64px; height: 64px; margin: 0 auto 16px auto; border-radius: 50%; background: rgba(249, 115, 22, 0.15); display: flex; align-items: center; justify-content: center; position: relative;">
                    <div style="position: absolute; inset: -5px; border-radius: 50%; border: 3px solid transparent; border-top-color: #f97316; border-right-color: #fb923c; animation: live-spin 1.5s linear infinite;"></div>
                    <span style="font-size: 28px;">⚡</span>
                </div>
                <div style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; background: rgba(249, 115, 22, 0.15); border: 1px solid rgba(249, 115, 22, 0.35); border-radius: 999px; color: #ea580c; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 12px;">
                    <span class="live-dot"></span> Menyiapkan Akun Otomatis
                </div>
                <h3 style="font-size: 20px; font-weight: 900; color: #0f172a; margin-bottom: 8px; line-height: 1.35;">
                    Akun sedang disiapkan, jangan tutup halaman ini.
                </h3>
                <p style="font-size: 15px; font-weight: 900; color: #ea580c; margin-bottom: 20px; max-width: 440px; margin-left: auto; margin-right: auto; line-height: 1.5;">
                    Akun akan muncul secara otomatis!!!
                </p>
                <div style="max-width: 280px; height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin: 0 auto 12px auto; position: relative;">
                    <div style="width: 55%; height: 100%; background: linear-gradient(90deg, #f97316, #fbbf24); border-radius: 999px; animation: live-progress 1.5s ease-in-out infinite alternate;"></div>
                </div>
                <span style="font-size: 11px; font-weight: 600; color: #64748b;">
                    ⚡ Sedang memproses dan menyiapkan lisensi Anda secara otomatis...
                </span>
            </div>
            @endif

            <div class="credential-box" id="credential-container" style="{{ $isWaiting ? 'display: none;' : '' }}">
                <div class="credential-header">
                    <div class="credential-title" id="credential-title">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        <span id="credential-status-text">Kredensial / Detail Akun Anda (Simpan Baik-Baik)</span>
                    </div>
                    <button type="button" id="btn-copy-account" onclick="copyAccountText()" class="btn-copy">
                        📋 Salin Akun
                    </button>
                </div>

                <div class="credential-body" id="credential-text">{{ $rawAccount }}</div>
            </div>

            <!-- CATATAN & GARANSI -->
            <div class="notes">
                <strong>⚠️ Ketentuan Garansi &amp; Penggunaan:</strong><br>
                • Harap segera amankan dan gunakan akun sesuai instruksi.<br>
                • Simpan invoice dan nomor pesanan ini sebagai bukti sah klaim garansi jika terjadi kendala.<br>
                • Layanan bantuan resmi: Hubungi CS {{ $storeName }} di WhatsApp <a href="https://wa.me/{{ $adminWaClean }}" target="_blank" style="color: #ea580c; font-weight: 700; text-decoration: underline;">+62 823-1745-8496</a> atau gabung <a href="{{ $waGroup }}" target="_blank" style="color: #ea580c; font-weight: 700; text-decoration: underline;">Grup Komunitas WhatsApp</a>.
            </div>
        @endif

        <!-- FOOTER -->
        <div class="footer">
            Invoice ini diterbitkan secara otomatis oleh sistem NandarStore dan sah tanpa tanda tangan basah.<br>
            © {{ date('Y') }} NandarStore. Hak Cipta Dilindungi.
        </div>
    </div>

    <script>
        function copyAccountText() {
            var text = document.getElementById('credential-text').innerText;
            if (!text) return;
            navigator.clipboard.writeText(text).then(function() {
                var btn = document.getElementById('btn-copy-account');
                var original = btn.innerText;
                btn.innerText = '✓ Berhasil Disalin!';
                btn.style.background = '#15803d';
                setTimeout(function() {
                    btn.innerText = original;
                    btn.style.background = '';
                }, 2000);
            });
        }

        @if($isWaiting)
        (function() {
            var invoiceNum = "{{ $order->invoice_number }}";
            var pollCount = 0;
            var maxPoll = 50;
            var pollInterval = setInterval(function() {
                pollCount++;
                fetch('/checkout/status/' + invoiceNum)
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data && data.is_ready && data.sn_or_account) {
                            clearInterval(pollInterval);
                            var waitingCard = document.getElementById('credential-waiting-card');
                            var credContainer = document.getElementById('credential-container');
                            var credText = document.getElementById('credential-text');

                            if (waitingCard) waitingCard.style.display = 'none';
                            if (credContainer) credContainer.style.display = 'block';
                            if (credText) credText.innerText = data.sn_or_account;
                        } else if (pollCount >= maxPoll) {
                            clearInterval(pollInterval);
                        }
                    })
                    .catch(function() {});
            }, 3000);
        })();
        @endif
    </script>
</body>
</html>

