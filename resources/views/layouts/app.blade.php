<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <title inertia>PremiumKita</title>
    @php
        $siteFavicon = \App\Models\Setting::where('key', 'store_favicon')->value('value') ?: (\App\Models\Setting::where('key', 'store_logo')->value('value') ?: null);
    @endphp
    @if($siteFavicon)
        <link rel="icon" href="{{ $siteFavicon }}">
    @endif
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">
    @inertia
</body>
</html>