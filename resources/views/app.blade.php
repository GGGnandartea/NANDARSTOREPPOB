<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $seoData = \App\Services\SeoService::resolveForRequest(request());
        $seoCfg = $seoData['config'];
        $appStoreName = $seoCfg['store_name'] ?: 'PremiumKita';
        $appStoreLogo = $seoCfg['store_logo'] ?: null;
        $appStoreFavicon = $seoCfg['store_favicon'] ?: ($appStoreLogo ?: null);
        $appLoadingLogo = \App\Models\Setting::where('key', 'loading_logo')->value('value') ?: ($appStoreLogo ?: null);
        $appStoreTagline = $seoCfg['store_tagline'] ?: 'Platform Digital, Akun Premium & Layanan PPOB Otomatis 24 Jam';
        $ogTitle = $seoData['title'];
        $ogDescription = $seoData['description'];
        $ogImage = $seoData['og_image'];
        $currentUrl = $seoData['canonical_url'];
    @endphp
    <title inertia>{{ $ogTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* POPULAR MODERN INITIAL PRELOADER */
        #ns-initial-preloader {
            position: fixed;
            inset: 0;
            z-index: 999999;
            background: radial-gradient(circle at 50% 40%, #171d2d 0%, #090d16 60%, #030712 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #ffffff;
            transition: opacity 0.55s cubic-bezier(0.16, 1, 0.3, 1), transform 0.55s cubic-bezier(0.16, 1, 0.3, 1), filter 0.55s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
            -webkit-user-select: none;
            overflow: hidden;
        }
        #ns-initial-preloader.ns-fade-out {
            opacity: 0;
            transform: scale(1.04);
            filter: blur(6px);
            pointer-events: none;
        }
        .ns-preloader-glow {
            position: absolute;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.28) 0%, rgba(249, 115, 22, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: ns-pulse-glow 3s ease-in-out infinite alternate;
        }
        @keyframes ns-pulse-glow {
            0% { transform: scale(0.85); opacity: 0.6; }
            100% { transform: scale(1.15); opacity: 1; }
        }
        .ns-logo-box {
            position: relative;
            width: 82px;
            height: 82px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
        }
        .ns-logo-ring {
            position: absolute;
            inset: -6px;
            border-radius: 28px;
            border: 2px solid transparent;
            border-top-color: #f97316;
            border-right-color: #fb923c;
            animation: ns-spin 2s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
        }
        @keyframes ns-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .ns-logo-emblem {
            width: 76px;
            height: 76px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: -1px;
            box-shadow: 0 12px 30px rgba(249, 115, 22, 0.45), inset 0 1px 1px rgba(255, 255, 255, 0.4);
            position: relative;
            z-index: 2;
        }
        .ns-brand-title {
            font-size: 26px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #ffffff;
            margin-bottom: 6px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2px;
        }
        .ns-brand-highlight {
            color: #f97316;
            position: relative;
        }
        .ns-brand-tagline {
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 0.5px;
            margin-bottom: 26px;
            text-align: center;
        }
        .ns-bar-track {
            width: 220px;
            height: 5px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 999px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.4);
        }
        .ns-bar-progress {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #f97316 0%, #fbbf24 80%, #ffffff 100%);
            border-radius: 999px;
            box-shadow: 0 0 12px rgba(249, 115, 22, 0.8);
            animation: ns-fill-bar 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes ns-fill-bar {
            0% { width: 4%; }
            40% { width: 55%; }
            80% { width: 88%; }
            100% { width: 100%; }
        }
        .ns-status-ticker {
            margin-top: 14px;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            height: 16px;
            text-align: center;
        }
    </style>
    <!-- SEO & Google Search Console Meta Tags -->
    <meta name="description" content="{{ $seoData['description'] }}">
    <meta name="keywords" content="{{ $seoData['keywords'] }}">
    <meta name="author" content="{{ $appStoreName }}">
    <meta name="publisher" content="{{ $appStoreName }}">
    <meta name="application-name" content="{{ $appStoreName }}">
    <meta name="apple-mobile-web-app-title" content="{{ $appStoreName }}">
    <meta name="theme-color" content="{{ $seoCfg['seo_theme_color'] ?? '#f97316' }}">
    <meta name="robots" content="{{ $seoData['robots'] }}">
    <meta name="googlebot" content="{{ $seoData['robots'] }}">
    <meta name="bingbot" content="{{ $seoData['robots'] }}">
    <meta name="geo.region" content="ID">
    <meta name="geo.country" content="ID">
    <meta http-equiv="content-language" content="id-ID">
    <link rel="canonical" href="{{ $currentUrl }}">
    @if(($seoCfg['seo_sitemap_enabled'] ?? '1') === '1')
    <link rel="sitemap" type="application/xml" title="Sitemap" href="{{ url('/sitemap.xml') }}">
    @endif

    <!-- Webmaster Verification Tags (Google Search Console, Bing, Yandex) -->
    @if(!empty($seoCfg['seo_google_verification']))
    <meta name="google-site-verification" content="{{ $seoCfg['seo_google_verification'] }}">
    @endif
    @if(!empty($seoCfg['seo_bing_verification']))
    <meta name="msvalidate.01" content="{{ $seoCfg['seo_bing_verification'] }}">
    @endif
    @if(!empty($seoCfg['seo_yandex_verification']))
    <meta name="yandex-verification" content="{{ $seoCfg['seo_yandex_verification'] }}">
    @endif

    <!-- Open Graph / WhatsApp / Facebook / Telegram / Google Discover -->
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="{{ $seoData['og_type'] }}">
    <meta property="og:site_name" content="{{ $appStoreName }}">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $seoData['og_description'] }}">
    @if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $ogTitle }}">
    @endif
    @if(!empty($seoData['product']))
    <meta property="product:price:amount" content="{{ (int) $seoData['product']->price }}">
    <meta property="product:price:currency" content="IDR">
    <meta property="product:availability" content="{{ ((int) $seoData['product']->stock > 0) ? 'in stock' : 'out of stock' }}">
    @endif

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $currentUrl }}">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $seoData['og_description'] }}">
    @if($ogImage)
    <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    <!-- Structured Data JSON-LD (Schema.org Rich Snippets & Google Sitelinks) -->
    @if(!empty($seoData['schemas']))
        @foreach($seoData['schemas'] as $schemaItem)
            <script type="application/ld+json">{!! json_encode($schemaItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @endforeach
    @endif

    <!-- Google Analytics 4 (GA4) -->
    @if(!empty($seoCfg['seo_ga4_id']))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($seoCfg['seo_ga4_id']) }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($seoCfg['seo_ga4_id']));
    </script>
    @endif

    <!-- Google Tag Manager (GTM) -->
    @if(!empty($seoCfg['seo_gtm_id']))
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',@json($seoCfg['seo_gtm_id']));
    </script>
    @endif

    @if($appStoreFavicon)
    <link rel="icon" href="{{ $appStoreFavicon }}">
    <link rel="apple-touch-icon" href="{{ $appStoreFavicon }}">
    @endif
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 font-sans antialiased">
    <!-- POPULAR INITIAL WEBSITE PRELOADER -->
    <div id="ns-initial-preloader">
        <div class="ns-preloader-glow"></div>
        <div class="ns-logo-box">
            <div class="ns-logo-ring"></div>
            @if($appLoadingLogo)
                <div class="ns-logo-emblem p-1 overflow-hidden flex items-center justify-center">
                    <img src="{{ $appLoadingLogo }}" alt="{{ $appStoreName }}" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 14px;">
                </div>
            @else
                <div class="ns-logo-emblem">PK</div>
            @endif
        </div>
        <div class="ns-brand-title">
            <span>{{ $appStoreName }}</span>
        </div>
        <div class="ns-brand-tagline">{{ $appStoreTagline }}</div>
        <div class="ns-bar-track">
            <div class="ns-bar-progress"></div>
        </div>
        <div class="ns-status-ticker" id="ns-preloader-status">Memuat Sistem...</div>
    </div>

    @if(!empty($seoCfg['seo_gtm_id']))
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ urlencode($seoCfg['seo_gtm_id']) }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    <noscript>
        <section style="padding:24px;max-width:960px;margin:0 auto;font-family:sans-serif;">
            <h1>{{ $ogTitle }}</h1>
            <h2>{{ $appStoreTagline }}</h2>
            @if(!empty($seoCfg['seo_secondary_tagline']))
            <p><strong>{{ $seoCfg['seo_secondary_tagline'] }}</strong></p>
            @endif
            <p>{{ $ogDescription }}</p>
            <nav aria-label="Navigasi Utama {{ $appStoreName }}">
                <ul>
                    <li><a href="{{ url('/') }}">Katalog Akun Premium - {{ $appStoreName }}</a></li>
                    <li><a href="{{ url('/ppob') }}">Layanan PPOB, Pulsa, Paket Data, Token PLN &amp; Top Up Game</a></li>
                    <li><a href="{{ url('/register') }}">Daftar Member &amp; Reseller VIP {{ $appStoreName }}</a></li>
                    <li><a href="{{ url('/faq') }}">Pusat Bantuan &amp; FAQ {{ $appStoreName }}</a></li>
                    <li><a href="{{ url('/syarat-ketentuan') }}">Syarat &amp; Ketentuan Garansi {{ $appStoreName }}</a></li>
                </ul>
            </nav>
        </section>
    </noscript>

    @inertia

    <script>
        (function() {
            var preloader = document.getElementById('ns-initial-preloader');
            var statusEl = document.getElementById('ns-preloader-status');
            if (!preloader) return;

            var phrases = ['Menghubungkan Server...', 'Memuat Katalog Produk...', 'Menyiapkan Layanan...'];
            var phraseIndex = 0;
            var phraseInterval = setInterval(function() {
                phraseIndex = (phraseIndex + 1) % phrases.length;
                if (statusEl) statusEl.textContent = phrases[phraseIndex];
            }, 300);

            var isDismissed = false;
            function dismissPreloader() {
                if (isDismissed) return;
                isDismissed = true;
                clearInterval(phraseInterval);
                if (statusEl) statusEl.textContent = 'Siap!';
                setTimeout(function() {
                    preloader.classList.add('ns-fade-out');
                    setTimeout(function() {
                        if (preloader && preloader.parentNode) {
                            preloader.parentNode.removeChild(preloader);
                        }
                    }, 600);
                }, 400);
            }

            // Listen to Inertia first page mount or Window load
            document.addEventListener('inertia:finish', function() {
                dismissPreloader();
            }, { once: true });

            if (document.readyState === 'complete') {
                setTimeout(dismissPreloader, 600);
            } else {
                window.addEventListener('load', function() {
                    setTimeout(dismissPreloader, 500);
                });
            }

            // Safety timeout: never block longer than 1.4s under any circumstance
            setTimeout(dismissPreloader, 1400);
        })();
    </script>
</body>
</html>