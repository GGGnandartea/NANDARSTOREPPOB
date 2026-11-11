<?php

namespace App\Services;

/**
 * Katalog Produk Resmi Kiosbank Webservice V2 (Non-Pulsa: Voucher Game, E-Wallet, Token PLN, Streaming, Voucher)
 * Berdasarkan dokumentasi resmi https://ppob.kiosbank.com:4040/api/webservicesV2/Main/Produk/*
 * dan https://ppob.kiosbank.com:4040/api/webservicesV2/Main/DaftarHarga
 */
class KiosbankOfficialCatalog
{
    /**
     * Daftar kode placeholder lama (non-Kiosbank) yang harus dibersihkan dan diganti
     * dengan kode produk resmi 6 karakter Kiosbank Webservice V2.
     */
    public const OBSOLETE_PLACEHOLDER_CODES = [
        // Placeholder Game lama
        'FF50', 'FF70', 'FF140', 'FF355', 'FF720', 'FF1450', 'FFWEEK',
        'GI60', 'GI330', 'GI1090', 'GIWELK',
        // Placeholder E-Wallet lama
        'DANA10', 'DANA20', 'DANA25', 'DANA50', 'DANA100',
        'GOPAY10', 'GOPAY20', 'GOPAY50', 'GOPAY100',
        'OVO10', 'OVO20', 'OVO50', 'OVO100',
        'SPAY10', 'SPAY20', 'SPAY50', 'SPAY100',
        // Placeholder PLN lama
        'PLN20', 'PLN50', 'PLN100', 'PLN200', 'PLN500', 'PLN1000',
        // Placeholder Streaming lama
        'NETFLIX1M', 'NETFLIXBASIC', 'NETFLIXSTD', 'NETFLIXPREM',
        'DISNEY1M', 'DISNEYPREM1M', 'DISNEY1Y',
        'VIDIOPLAT30', 'VIDIODMD30', 'VIDIOPLAT1Y',
        'PRIME1M', 'PRIME3M',
        'HBOGO1M', 'HBOGO3M',
        'VIU30D', 'VIU90D', 'VIU365D',
        'WETV30D', 'WETV90D', 'WETV1Y',
        'BSTATION30D', 'BSTATION90D', 'BSTATION365D',
        'VISION30D', 'VISIONSP30D',
        'CP1M', 'BEIN1M',
        'ITUNES100', 'ITUNES150', 'ITUNES250',
        'TINDERPLUS1M', 'TINDERGOLD1M',
        'BIGO100', 'BIGO500',
        'JOOXVIP30', 'IFLIXVIP30', 'MOLA30', 'GENFLIX30', 'SUSHIROLL30',
        'YALLAGOLD1K', 'MICOLIVE100', 'LIVU500', 'LITA100', 'LITA500',
        'LITMATCH100', 'LINKPLUS50',
    ];

    /**
     * Pemetaan prefixID resmi Kiosbank (/Services/getHargaByProductID)
     * ke kategori dan operator internal.
     */
    public const PREFIX_METADATA = [
        // VOUCHER GAME (AAO.*)
        'AAO.AAAA' => ['category' => 'game', 'operator' => 'Arena of Valor'],
        'AAO.AAAB' => ['category' => 'game', 'operator' => 'AU2 Mobile'],
        'AAO.AAAC' => ['category' => 'game', 'operator' => 'Boyaa Capsa Susun'],
        'AAO.AAAD' => ['category' => 'game', 'operator' => 'Call of Duty Mobile'],
        'AAO.AAAE' => ['category' => 'game', 'operator' => 'EA Sports FC Mobile'],
        'AAO.AAAF' => ['category' => 'game', 'operator' => 'Free Fire'],
        'AAO.AAAG' => ['category' => 'game', 'operator' => 'Genshin Impact'],
        'AAO.AAAH' => ['category' => 'game', 'operator' => 'Google Play'],
        'AAO.AAAI' => ['category' => 'game', 'operator' => 'Honkai Star Rail'],
        'AAO.AAAJ' => ['category' => 'game', 'operator' => 'Honor of Kings'],
        'AAO.AAAK' => ['category' => 'game', 'operator' => 'IndoPlay'],
        'AAO.AAAL' => ['category' => 'game', 'operator' => 'Mobile Legends'],
        'AAO.AAAM' => ['category' => 'game', 'operator' => 'Megaxus'],
        'AAO.AAAN' => ['category' => 'game', 'operator' => 'Minecoins'],
        'AAO.AAAO' => ['category' => 'game', 'operator' => 'Nintendo eShop'],
        'AAO.AAAP' => ['category' => 'game', 'operator' => 'Point Blank'],
        'AAO.AAAQ' => ['category' => 'game', 'operator' => 'PlayStation Store'],
        'AAO.AAAR' => ['category' => 'game', 'operator' => 'PUBG PC'],
        'AAO.AAAS' => ['category' => 'game', 'operator' => 'PUBG Mobile'],
        'AAO.AAAT' => ['category' => 'game', 'operator' => 'Razer Gold'],
        'AAO.AAAU' => ['category' => 'game', 'operator' => 'Roblox'],
        'AAO.AAAV' => ['category' => 'game', 'operator' => 'Steam Wallet'],
        'AAO.AAAW' => ['category' => 'game', 'operator' => 'Unipin Voucher'],
        'AAO.AAAX' => ['category' => 'game', 'operator' => 'Valorant'],
        'AAO.AAAY' => ['category' => 'game', 'operator' => 'Wave Game'],
        'AAO.AAAZ' => ['category' => 'game', 'operator' => 'Zepetto'],
        'AAO.AABA' => ['category' => 'game', 'operator' => 'Garena Shell'],

        // STREAMING (AAB.*)
        'AAB.AAAA' => ['category' => 'streaming', 'operator' => 'Spotify'],
        'AAB.AAAB' => ['category' => 'streaming', 'operator' => 'beIN Sports'],
        'AAB.AAAC' => ['category' => 'streaming', 'operator' => 'Catchplay+'],
        'AAB.AAAD' => ['category' => 'streaming', 'operator' => 'iTunes'],
        'AAB.AAAE' => ['category' => 'streaming', 'operator' => 'Vidio'],
        'AAB.AAAG' => ['category' => 'streaming', 'operator' => 'Vision+'],
        'AAB.AAAH' => ['category' => 'streaming', 'operator' => 'Genflix'],
        'AAB.AAAI' => ['category' => 'streaming', 'operator' => 'Sushiroll'],
        'AAB.AAAJ' => ['category' => 'streaming', 'operator' => 'Bigo Live'],
        'AAB.AAAK' => ['category' => 'streaming', 'operator' => 'Yalla Live'],
        'AAB.AAAL' => ['category' => 'streaming', 'operator' => 'Mico Live'],
        'AAB.AAAM' => ['category' => 'streaming', 'operator' => 'LivU'],
        'AAB.AAAN' => ['category' => 'streaming', 'operator' => 'Lita'],
        'AAB.AAAO' => ['category' => 'streaming', 'operator' => 'Litmatch'],
        'AAB.AAAP' => ['category' => 'streaming', 'operator' => 'Bstation'],
        'AAB.AAAQ' => ['category' => 'streaming', 'operator' => 'Tinder'],
        'AAB.AAAR' => ['category' => 'streaming', 'operator' => 'WeTV'],
        'AAB.AAAS' => ['category' => 'streaming', 'operator' => 'Netflix'],
        'AAB.AAAT' => ['category' => 'streaming', 'operator' => 'Disney+'],
        'AAB.AAAU' => ['category' => 'streaming', 'operator' => 'HBO GO'],
        'AAB.AAAV' => ['category' => 'streaming', 'operator' => 'LinkPlus'],
        'AAB.AAAW' => ['category' => 'streaming', 'operator' => 'Prime Video'],
        'AAB.AAAX' => ['category' => 'streaming', 'operator' => 'Mola TV'],
        'AAB.AAAY' => ['category' => 'streaming', 'operator' => 'Joox'],
        'AAB.AAAZ' => ['category' => 'streaming', 'operator' => 'iFlix'],
        'AAB.AABA' => ['category' => 'streaming', 'operator' => 'Viu'],
    ];

    /**
     * Cek apakah suatu prefix dapat disinkronkan melalui endpoint /Services/getHargaByProductID Kiosbank.
     */
    public static function isPriceSyncablePrefix(string $prefixId): bool
    {
        if (isset(KiosbankService::PULSA_PREFIX_MAP[$prefixId])) {
            return true;
        }
        return isset(self::PREFIX_METADATA[$prefixId])
            || preg_match('/^(AAE|AAO|AAB|AAH|AAN)\.[A-Z]{4}$/', $prefixId) === 1;
    }

    /**
     * Resolusi metadata (category, operator, prefix_id) berdasarkan kode produk atau prefixID Kiosbank.
     */
    public static function metadata(string $code, string $name, ?string $prefixId = null): ?array
    {
        $pulsaMeta = KiosbankPulsaCatalog::metadata($code, $name);
        if ($pulsaMeta !== null) {
            return $pulsaMeta;
        }

        if ($prefixId !== null && isset(self::PREFIX_METADATA[$prefixId])) {
            return array_merge(self::PREFIX_METADATA[$prefixId], ['prefix_id' => $prefixId]);
        }

        // Deteksi berdasarkan suffix kode produk Voucher Game Kiosbank
        $gameSuffixMap = [
            'BK' => ['operator' => 'Free Fire',           'prefix_id' => 'AAO.AAAF'],
            'BE' => ['operator' => 'Mobile Legends',      'prefix_id' => 'AAO.AAAL'],
            'BR' => ['operator' => 'Honor of Kings',      'prefix_id' => 'AAO.AAAJ'],
            'BO' => ['operator' => 'Genshin Impact',      'prefix_id' => 'AAO.AAAG'],
            'BL' => ['operator' => 'PUBG Mobile',         'prefix_id' => 'AAO.AAAS'],
            'BQ' => ['operator' => 'Valorant',            'prefix_id' => 'AAO.AAAX'],
            'BX' => ['operator' => 'Roblox',              'prefix_id' => 'AAO.AAAU'],
            'BM' => ['operator' => 'Google Play',         'prefix_id' => 'AAO.AAAH'],
            'BD' => ['operator' => 'Unipin Voucher',      'prefix_id' => 'AAO.AAAW'],
            'BS' => ['operator' => 'Honkai Star Rail',    'prefix_id' => 'AAO.AAAI'],
            'CM' => ['operator' => 'Call of Duty Mobile', 'prefix_id' => 'AAO.AAAD'],
            'BP' => ['operator' => 'EA Sports FC Mobile', 'prefix_id' => 'AAO.AAAE'],
            'BN' => ['operator' => 'Point Blank',         'prefix_id' => 'AAO.AAAP'],
            'AX' => ['operator' => 'Steam Wallet',        'prefix_id' => 'AAO.AAAV'],
            'AH' => ['operator' => 'Garena Shell',        'prefix_id' => 'AAO.AABA'],
            'AO' => ['operator' => 'Razer Gold',          'prefix_id' => 'AAO.AAAT'],
        ];

        $suffix2 = strtoupper(substr($code, -2));
        if (str_starts_with($code, '6') && isset($gameSuffixMap[$suffix2])) {
            return array_merge(['category' => 'game'], $gameSuffixMap[$suffix2]);
        }

        // Deteksi E-Wallet (751*** - 756***)
        $ewalletPrefixMap = [
            '751' => ['operator' => 'OVO',             'prefix_id' => 'EWAL.OVO'],
            '752' => ['operator' => 'GoPay',           'prefix_id' => 'EWAL.GOPAY'],
            '753' => ['operator' => 'Mandiri E-Money', 'prefix_id' => 'EWAL.EMONEY'],
            '754' => ['operator' => 'DANA',            'prefix_id' => 'EWAL.DANA'],
            '755' => ['operator' => 'LinkAja',         'prefix_id' => 'EWAL.LINKAJA'],
            '756' => ['operator' => 'ShopeePay',       'prefix_id' => 'EWAL.SHOPEE'],
        ];
        $prefix3 = substr($code, 0, 3);
        if (isset($ewalletPrefixMap[$prefix3])) {
            return array_merge(['category' => 'ewallet'], $ewalletPrefixMap[$prefix3]);
        }

        // Deteksi PLN Prepaid (1003*2)
        if (preg_match('/^1003[0-9A-Z]2$/', $code)) {
            return ['category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302'];
        }

        return null;
    }

    /**
     * Seluruh produk resmi Non-Pulsa Kiosbank Webservice V2.
     */
    public static function products(): array
    {
        return [
            // =========================================================================
            // 1. LISTRIK PLN PREPAID / TOKEN LISTRIK (Prefix: 100302)
            // Sumber: https://ppob.kiosbank.com:4040/api/webservicesV2/Main/Produk/LISTRIK
            // =========================================================================
            ['code' => '100392', 'name' => 'Token Listrik PLN 5.000',     'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 5500,    'margin' => 1500],
            ['code' => '1003A2', 'name' => 'Token Listrik PLN 10.000',    'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 10500,   'margin' => 1500],
            ['code' => '1003B2', 'name' => 'Token Listrik PLN 15.000',    'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 15500,   'margin' => 1500],
            ['code' => '100312', 'name' => 'Token Listrik PLN 20.000',    'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 20500,   'margin' => 1500],
            ['code' => '1003C2', 'name' => 'Token Listrik PLN 25.000',    'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 25500,   'margin' => 1500],
            ['code' => '100322', 'name' => 'Token Listrik PLN 50.000',    'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 50500,   'margin' => 2000],
            ['code' => '100332', 'name' => 'Token Listrik PLN 100.000',   'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 100500,  'margin' => 2500],
            ['code' => '100342', 'name' => 'Token Listrik PLN 200.000',   'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 200500,  'margin' => 3500],
            ['code' => '100352', 'name' => 'Token Listrik PLN 500.000',   'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 500500,  'margin' => 5000],
            ['code' => '100362', 'name' => 'Token Listrik PLN 1.000.000', 'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 1000500, 'margin' => 7500],

            // =========================================================================
            // 2. VOUCHER GAME — FREE FIRE (Prefix: AAO.AAAF)
            // Sumber: Main/Produk/VOUCHER GAME & Main/Nopel/VOUCHER GAME (6701BK = 5 Diamond)
            // =========================================================================
            ['code' => '6701BK', 'name' => 'Free Fire 5 Diamond',            'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 900,    'margin' => 600],
            ['code' => '6712BK', 'name' => 'Free Fire 10 Diamond',           'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 1750,   'margin' => 650],
            ['code' => '6702BK', 'name' => 'Free Fire 12 Diamond',           'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 1950,   'margin' => 650],
            ['code' => '6714BK', 'name' => 'Free Fire 20 Diamond',           'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 3200,   'margin' => 700],
            ['code' => '6715BK', 'name' => 'Free Fire 25 Diamond',           'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 3950,   'margin' => 750],
            ['code' => '6717BK', 'name' => 'Free Fire 30 Diamond',           'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 4500,   'margin' => 700],
            ['code' => '6703BK', 'name' => 'Free Fire 50 Diamond',           'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 7100,   'margin' => 700],
            ['code' => '6704BK', 'name' => 'Free Fire 70 Diamond',           'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 9700,   'margin' => 800],
            ['code' => '6721BK', 'name' => 'Free Fire 100 Diamond',          'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 13900,  'margin' => 900],
            ['code' => '6705BK', 'name' => 'Free Fire 140 Diamond',          'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 19200,  'margin' => 1000],
            ['code' => '6730BK', 'name' => 'Free Fire 210 Diamond',          'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 28800,  'margin' => 1200],
            ['code' => '6706BK', 'name' => 'Free Fire 355 Diamond',          'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 47500,  'margin' => 2000],
            ['code' => '6745BK', 'name' => 'Free Fire 500 Diamond',          'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 67500,  'margin' => 2500],
            ['code' => '6707BK', 'name' => 'Free Fire 720 Diamond',          'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 94500,  'margin' => 3500],
            ['code' => '6755BK', 'name' => 'Free Fire 1000 Diamond',         'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 132000, 'margin' => 4000],
            ['code' => '6708BK', 'name' => 'Free Fire 1450 Diamond',         'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 189000, 'margin' => 5000],
            ['code' => '6709BK', 'name' => 'Free Fire 2180 Diamond',         'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 285000, 'margin' => 6000],
            ['code' => '6710BK', 'name' => 'Free Fire 3640 Diamond',         'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 475000, 'margin' => 8000],
            ['code' => '6711BK', 'name' => 'Free Fire 7290 Diamond',         'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 945000, 'margin' => 12000],
            ['code' => '6806BK', 'name' => 'Free Fire Membership Mingguan',  'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 29000,  'margin' => 1500],
            ['code' => '6807BK', 'name' => 'Free Fire Membership Bulanan',   'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 87000,  'margin' => 3000],
            ['code' => '6808BK', 'name' => 'Free Fire Level Up Pass',        'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 15000,  'margin' => 1000],
            ['code' => '6809BK', 'name' => 'Free Fire BP Card',              'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 43000,  'margin' => 2000],

            // =========================================================================
            // 3. VOUCHER GAME — MOBILE LEGENDS (Prefix: AAO.AAAL)
            // Sumber: Main/Produk/VOUCHER GAME & Main/Nopel/VOUCHER GAME (6707BE = 170 Diamonds)
            // =========================================================================
            ['code' => '6805BE', 'name' => 'Mobile Legends 3 Diamonds',          'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 1100,   'margin' => 500],
            ['code' => '6726BE', 'name' => 'Mobile Legends 5 Diamonds',          'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 1500,   'margin' => 500],
            ['code' => '6759BE', 'name' => 'Mobile Legends 10 Diamonds',         'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 2900,   'margin' => 600],
            ['code' => '6722BE', 'name' => 'Mobile Legends 12 Diamonds',         'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 3400,   'margin' => 600],
            ['code' => '6729BE', 'name' => 'Mobile Legends 19 Diamonds',         'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 5300,   'margin' => 700],
            ['code' => '6723BE', 'name' => 'Mobile Legends 28 Diamonds',         'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 7800,   'margin' => 800],
            ['code' => '6701BE', 'name' => 'Mobile Legends 30 Diamonds',         'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 8400,   'margin' => 800],
            ['code' => '6702BE', 'name' => 'Mobile Legends 36 Diamonds',         'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 10100,  'margin' => 900],
            ['code' => '6730BE', 'name' => 'Mobile Legends 44 Diamonds',         'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 12200,  'margin' => 1000],
            ['code' => '6703BE', 'name' => 'Mobile Legends 45 Diamonds',         'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 12500,  'margin' => 1000],
            ['code' => '6704BE', 'name' => 'Mobile Legends 59 Diamonds',         'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 16200,  'margin' => 1100],
            ['code' => '6706BE', 'name' => 'Mobile Legends 74 Diamonds',         'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 20200,  'margin' => 1200],
            ['code' => '6734BE', 'name' => 'Mobile Legends 86 Diamonds',         'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 22800,  'margin' => 1200],
            ['code' => '6735BE', 'name' => 'Mobile Legends 112 Diamonds',        'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 30200,  'margin' => 1500],
            ['code' => '6707BE', 'name' => 'Mobile Legends 170 Diamonds',        'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 45000,  'margin' => 2000],
            ['code' => '6737BE', 'name' => 'Mobile Legends 172 Diamonds',        'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 45500,  'margin' => 2000],
            ['code' => '6738BE', 'name' => 'Mobile Legends 257 Diamonds',        'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 68000,  'margin' => 2500],
            ['code' => '6741BE', 'name' => 'Mobile Legends 344 Diamonds',        'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 91000,  'margin' => 3000],
            ['code' => '6708BE', 'name' => 'Mobile Legends 370 Diamonds',        'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 98000,  'margin' => 3500],
            ['code' => '6743BE', 'name' => 'Mobile Legends 429 Diamonds',        'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 113500, 'margin' => 3500],
            ['code' => '6744BE', 'name' => 'Mobile Legends 514 Diamonds',        'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 136000, 'margin' => 4000],
            ['code' => '6709BE', 'name' => 'Mobile Legends 702 Diamonds',        'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 178000, 'margin' => 5000],
            ['code' => '6747BE', 'name' => 'Mobile Legends 706 Diamonds',        'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 179000, 'margin' => 5000],
            ['code' => '6710BE', 'name' => 'Mobile Legends 875 Diamonds',        'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 222000, 'margin' => 6000],
            ['code' => '6711BE', 'name' => 'Mobile Legends 1159 Diamonds',       'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 295000, 'margin' => 7000],
            ['code' => '6713BE', 'name' => 'Mobile Legends 2010 Diamonds',       'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 485000, 'margin' => 10000],
            ['code' => '6803BE', 'name' => 'Mobile Legends Weekly Diamond Pass', 'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 27500,  'margin' => 1500],
            ['code' => '6720BE', 'name' => 'Mobile Legends Starlight Member Plus', 'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 145000, 'margin' => 4000],
            ['code' => '6721BE', 'name' => 'Mobile Legends Twilight Pass',       'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 145000, 'margin' => 4000],

            // =========================================================================
            // 4. VOUCHER GAME — HONOR OF KINGS (Prefix: AAO.AAAJ)
            // =========================================================================
            ['code' => '6001BR', 'name' => 'Honor of Kings 8 Tokens',          'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 1644,   'margin' => 556],
            ['code' => '6002BR', 'name' => 'Honor of Kings 16 + 1 Tokens',     'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 2825,   'margin' => 575],
            ['code' => '6003BR', 'name' => 'Honor of Kings 23 + 2 Tokens',     'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 3834,   'margin' => 666],
            ['code' => '6004BR', 'name' => 'Honor of Kings 80 + 8 Tokens',     'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 12842,  'margin' => 1058],
            ['code' => '6005BR', 'name' => 'Honor of Kings Weekly Card',       'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 12174,  'margin' => 1026],
            ['code' => '6006BR', 'name' => 'Honor of Kings 240 + 17 Tokens',   'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 44561,  'margin' => 1939],
            ['code' => '6007BR', 'name' => 'Honor of Kings Weekly Card Plus',  'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 40251,  'margin' => 1949],
            ['code' => '6008BR', 'name' => 'Honor of Kings 400 + 32 Tokens',   'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 68112,  'margin' => 2488],
            ['code' => '6009BR', 'name' => 'Honor of Kings 560 + 45 Tokens',   'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 103747, 'margin' => 3453],
            ['code' => '6010BR', 'name' => 'Honor of Kings 800 + 95 Tokens',   'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 119625, 'margin' => 3975],
            ['code' => '6011BR', 'name' => 'Honor of Kings 1200 + 153 Tokens', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 179500, 'margin' => 5500],
            ['code' => '6012BR', 'name' => 'Honor of Kings 2400 + 324 Tokens', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 359000, 'margin' => 8000],
            ['code' => '6013BR', 'name' => 'Honor of Kings 4000 + 580 Tokens', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 598000, 'margin' => 12000],

            // =========================================================================
            // 5. VOUCHER GAME — GENSHIN IMPACT (Prefix: AAO.AAAG)
            // =========================================================================
            ['code' => '6001BO', 'name' => 'Genshin Impact 60 Genesis Crystals',       'category' => 'game', 'operator' => 'Genshin Impact', 'prefix_id' => 'AAO.AAAG', 'base_price' => 14800,  'margin' => 1200],
            ['code' => '6002BO', 'name' => 'Genshin Impact 300+30 Genesis Crystals',   'category' => 'game', 'operator' => 'Genshin Impact', 'prefix_id' => 'AAO.AAAG', 'base_price' => 76000,  'margin' => 2500],
            ['code' => '6003BO', 'name' => 'Genshin Impact 980+110 Genesis Crystals',  'category' => 'game', 'operator' => 'Genshin Impact', 'prefix_id' => 'AAO.AAAG', 'base_price' => 232000, 'margin' => 5000],
            ['code' => '6004BO', 'name' => 'Genshin Impact 1980+260 Genesis Crystals', 'category' => 'game', 'operator' => 'Genshin Impact', 'prefix_id' => 'AAO.AAAG', 'base_price' => 450000, 'margin' => 8000],
            ['code' => '6005BO', 'name' => 'Genshin Impact 3280+600 Genesis Crystals', 'category' => 'game', 'operator' => 'Genshin Impact', 'prefix_id' => 'AAO.AAAG', 'base_price' => 760000, 'margin' => 12000],

            // =========================================================================
            // 6. VOUCHER GAME — PUBG MOBILE (Prefix: AAO.AAAS)
            // =========================================================================
            ['code' => '6707BL', 'name' => 'PUBG Mobile 35 UC',              'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 7200,   'margin' => 800],
            ['code' => '6701BL', 'name' => 'PUBG Mobile 50 UC',              'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 10200,  'margin' => 900],
            ['code' => '6708BL', 'name' => 'PUBG Mobile 60 UC',              'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 13500,  'margin' => 1000],
            ['code' => '6769BL', 'name' => 'PUBG Mobile 100 UC',             'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 22500,  'margin' => 1200],
            ['code' => '6716BL', 'name' => 'PUBG Mobile 150 UC',             'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 33000,  'margin' => 1500],
            ['code' => '6702BL', 'name' => 'PUBG Mobile 250 UC',             'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 52000,  'margin' => 2000],
            ['code' => '6725BL', 'name' => 'PUBG Mobile 325 UC',             'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 67500,  'margin' => 2500],
            ['code' => '6703BL', 'name' => 'PUBG Mobile 500 UC',             'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 102000, 'margin' => 3500],
            ['code' => '6772BL', 'name' => 'PUBG Mobile 660 UC',             'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 135000, 'margin' => 4000],
            ['code' => '6704BL', 'name' => 'PUBG Mobile 1000 UC',            'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 204000, 'margin' => 5000],
            ['code' => '6743BL', 'name' => 'PUBG Mobile 1800 UC',            'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 338000, 'margin' => 7000],
            ['code' => '6705BL', 'name' => 'PUBG Mobile 2500 UC',            'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 495000, 'margin' => 10000],
            ['code' => '6777BL', 'name' => 'PUBG Mobile Royale Pass',        'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 148000, 'margin' => 4000],
            ['code' => '6778BL', 'name' => 'PUBG Mobile Elite Pass Plus',    'category' => 'game', 'operator' => 'PUBG Mobile', 'prefix_id' => 'AAO.AAAS', 'base_price' => 375000, 'margin' => 7500],

            // =========================================================================
            // 7. VOUCHER GAME — VALORANT (Prefix: AAO.AAAX)
            // =========================================================================
            ['code' => '6001BQ', 'name' => 'Valorant 475 Points',  'category' => 'game', 'operator' => 'Valorant', 'prefix_id' => 'AAO.AAAX', 'base_price' => 52000,  'margin' => 2000],
            ['code' => '6002BQ', 'name' => 'Valorant 1000 Points', 'category' => 'game', 'operator' => 'Valorant', 'prefix_id' => 'AAO.AAAX', 'base_price' => 105000, 'margin' => 3500],
            ['code' => '6003BQ', 'name' => 'Valorant 2050 Points', 'category' => 'game', 'operator' => 'Valorant', 'prefix_id' => 'AAO.AAAX', 'base_price' => 210000, 'margin' => 5000],
            ['code' => '6004BQ', 'name' => 'Valorant 3650 Points', 'category' => 'game', 'operator' => 'Valorant', 'prefix_id' => 'AAO.AAAX', 'base_price' => 360000, 'margin' => 8000],

            // =========================================================================
            // 8. VOUCHER GAME — ROBLOX (Prefix: AAO.AAAU)
            // =========================================================================
            ['code' => '6001BX', 'name' => 'Roblox Gift Card IDR 50.000',  'category' => 'game', 'operator' => 'Roblox', 'prefix_id' => 'AAO.AAAU', 'base_price' => 50500,  'margin' => 2000],
            ['code' => '6002BX', 'name' => 'Roblox Gift Card IDR 65.000',  'category' => 'game', 'operator' => 'Roblox', 'prefix_id' => 'AAO.AAAU', 'base_price' => 65500,  'margin' => 2500],
            ['code' => '6003BX', 'name' => 'Roblox Gift Card IDR 100.000', 'category' => 'game', 'operator' => 'Roblox', 'prefix_id' => 'AAO.AAAU', 'base_price' => 100500, 'margin' => 3000],
            ['code' => '6005BX', 'name' => 'Roblox Gift Card IDR 200.000', 'category' => 'game', 'operator' => 'Roblox', 'prefix_id' => 'AAO.AAAU', 'base_price' => 200500, 'margin' => 5000],
            ['code' => '6007BX', 'name' => 'Roblox Gift Card IDR 500.000', 'category' => 'game', 'operator' => 'Roblox', 'prefix_id' => 'AAO.AAAU', 'base_price' => 500500, 'margin' => 8000],
            ['code' => '6013BX', 'name' => 'Roblox 800 Robux',             'category' => 'game', 'operator' => 'Roblox', 'prefix_id' => 'AAO.AAAU', 'base_price' => 155000, 'margin' => 4500],
            ['code' => '6014BX', 'name' => 'Roblox 2000 Robux',            'category' => 'game', 'operator' => 'Roblox', 'prefix_id' => 'AAO.AAAU', 'base_price' => 385000, 'margin' => 7500],
            ['code' => '6015BX', 'name' => 'Roblox 4500 Robux',            'category' => 'game', 'operator' => 'Roblox', 'prefix_id' => 'AAO.AAAU', 'base_price' => 760000, 'margin' => 12000],

            // =========================================================================
            // 9. VOUCHER GAME — GOOGLE PLAY (Prefix: AAO.AAAH)
            // Sumber: Main/Produk/VOUCHER GAME & Main/Nopel/VOUCHER GAME (6701BM = 20.000)
            // =========================================================================
            ['code' => '6712BM', 'name' => 'Kode Voucher Google Play 5.000',   'category' => 'game', 'operator' => 'Google Play', 'prefix_id' => 'AAO.AAAH', 'base_price' => 5600,   'margin' => 900],
            ['code' => '6713BM', 'name' => 'Kode Voucher Google Play 10.000',  'category' => 'game', 'operator' => 'Google Play', 'prefix_id' => 'AAO.AAAH', 'base_price' => 11000,  'margin' => 1000],
            ['code' => '6701BM', 'name' => 'Kode Voucher Google Play 20.000',  'category' => 'game', 'operator' => 'Google Play', 'prefix_id' => 'AAO.AAAH', 'base_price' => 21800,  'margin' => 1200],
            ['code' => '6702BM', 'name' => 'Kode Voucher Google Play 50.000',  'category' => 'game', 'operator' => 'Google Play', 'prefix_id' => 'AAO.AAAH', 'base_price' => 54000,  'margin' => 2000],
            ['code' => '6703BM', 'name' => 'Kode Voucher Google Play 100.000', 'category' => 'game', 'operator' => 'Google Play', 'prefix_id' => 'AAO.AAAH', 'base_price' => 108000, 'margin' => 3000],
            ['code' => '6704BM', 'name' => 'Kode Voucher Google Play 150.000', 'category' => 'game', 'operator' => 'Google Play', 'prefix_id' => 'AAO.AAAH', 'base_price' => 162000, 'margin' => 4000],
            ['code' => '6705BM', 'name' => 'Kode Voucher Google Play 300.000', 'category' => 'game', 'operator' => 'Google Play', 'prefix_id' => 'AAO.AAAH', 'base_price' => 322000, 'margin' => 6000],
            ['code' => '6706BM', 'name' => 'Kode Voucher Google Play 500.000', 'category' => 'game', 'operator' => 'Google Play', 'prefix_id' => 'AAO.AAAH', 'base_price' => 535000, 'margin' => 9000],

            // =========================================================================
            // 10. VOUCHER GAME — UNIPIN VOUCHER (Prefix: AAO.AAAW)
            // Sumber: Main/Produk/VOUCHER GAME & Main/Nopel/VOUCHER GAME (6010BD, 6050BD)
            // =========================================================================
            ['code' => '6010BD', 'name' => 'Unipin Voucher 10.000',  'category' => 'game', 'operator' => 'Unipin Voucher', 'prefix_id' => 'AAO.AAAW', 'base_price' => 10200,  'margin' => 1000],
            ['code' => '6020BD', 'name' => 'Unipin Voucher 20.000',  'category' => 'game', 'operator' => 'Unipin Voucher', 'prefix_id' => 'AAO.AAAW', 'base_price' => 20300,  'margin' => 1200],
            ['code' => '6050BD', 'name' => 'Unipin Voucher 50.000',  'category' => 'game', 'operator' => 'Unipin Voucher', 'prefix_id' => 'AAO.AAAW', 'base_price' => 50500,  'margin' => 2000],
            ['code' => '6100BD', 'name' => 'Unipin Voucher 100.000', 'category' => 'game', 'operator' => 'Unipin Voucher', 'prefix_id' => 'AAO.AAAW', 'base_price' => 100800, 'margin' => 3000],
            ['code' => '6300BD', 'name' => 'Unipin Voucher 300.000', 'category' => 'game', 'operator' => 'Unipin Voucher', 'prefix_id' => 'AAO.AAAW', 'base_price' => 301500, 'margin' => 5500],
            ['code' => '6500BD', 'name' => 'Unipin Voucher 500.000', 'category' => 'game', 'operator' => 'Unipin Voucher', 'prefix_id' => 'AAO.AAAW', 'base_price' => 502000, 'margin' => 8000],

            // =========================================================================
            // 11. VOUCHER GAME — HONKAI STAR RAIL (Prefix: AAO.AAAI)
            // =========================================================================
            ['code' => '6001BS', 'name' => 'Honkai Star Rail Express Supply Pass', 'category' => 'game', 'operator' => 'Honkai Star Rail', 'prefix_id' => 'AAO.AAAI', 'base_price' => 75000,  'margin' => 2500],
            ['code' => '6002BS', 'name' => 'Honkai Star Rail 60 Oneiric Shard',    'category' => 'game', 'operator' => 'Honkai Star Rail', 'prefix_id' => 'AAO.AAAI', 'base_price' => 15000,  'margin' => 1000],
            ['code' => '6003BS', 'name' => 'Honkai Star Rail 330 Oneiric Shard',   'category' => 'game', 'operator' => 'Honkai Star Rail', 'prefix_id' => 'AAO.AAAI', 'base_price' => 76000,  'margin' => 2500],
            ['code' => '6004BS', 'name' => 'Honkai Star Rail 1090 Oneiric Shard',  'category' => 'game', 'operator' => 'Honkai Star Rail', 'prefix_id' => 'AAO.AAAI', 'base_price' => 232000, 'margin' => 5000],
            ['code' => '6005BS', 'name' => 'Honkai Star Rail 2240 Oneiric Shard',  'category' => 'game', 'operator' => 'Honkai Star Rail', 'prefix_id' => 'AAO.AAAI', 'base_price' => 450000, 'margin' => 8000],
            ['code' => '6006BS', 'name' => 'Honkai Star Rail 3880 Oneiric Shard',  'category' => 'game', 'operator' => 'Honkai Star Rail', 'prefix_id' => 'AAO.AAAI', 'base_price' => 760000, 'margin' => 12000],

            // =========================================================================
            // 12. VOUCHER GAME — CALL OF DUTY MOBILE (Prefix: AAO.AAAD)
            // =========================================================================
            ['code' => '6001CM', 'name' => 'Call of Duty Mobile 31 CP',  'category' => 'game', 'operator' => 'Call of Duty Mobile', 'prefix_id' => 'AAO.AAAD', 'base_price' => 4800,  'margin' => 700],
            ['code' => '6002CM', 'name' => 'Call of Duty Mobile 62 CP',  'category' => 'game', 'operator' => 'Call of Duty Mobile', 'prefix_id' => 'AAO.AAAD', 'base_price' => 9500,  'margin' => 900],
            ['code' => '6003CM', 'name' => 'Call of Duty Mobile 127 CP', 'category' => 'game', 'operator' => 'Call of Duty Mobile', 'prefix_id' => 'AAO.AAAD', 'base_price' => 19000, 'margin' => 1200],
            ['code' => '6004CM', 'name' => 'Call of Duty Mobile 320 CP', 'category' => 'game', 'operator' => 'Call of Duty Mobile', 'prefix_id' => 'AAO.AAAD', 'base_price' => 47500, 'margin' => 2000],
            ['code' => '6005CM', 'name' => 'Call of Duty Mobile 645 CP', 'category' => 'game', 'operator' => 'Call of Duty Mobile', 'prefix_id' => 'AAO.AAAD', 'base_price' => 95000, 'margin' => 3500],
            ['code' => '6006CM', 'name' => 'Call of Duty Mobile 800 CP', 'category' => 'game', 'operator' => 'Call of Duty Mobile', 'prefix_id' => 'AAO.AAAD', 'base_price' => 118000,'margin' => 4000],

            // =========================================================================
            // 13. VOUCHER GAME — EA SPORTS FC MOBILE (Prefix: AAO.AAAE)
            // =========================================================================
            ['code' => '6001BP', 'name' => 'EA Sports FC Mobile 40 FC Points',   'category' => 'game', 'operator' => 'EA Sports FC Mobile', 'prefix_id' => 'AAO.AAAE', 'base_price' => 6500,   'margin' => 800],
            ['code' => '6002BP', 'name' => 'EA Sports FC Mobile 100 FC Points',  'category' => 'game', 'operator' => 'EA Sports FC Mobile', 'prefix_id' => 'AAO.AAAE', 'base_price' => 15500,  'margin' => 1000],
            ['code' => '6003BP', 'name' => 'EA Sports FC Mobile 520 FC Points',  'category' => 'game', 'operator' => 'EA Sports FC Mobile', 'prefix_id' => 'AAO.AAAE', 'base_price' => 76000,  'margin' => 2500],
            ['code' => '6004BP', 'name' => 'EA Sports FC Mobile 1070 FC Points', 'category' => 'game', 'operator' => 'EA Sports FC Mobile', 'prefix_id' => 'AAO.AAAE', 'base_price' => 152000, 'margin' => 4000],
            ['code' => '6005BP', 'name' => 'EA Sports FC Mobile 2200 FC Points', 'category' => 'game', 'operator' => 'EA Sports FC Mobile', 'prefix_id' => 'AAO.AAAE', 'base_price' => 315000, 'margin' => 7000],
            ['code' => '6007BP', 'name' => 'EA Sports FC Mobile 39 Silver',      'category' => 'game', 'operator' => 'EA Sports FC Mobile', 'prefix_id' => 'AAO.AAAE', 'base_price' => 6500,   'margin' => 800],
            ['code' => '6008BP', 'name' => 'EA Sports FC Mobile 99 Silver',      'category' => 'game', 'operator' => 'EA Sports FC Mobile', 'prefix_id' => 'AAO.AAAE', 'base_price' => 15500,  'margin' => 1000],
            ['code' => '6009BP', 'name' => 'EA Sports FC Mobile 499 Silver',     'category' => 'game', 'operator' => 'EA Sports FC Mobile', 'prefix_id' => 'AAO.AAAE', 'base_price' => 76000,  'margin' => 2500],

            // =========================================================================
            // 14. VOUCHER GAME — POINT BLANK (Prefix: AAO.AAAP)
            // =========================================================================
            ['code' => '6701BN', 'name' => 'Point Blank 1.200 PB Cash',  'category' => 'game', 'operator' => 'Point Blank', 'prefix_id' => 'AAO.AAAP', 'base_price' => 10000,  'margin' => 1000],
            ['code' => '6702BN', 'name' => 'Point Blank 2.400 PB Cash',  'category' => 'game', 'operator' => 'Point Blank', 'prefix_id' => 'AAO.AAAP', 'base_price' => 20000,  'margin' => 1200],
            ['code' => '6703BN', 'name' => 'Point Blank 6.000 PB Cash',  'category' => 'game', 'operator' => 'Point Blank', 'prefix_id' => 'AAO.AAAP', 'base_price' => 50000,  'margin' => 2000],
            ['code' => '6704BN', 'name' => 'Point Blank 12.000 PB Cash', 'category' => 'game', 'operator' => 'Point Blank', 'prefix_id' => 'AAO.AAAP', 'base_price' => 100000, 'margin' => 3000],
            ['code' => '6705BN', 'name' => 'Point Blank 24.000 PB Cash', 'category' => 'game', 'operator' => 'Point Blank', 'prefix_id' => 'AAO.AAAP', 'base_price' => 200000, 'margin' => 5000],
            ['code' => '6706BN', 'name' => 'Point Blank 36.000 PB Cash', 'category' => 'game', 'operator' => 'Point Blank', 'prefix_id' => 'AAO.AAAP', 'base_price' => 300000, 'margin' => 6500],
            ['code' => '6707BN', 'name' => 'Point Blank 60.000 PB Cash', 'category' => 'game', 'operator' => 'Point Blank', 'prefix_id' => 'AAO.AAAP', 'base_price' => 500000, 'margin' => 9000],

            // =========================================================================
            // 15. VOUCHER GAME — STEAM WALLET (Prefix: AAO.AAAV)
            // =========================================================================
            ['code' => '6001AX', 'name' => 'Steam Wallet IDR 12.000',  'category' => 'game', 'operator' => 'Steam Wallet', 'prefix_id' => 'AAO.AAAV', 'base_price' => 13200,  'margin' => 1000],
            ['code' => '6002AX', 'name' => 'Steam Wallet IDR 45.000',  'category' => 'game', 'operator' => 'Steam Wallet', 'prefix_id' => 'AAO.AAAV', 'base_price' => 48500,  'margin' => 2000],
            ['code' => '6003AX', 'name' => 'Steam Wallet IDR 60.000',  'category' => 'game', 'operator' => 'Steam Wallet', 'prefix_id' => 'AAO.AAAV', 'base_price' => 64500,  'margin' => 2500],
            ['code' => '6004AX', 'name' => 'Steam Wallet IDR 90.000',  'category' => 'game', 'operator' => 'Steam Wallet', 'prefix_id' => 'AAO.AAAV', 'base_price' => 96500,  'margin' => 3000],
            ['code' => '6120AX', 'name' => 'Steam Wallet IDR 120.000', 'category' => 'game', 'operator' => 'Steam Wallet', 'prefix_id' => 'AAO.AAAV', 'base_price' => 128500, 'margin' => 3500],
            ['code' => '6250AX', 'name' => 'Steam Wallet IDR 250.000', 'category' => 'game', 'operator' => 'Steam Wallet', 'prefix_id' => 'AAO.AAAV', 'base_price' => 267000, 'margin' => 6000],
            ['code' => '6400AX', 'name' => 'Steam Wallet IDR 400.000', 'category' => 'game', 'operator' => 'Steam Wallet', 'prefix_id' => 'AAO.AAAV', 'base_price' => 426000, 'margin' => 8000],
            ['code' => '6600AX', 'name' => 'Steam Wallet IDR 600.000', 'category' => 'game', 'operator' => 'Steam Wallet', 'prefix_id' => 'AAO.AAAV', 'base_price' => 638000, 'margin' => 10000],

            // =========================================================================
            // 16. VOUCHER GAME — GARENA SHELL (Prefix: AAO.AABA)
            // =========================================================================
            ['code' => '6010AH', 'name' => 'Garena 33 Shells',  'category' => 'game', 'operator' => 'Garena Shell', 'prefix_id' => 'AAO.AABA', 'base_price' => 10000,  'margin' => 1000],
            ['code' => '6020AH', 'name' => 'Garena 66 Shells',  'category' => 'game', 'operator' => 'Garena Shell', 'prefix_id' => 'AAO.AABA', 'base_price' => 19800,  'margin' => 1200],
            ['code' => '6050AH', 'name' => 'Garena 165 Shells', 'category' => 'game', 'operator' => 'Garena Shell', 'prefix_id' => 'AAO.AABA', 'base_price' => 49200,  'margin' => 2000],
            ['code' => '6100AH', 'name' => 'Garena 330 Shells', 'category' => 'game', 'operator' => 'Garena Shell', 'prefix_id' => 'AAO.AABA', 'base_price' => 98000,  'margin' => 3000],

            // =========================================================================
            // 17. VOUCHER GAME — RAZER GOLD (Prefix: AAO.AAAT)
            // =========================================================================
            ['code' => '6001AO', 'name' => 'Razer Gold 10.000',  'category' => 'game', 'operator' => 'Razer Gold', 'prefix_id' => 'AAO.AAAT', 'base_price' => 10200,  'margin' => 1000],
            ['code' => '6002AO', 'name' => 'Razer Gold 20.000',  'category' => 'game', 'operator' => 'Razer Gold', 'prefix_id' => 'AAO.AAAT', 'base_price' => 20300,  'margin' => 1200],
            ['code' => '6003AO', 'name' => 'Razer Gold 50.000',  'category' => 'game', 'operator' => 'Razer Gold', 'prefix_id' => 'AAO.AAAT', 'base_price' => 50500,  'margin' => 2000],
            ['code' => '6004AO', 'name' => 'Razer Gold 100.000', 'category' => 'game', 'operator' => 'Razer Gold', 'prefix_id' => 'AAO.AAAT', 'base_price' => 100800, 'margin' => 3000],
            ['code' => '6005AO', 'name' => 'Razer Gold 200.000', 'category' => 'game', 'operator' => 'Razer Gold', 'prefix_id' => 'AAO.AAAT', 'base_price' => 201200, 'margin' => 4500],
            ['code' => '6006AO', 'name' => 'Razer Gold 500.000', 'category' => 'game', 'operator' => 'Razer Gold', 'prefix_id' => 'AAO.AAAT', 'base_price' => 502500, 'margin' => 8000],

            // =========================================================================
            // 18. E-WALLET / UANG ELEKTRONIK — DANA (Prefix: EWAL.DANA)
            // Sumber: https://ppob.kiosbank.com:4040/api/webservicesV2/Main/Produk/UANG%20ELEKTRONIK
            // =========================================================================
            ['code' => '754001', 'name' => 'Saldo DANA Rp 10.000',  'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 10600,  'margin' => 900],
            ['code' => '754071', 'name' => 'Saldo DANA Rp 15.000',  'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 15600,  'margin' => 900],
            ['code' => '754011', 'name' => 'Saldo DANA Rp 20.000',  'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 20600,  'margin' => 900],
            ['code' => '754081', 'name' => 'Saldo DANA Rp 25.000',  'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 25600,  'margin' => 900],
            ['code' => '754091', 'name' => 'Saldo DANA Rp 30.000',  'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 30600,  'margin' => 1000],
            ['code' => '754101', 'name' => 'Saldo DANA Rp 35.000',  'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 35600,  'margin' => 1000],
            ['code' => '754111', 'name' => 'Saldo DANA Rp 40.000',  'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 40600,  'margin' => 1100],
            ['code' => '754121', 'name' => 'Saldo DANA Rp 45.000',  'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 45600,  'margin' => 1100],
            ['code' => '754021', 'name' => 'Saldo DANA Rp 50.000',  'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 50600,  'margin' => 1200],
            ['code' => '754151', 'name' => 'Saldo DANA Rp 75.000',  'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 75600,  'margin' => 1400],
            ['code' => '754031', 'name' => 'Saldo DANA Rp 100.000', 'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 100600, 'margin' => 1400],
            ['code' => '754191', 'name' => 'Saldo DANA Rp 150.000', 'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 150600, 'margin' => 1900],
            ['code' => '754041', 'name' => 'Saldo DANA Rp 200.000', 'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 200600, 'margin' => 2400],
            ['code' => '754051', 'name' => 'Saldo DANA Rp 250.000', 'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 250600, 'margin' => 2900],
            ['code' => '754061', 'name' => 'Saldo DANA Rp 300.000', 'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 300600, 'margin' => 3400],
            ['code' => '754211', 'name' => 'Saldo DANA Rp 400.000', 'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 400600, 'margin' => 4400],
            ['code' => '754221', 'name' => 'Saldo DANA Rp 500.000', 'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 500600, 'margin' => 5400],

            // =========================================================================
            // 19. E-WALLET / UANG ELEKTRONIK — GOPAY (Prefix: EWAL.GOPAY)
            // =========================================================================
            ['code' => '752001', 'name' => 'Saldo GoPay Rp 10.000',  'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 10700,  'margin' => 800],
            ['code' => '752101', 'name' => 'Saldo GoPay Rp 15.000',  'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 15700,  'margin' => 800],
            ['code' => '752011', 'name' => 'Saldo GoPay Rp 20.000',  'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 20700,  'margin' => 800],
            ['code' => '752021', 'name' => 'Saldo GoPay Rp 25.000',  'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 25700,  'margin' => 900],
            ['code' => '752111', 'name' => 'Saldo GoPay Rp 30.000',  'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 30700,  'margin' => 900],
            ['code' => '752121', 'name' => 'Saldo GoPay Rp 35.000',  'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 35700,  'margin' => 1000],
            ['code' => '752131', 'name' => 'Saldo GoPay Rp 40.000',  'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 40700,  'margin' => 1000],
            ['code' => '752031', 'name' => 'Saldo GoPay Rp 50.000',  'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 50700,  'margin' => 1100],
            ['code' => '752041', 'name' => 'Saldo GoPay Rp 75.000',  'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 75700,  'margin' => 1300],
            ['code' => '752051', 'name' => 'Saldo GoPay Rp 100.000', 'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 100700, 'margin' => 1300],
            ['code' => '752061', 'name' => 'Saldo GoPay Rp 150.000', 'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 150700, 'margin' => 1800],
            ['code' => '752071', 'name' => 'Saldo GoPay Rp 200.000', 'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 200700, 'margin' => 2300],
            ['code' => '752081', 'name' => 'Saldo GoPay Rp 250.000', 'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 250700, 'margin' => 2800],
            ['code' => '752151', 'name' => 'Saldo GoPay Rp 300.000', 'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 300700, 'margin' => 3300],
            ['code' => '752171', 'name' => 'Saldo GoPay Rp 500.000', 'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 500700, 'margin' => 5300],

            // =========================================================================
            // 20. E-WALLET / UANG ELEKTRONIK — OVO (Prefix: EWAL.OVO)
            // =========================================================================
            ['code' => '751001', 'name' => 'Saldo OVO Rp 20.000',  'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 20800,  'margin' => 700],
            ['code' => '751011', 'name' => 'Saldo OVO Rp 25.000',  'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 25800,  'margin' => 800],
            ['code' => '751121', 'name' => 'Saldo OVO Rp 30.000',  'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 30800,  'margin' => 800],
            ['code' => '751141', 'name' => 'Saldo OVO Rp 40.000',  'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 40800,  'margin' => 900],
            ['code' => '751021', 'name' => 'Saldo OVO Rp 50.000',  'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 50800,  'margin' => 1000],
            ['code' => '751201', 'name' => 'Saldo OVO Rp 75.000',  'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 75800,  'margin' => 1200],
            ['code' => '751031', 'name' => 'Saldo OVO Rp 100.000', 'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 100800, 'margin' => 1200],
            ['code' => '751351', 'name' => 'Saldo OVO Rp 150.000', 'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 150800, 'margin' => 1700],
            ['code' => '751041', 'name' => 'Saldo OVO Rp 200.000', 'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 200800, 'margin' => 2200],
            ['code' => '751051', 'name' => 'Saldo OVO Rp 250.000', 'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 250800, 'margin' => 2700],
            ['code' => '751061', 'name' => 'Saldo OVO Rp 300.000', 'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 300800, 'margin' => 3200],
            ['code' => '751081', 'name' => 'Saldo OVO Rp 400.000', 'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 400800, 'margin' => 4200],
            ['code' => '751101', 'name' => 'Saldo OVO Rp 500.000', 'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 500800, 'margin' => 5200],

            // =========================================================================
            // 21. E-WALLET / UANG ELEKTRONIK — SHOPEEPAY (Prefix: EWAL.SHOPEE)
            // =========================================================================
            ['code' => '756001', 'name' => 'Saldo ShopeePay Rp 10.000',  'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 10600,  'margin' => 900],
            ['code' => '756011', 'name' => 'Saldo ShopeePay Rp 15.000',  'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 15600,  'margin' => 900],
            ['code' => '756021', 'name' => 'Saldo ShopeePay Rp 20.000',  'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 20600,  'margin' => 900],
            ['code' => '756031', 'name' => 'Saldo ShopeePay Rp 25.000',  'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 25600,  'margin' => 900],
            ['code' => '756041', 'name' => 'Saldo ShopeePay Rp 30.000',  'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 30600,  'margin' => 1000],
            ['code' => '756051', 'name' => 'Saldo ShopeePay Rp 35.000',  'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 35600,  'margin' => 1000],
            ['code' => '756061', 'name' => 'Saldo ShopeePay Rp 40.000',  'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 40600,  'margin' => 1100],
            ['code' => '756081', 'name' => 'Saldo ShopeePay Rp 50.000',  'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 50600,  'margin' => 1200],
            ['code' => '756131', 'name' => 'Saldo ShopeePay Rp 75.000',  'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 75600,  'margin' => 1400],
            ['code' => '756181', 'name' => 'Saldo ShopeePay Rp 100.000', 'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 100600, 'margin' => 1400],
            ['code' => '756281', 'name' => 'Saldo ShopeePay Rp 150.000', 'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 150600, 'margin' => 1900],
            ['code' => '756381', 'name' => 'Saldo ShopeePay Rp 200.000', 'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 200600, 'margin' => 2400],
            ['code' => '756481', 'name' => 'Saldo ShopeePay Rp 250.000', 'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 250600, 'margin' => 2900],
            ['code' => '756581', 'name' => 'Saldo ShopeePay Rp 300.000', 'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 300600, 'margin' => 3400],
            ['code' => '756981', 'name' => 'Saldo ShopeePay Rp 500.000', 'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 500600, 'margin' => 5400],

            // =========================================================================
            // 22. E-WALLET / UANG ELEKTRONIK — LINKAJA (Prefix: EWAL.LINKAJA)
            // =========================================================================
            ['code' => '755001', 'name' => 'Saldo LinkAja Rp 10.000',  'category' => 'ewallet', 'operator' => 'LinkAja', 'prefix_id' => 'EWAL.LINKAJA', 'base_price' => 10600,  'margin' => 900],
            ['code' => '755011', 'name' => 'Saldo LinkAja Rp 20.000',  'category' => 'ewallet', 'operator' => 'LinkAja', 'prefix_id' => 'EWAL.LINKAJA', 'base_price' => 20600,  'margin' => 900],
            ['code' => '755021', 'name' => 'Saldo LinkAja Rp 25.000',  'category' => 'ewallet', 'operator' => 'LinkAja', 'prefix_id' => 'EWAL.LINKAJA', 'base_price' => 25600,  'margin' => 900],
            ['code' => '755051', 'name' => 'Saldo LinkAja Rp 50.000',  'category' => 'ewallet', 'operator' => 'LinkAja', 'prefix_id' => 'EWAL.LINKAJA', 'base_price' => 50600,  'margin' => 1200],
            ['code' => '755101', 'name' => 'Saldo LinkAja Rp 100.000', 'category' => 'ewallet', 'operator' => 'LinkAja', 'prefix_id' => 'EWAL.LINKAJA', 'base_price' => 100600, 'margin' => 1400],
            ['code' => '755151', 'name' => 'Saldo LinkAja Rp 150.000', 'category' => 'ewallet', 'operator' => 'LinkAja', 'prefix_id' => 'EWAL.LINKAJA', 'base_price' => 150600, 'margin' => 1900],
            ['code' => '755201', 'name' => 'Saldo LinkAja Rp 200.000', 'category' => 'ewallet', 'operator' => 'LinkAja', 'prefix_id' => 'EWAL.LINKAJA', 'base_price' => 200600, 'margin' => 2400],
            ['code' => '755251', 'name' => 'Saldo LinkAja Rp 250.000', 'category' => 'ewallet', 'operator' => 'LinkAja', 'prefix_id' => 'EWAL.LINKAJA', 'base_price' => 250600, 'margin' => 2900],
            ['code' => '755291', 'name' => 'Saldo LinkAja Rp 500.000', 'category' => 'ewallet', 'operator' => 'LinkAja', 'prefix_id' => 'EWAL.LINKAJA', 'base_price' => 500600, 'margin' => 5400],

            // =========================================================================
            // 23. E-WALLET / UANG ELEKTRONIK — MANDIRI E-MONEY (Prefix: EWAL.EMONEY)
            // =========================================================================
            ['code' => '753001', 'name' => 'Mandiri E-Money Rp 20.000',  'category' => 'ewallet', 'operator' => 'Mandiri E-Money', 'prefix_id' => 'EWAL.EMONEY', 'base_price' => 20800,  'margin' => 1000],
            ['code' => '753051', 'name' => 'Mandiri E-Money Rp 25.000',  'category' => 'ewallet', 'operator' => 'Mandiri E-Money', 'prefix_id' => 'EWAL.EMONEY', 'base_price' => 25800,  'margin' => 1000],
            ['code' => '753011', 'name' => 'Mandiri E-Money Rp 50.000',  'category' => 'ewallet', 'operator' => 'Mandiri E-Money', 'prefix_id' => 'EWAL.EMONEY', 'base_price' => 50800,  'margin' => 1200],
            ['code' => '753021', 'name' => 'Mandiri E-Money Rp 100.000', 'category' => 'ewallet', 'operator' => 'Mandiri E-Money', 'prefix_id' => 'EWAL.EMONEY', 'base_price' => 100800, 'margin' => 1500],
            ['code' => '753061', 'name' => 'Mandiri E-Money Rp 150.000', 'category' => 'ewallet', 'operator' => 'Mandiri E-Money', 'prefix_id' => 'EWAL.EMONEY', 'base_price' => 150800, 'margin' => 2000],
            ['code' => '753031', 'name' => 'Mandiri E-Money Rp 200.000', 'category' => 'ewallet', 'operator' => 'Mandiri E-Money', 'prefix_id' => 'EWAL.EMONEY', 'base_price' => 200800, 'margin' => 2500],
            ['code' => '753071', 'name' => 'Mandiri E-Money Rp 250.000', 'category' => 'ewallet', 'operator' => 'Mandiri E-Money', 'prefix_id' => 'EWAL.EMONEY', 'base_price' => 250800, 'margin' => 3000],
            ['code' => '753081', 'name' => 'Mandiri E-Money Rp 300.000', 'category' => 'ewallet', 'operator' => 'Mandiri E-Money', 'prefix_id' => 'EWAL.EMONEY', 'base_price' => 300800, 'margin' => 3500],
            ['code' => '753041', 'name' => 'Mandiri E-Money Rp 500.000', 'category' => 'ewallet', 'operator' => 'Mandiri E-Money', 'prefix_id' => 'EWAL.EMONEY', 'base_price' => 500800, 'margin' => 5500],

            // =========================================================================
            // 24. APLIKASI STREAMING RESMI (Prefix: AAB.*)
            // Sumber: https://ppob.kiosbank.com:4040/api/webservicesV2/Main/Produk/STREAMING
            // =========================================================================
            // Spotify (AAB.AAAA)
            ['code' => '77A013', 'name' => 'Spotify Premium 1 Bulan', 'category' => 'streaming', 'operator' => 'Spotify', 'prefix_id' => 'AAB.AAAA', 'base_price' => 54990,  'margin' => 2510],
            ['code' => '77A023', 'name' => 'Spotify Premium 3 Bulan', 'category' => 'streaming', 'operator' => 'Spotify', 'prefix_id' => 'AAB.AAAA', 'base_price' => 164970, 'margin' => 5030],
            ['code' => '77A033', 'name' => 'Spotify Premium 6 Bulan', 'category' => 'streaming', 'operator' => 'Spotify', 'prefix_id' => 'AAB.AAAA', 'base_price' => 329940, 'margin' => 8060],

            // beIN Sports (AAB.AAAB)
            ['code' => '77A012', 'name' => 'beIN Sports Connect 1 Bulan', 'category' => 'streaming', 'operator' => 'beIN Sports', 'prefix_id' => 'AAB.AAAB', 'base_price' => 45000, 'margin' => 2500],

            // Catchplay+ (AAB.AAAC)
            ['code' => '77A011', 'name' => 'Catchplay+ Movie Lovers 1 Bulan', 'category' => 'streaming', 'operator' => 'Catchplay+', 'prefix_id' => 'AAB.AAAC', 'base_price' => 45000, 'margin' => 2500],

            // iTunes Gift Card (AAB.AAAD)
            ['code' => '77A021', 'name' => 'iTunes US 10 Dollar',  'category' => 'streaming', 'operator' => 'iTunes', 'prefix_id' => 'AAB.AAAD', 'base_price' => 160000,  'margin' => 5000],
            ['code' => '77A031', 'name' => 'iTunes US 15 Dollar',  'category' => 'streaming', 'operator' => 'iTunes', 'prefix_id' => 'AAB.AAAD', 'base_price' => 240000,  'margin' => 6000],
            ['code' => '77A041', 'name' => 'iTunes US 25 Dollar',  'category' => 'streaming', 'operator' => 'iTunes', 'prefix_id' => 'AAB.AAAD', 'base_price' => 395000,  'margin' => 8000],
            ['code' => '77A051', 'name' => 'iTunes US 50 Dollar',  'category' => 'streaming', 'operator' => 'iTunes', 'prefix_id' => 'AAB.AAAD', 'base_price' => 785000,  'margin' => 12000],
            ['code' => '77A061', 'name' => 'iTunes US 100 Dollar', 'category' => 'streaming', 'operator' => 'iTunes', 'prefix_id' => 'AAB.AAAD', 'base_price' => 1560000, 'margin' => 20000],

            // Vidio (AAB.AAAE)
            ['code' => '77A071', 'name' => 'Vidio Premier Platinum 7 Hari',            'category' => 'streaming', 'operator' => 'Vidio', 'prefix_id' => 'AAB.AAAE', 'base_price' => 19000,  'margin' => 1500],
            ['code' => '77A081', 'name' => 'Vidio Premier Platinum 30 Hari',           'category' => 'streaming', 'operator' => 'Vidio', 'prefix_id' => 'AAB.AAAE', 'base_price' => 39000,  'margin' => 2000],
            ['code' => '77D101', 'name' => 'Vidio Premier Platinum Mahasiswa 30 Hari', 'category' => 'streaming', 'operator' => 'Vidio', 'prefix_id' => 'AAB.AAAE', 'base_price' => 25000,  'margin' => 1500],
            ['code' => '77D111', 'name' => 'Vidio Premier Platinum + F1 30 Hari',      'category' => 'streaming', 'operator' => 'Vidio', 'prefix_id' => 'AAB.AAAE', 'base_price' => 59000,  'margin' => 2500],
            ['code' => '77A091', 'name' => 'Vidio Premier Platinum 1 Tahun',           'category' => 'streaming', 'operator' => 'Vidio', 'prefix_id' => 'AAB.AAAE', 'base_price' => 269000, 'margin' => 6000],

            // Vision+ (AAB.AAAG)
            ['code' => '77A111', 'name' => 'Vision+ Premium 30 Hari',        'category' => 'streaming', 'operator' => 'Vision+', 'prefix_id' => 'AAB.AAAG', 'base_price' => 20000,  'margin' => 1500],
            ['code' => '77A121', 'name' => 'Vision+ Premium Sport 7 Hari',   'category' => 'streaming', 'operator' => 'Vision+', 'prefix_id' => 'AAB.AAAG', 'base_price' => 18000,  'margin' => 1500],
            ['code' => '77A131', 'name' => 'Vision+ Premium Sport 30 Hari',  'category' => 'streaming', 'operator' => 'Vision+', 'prefix_id' => 'AAB.AAAG', 'base_price' => 35000,  'margin' => 2000],
            ['code' => '77A141', 'name' => 'Vision+ Premium Sport 90 Hari',  'category' => 'streaming', 'operator' => 'Vision+', 'prefix_id' => 'AAB.AAAG', 'base_price' => 95000,  'margin' => 3500],
            ['code' => '77A151', 'name' => 'Vision+ Premium Sport 365 Hari', 'category' => 'streaming', 'operator' => 'Vision+', 'prefix_id' => 'AAB.AAAG', 'base_price' => 200000, 'margin' => 5000],

            // Genflix (AAB.AAAH)
            ['code' => '77A161', 'name' => 'Genflix 1 Bulan', 'category' => 'streaming', 'operator' => 'Genflix', 'prefix_id' => 'AAB.AAAH', 'base_price' => 25000, 'margin' => 1500],

            // Sushiroll (AAB.AAAI)
            ['code' => '77A171', 'name' => 'Sushiroll 30 Hari All Access', 'category' => 'streaming', 'operator' => 'Sushiroll', 'prefix_id' => 'AAB.AAAI', 'base_price' => 20000, 'margin' => 1500],

            // Bigo Live (AAB.AAAJ)
            ['code' => '77A181', 'name' => 'Bigo Live 5 Diamond',   'category' => 'streaming', 'operator' => 'Bigo Live', 'prefix_id' => 'AAB.AAAJ', 'base_price' => 2500,   'margin' => 600],
            ['code' => '77A191', 'name' => 'Bigo Live 30 Diamond',  'category' => 'streaming', 'operator' => 'Bigo Live', 'prefix_id' => 'AAB.AAAJ', 'base_price' => 10500,  'margin' => 1000],
            ['code' => '77A201', 'name' => 'Bigo Live 50 Diamond',  'category' => 'streaming', 'operator' => 'Bigo Live', 'prefix_id' => 'AAB.AAAJ', 'base_price' => 17000,  'margin' => 1200],
            ['code' => '77A211', 'name' => 'Bigo Live 100 Diamond', 'category' => 'streaming', 'operator' => 'Bigo Live', 'prefix_id' => 'AAB.AAAJ', 'base_price' => 33000,  'margin' => 1800],
            ['code' => '77A221', 'name' => 'Bigo Live 250 Diamond', 'category' => 'streaming', 'operator' => 'Bigo Live', 'prefix_id' => 'AAB.AAAJ', 'base_price' => 82000,  'margin' => 3000],

            // Yalla Live (AAB.AAAK)
            ['code' => '77B021', 'name' => 'Yalla Live Gold 2 USD',  'category' => 'streaming', 'operator' => 'Yalla Live', 'prefix_id' => 'AAB.AAAK', 'base_price' => 32000,  'margin' => 1800],
            ['code' => '77B031', 'name' => 'Yalla Live Gold 5 USD',  'category' => 'streaming', 'operator' => 'Yalla Live', 'prefix_id' => 'AAB.AAAK', 'base_price' => 79000,  'margin' => 3000],
            ['code' => '77B041', 'name' => 'Yalla Live Gold 10 USD', 'category' => 'streaming', 'operator' => 'Yalla Live', 'prefix_id' => 'AAB.AAAK', 'base_price' => 158000, 'margin' => 4500],

            // Mico Live (AAB.AAAL)
            ['code' => '77B091', 'name' => 'Mico Live 90 Coins',  'category' => 'streaming', 'operator' => 'Mico Live', 'prefix_id' => 'AAB.AAAL', 'base_price' => 22000,  'margin' => 1500],
            ['code' => '77B101', 'name' => 'Mico Live 300 Coins', 'category' => 'streaming', 'operator' => 'Mico Live', 'prefix_id' => 'AAB.AAAL', 'base_price' => 68000,  'margin' => 2500],
            ['code' => '77B111', 'name' => 'Mico Live 450 Coins', 'category' => 'streaming', 'operator' => 'Mico Live', 'prefix_id' => 'AAB.AAAL', 'base_price' => 102000, 'margin' => 3500],
            ['code' => '77B121', 'name' => 'Mico Live 900 Coins', 'category' => 'streaming', 'operator' => 'Mico Live', 'prefix_id' => 'AAB.AAAL', 'base_price' => 202000, 'margin' => 5000],

            // LivU (AAB.AAAM)
            ['code' => '77B161', 'name' => 'LivU 350 Coins',  'category' => 'streaming', 'operator' => 'LivU', 'prefix_id' => 'AAB.AAAM', 'base_price' => 25000,  'margin' => 1500],
            ['code' => '77B171', 'name' => 'LivU 650 Coins',  'category' => 'streaming', 'operator' => 'LivU', 'prefix_id' => 'AAB.AAAM', 'base_price' => 45000,  'margin' => 2000],
            ['code' => '77B181', 'name' => 'LivU 1350 Coins', 'category' => 'streaming', 'operator' => 'LivU', 'prefix_id' => 'AAB.AAAM', 'base_price' => 90000,  'margin' => 3000],
            ['code' => '77B191', 'name' => 'LivU 3000 Coins', 'category' => 'streaming', 'operator' => 'LivU', 'prefix_id' => 'AAB.AAAM', 'base_price' => 195000, 'margin' => 5000],

            // Lita (AAB.AAAN)
            ['code' => '77B211', 'name' => 'Lita 40 Coins',   'category' => 'streaming', 'operator' => 'Lita', 'prefix_id' => 'AAB.AAAN', 'base_price' => 9000,   'margin' => 1000],
            ['code' => '77B221', 'name' => 'Lita 100 Coins',  'category' => 'streaming', 'operator' => 'Lita', 'prefix_id' => 'AAB.AAAN', 'base_price' => 20000,  'margin' => 1500],
            ['code' => '77B231', 'name' => 'Lita 200 Coins',  'category' => 'streaming', 'operator' => 'Lita', 'prefix_id' => 'AAB.AAAN', 'base_price' => 39000,  'margin' => 2000],
            ['code' => '77B241', 'name' => 'Lita 500 Coins',  'category' => 'streaming', 'operator' => 'Lita', 'prefix_id' => 'AAB.AAAN', 'base_price' => 96000,  'margin' => 3500],
            ['code' => '77B251', 'name' => 'Lita 1000 Coins', 'category' => 'streaming', 'operator' => 'Lita', 'prefix_id' => 'AAB.AAAN', 'base_price' => 190000, 'margin' => 5000],

            // Litmatch (AAB.AAAO)
            ['code' => '77B281', 'name' => 'Litmatch 20 Diamonds',  'category' => 'streaming', 'operator' => 'Litmatch', 'prefix_id' => 'AAB.AAAO', 'base_price' => 5500,   'margin' => 800],
            ['code' => '77B291', 'name' => 'Litmatch 45 Diamonds',  'category' => 'streaming', 'operator' => 'Litmatch', 'prefix_id' => 'AAB.AAAO', 'base_price' => 11500,  'margin' => 1000],
            ['code' => '77B301', 'name' => 'Litmatch 240 Diamonds', 'category' => 'streaming', 'operator' => 'Litmatch', 'prefix_id' => 'AAB.AAAO', 'base_price' => 56000,  'margin' => 2500],
            ['code' => '77B311', 'name' => 'Litmatch 620 Diamonds', 'category' => 'streaming', 'operator' => 'Litmatch', 'prefix_id' => 'AAB.AAAO', 'base_price' => 142000, 'margin' => 4000],

            // Bstation (AAB.AAAP)
            ['code' => '77C071', 'name' => 'Bstation Premium 7 Hari',  'category' => 'streaming', 'operator' => 'Bstation', 'prefix_id' => 'AAB.AAAP', 'base_price' => 12000,  'margin' => 1200],
            ['code' => '77C081', 'name' => 'Bstation Premium 31 Hari', 'category' => 'streaming', 'operator' => 'Bstation', 'prefix_id' => 'AAB.AAAP', 'base_price' => 29000,  'margin' => 1800],
            ['code' => '77C091', 'name' => 'Bstation Premium 93 Hari', 'category' => 'streaming', 'operator' => 'Bstation', 'prefix_id' => 'AAB.AAAP', 'base_price' => 79000,  'margin' => 3000],
            ['code' => '77C101', 'name' => 'Bstation Premium 1 Tahun', 'category' => 'streaming', 'operator' => 'Bstation', 'prefix_id' => 'AAB.AAAP', 'base_price' => 249000, 'margin' => 6000],

            // Tinder (AAB.AAAQ)
            ['code' => '77C111', 'name' => 'Tinder Plus 1 Bulan', 'category' => 'streaming', 'operator' => 'Tinder', 'prefix_id' => 'AAB.AAAQ', 'base_price' => 75000,  'margin' => 3000],
            ['code' => '77C121', 'name' => 'Tinder Plus 6 Bulan', 'category' => 'streaming', 'operator' => 'Tinder', 'prefix_id' => 'AAB.AAAQ', 'base_price' => 225000, 'margin' => 6000],
            ['code' => '77C141', 'name' => 'Tinder Gold 1 Bulan', 'category' => 'streaming', 'operator' => 'Tinder', 'prefix_id' => 'AAB.AAAQ', 'base_price' => 145000, 'margin' => 4500],
            ['code' => '77C151', 'name' => 'Tinder Gold 6 Bulan', 'category' => 'streaming', 'operator' => 'Tinder', 'prefix_id' => 'AAB.AAAQ', 'base_price' => 435000, 'margin' => 9000],

            // WeTV (AAB.AAAR)
            ['code' => '77C171', 'name' => 'WeTV VIP 1 Bulan', 'category' => 'streaming', 'operator' => 'WeTV', 'prefix_id' => 'AAB.AAAR', 'base_price' => 35000,  'margin' => 2000],
            ['code' => '77C181', 'name' => 'WeTV VIP 3 Bulan', 'category' => 'streaming', 'operator' => 'WeTV', 'prefix_id' => 'AAB.AAAR', 'base_price' => 89000,  'margin' => 3000],
            ['code' => '77C191', 'name' => 'WeTV VIP 1 Tahun', 'category' => 'streaming', 'operator' => 'WeTV', 'prefix_id' => 'AAB.AAAR', 'base_price' => 299000, 'margin' => 6000],

            // Netflix (AAB.AAAS)
            ['code' => '77C201', 'name' => 'Netflix Mobile 1 Bulan',   'category' => 'streaming', 'operator' => 'Netflix', 'prefix_id' => 'AAB.AAAS', 'base_price' => 54000,  'margin' => 2500],
            ['code' => '77C211', 'name' => 'Netflix Basic 1 Bulan',    'category' => 'streaming', 'operator' => 'Netflix', 'prefix_id' => 'AAB.AAAS', 'base_price' => 65000,  'margin' => 3000],
            ['code' => '77C221', 'name' => 'Netflix Standard 1 Bulan', 'category' => 'streaming', 'operator' => 'Netflix', 'prefix_id' => 'AAB.AAAS', 'base_price' => 120000, 'margin' => 4000],
            ['code' => '77C231', 'name' => 'Netflix Premium 1 Bulan',  'category' => 'streaming', 'operator' => 'Netflix', 'prefix_id' => 'AAB.AAAS', 'base_price' => 186000, 'margin' => 5000],

            // Disney+ Hotstar (AAB.AAAT)
            ['code' => '77C241', 'name' => 'Disney+ Hotstar 1 Bulan', 'category' => 'streaming', 'operator' => 'Disney+', 'prefix_id' => 'AAB.AAAT', 'base_price' => 65000,  'margin' => 3000],
            ['code' => '77C251', 'name' => 'Disney+ Hotstar 1 Tahun', 'category' => 'streaming', 'operator' => 'Disney+', 'prefix_id' => 'AAB.AAAT', 'base_price' => 450000, 'margin' => 9000],

            // HBO GO (AAB.AAAU)
            ['code' => '77C261', 'name' => 'HBO GO 30 Hari', 'category' => 'streaming', 'operator' => 'HBO GO', 'prefix_id' => 'AAB.AAAU', 'base_price' => 60000, 'margin' => 3000],

            // LinkPlus (AAB.AAAV)
            ['code' => '77D001', 'name' => 'Voucher LinkPlus 12.800', 'category' => 'streaming', 'operator' => 'LinkPlus', 'prefix_id' => 'AAB.AAAV', 'base_price' => 12800, 'margin' => 1200],
            ['code' => '77D011', 'name' => 'Voucher LinkPlus 16.000', 'category' => 'streaming', 'operator' => 'LinkPlus', 'prefix_id' => 'AAB.AAAV', 'base_price' => 16000, 'margin' => 1200],
            ['code' => '77D021', 'name' => 'Voucher LinkPlus 19.000', 'category' => 'streaming', 'operator' => 'LinkPlus', 'prefix_id' => 'AAB.AAAV', 'base_price' => 19000, 'margin' => 1500],

            // Prime Video (AAB.AAAW)
            ['code' => '77D031', 'name' => 'Amazon Prime Video 1 Bulan', 'category' => 'streaming', 'operator' => 'Prime Video', 'prefix_id' => 'AAB.AAAW', 'base_price' => 59000, 'margin' => 2500],

            // Mola TV (AAB.AAAX)
            ['code' => '77D041', 'name' => 'Mola TV 1 Bulan', 'category' => 'streaming', 'operator' => 'Mola TV', 'prefix_id' => 'AAB.AAAX', 'base_price' => 50000, 'margin' => 2500],

            // Joox (AAB.AAAY)
            ['code' => '77D051', 'name' => 'JOOX VIP 30 Hari', 'category' => 'streaming', 'operator' => 'Joox', 'prefix_id' => 'AAB.AAAY', 'base_price' => 49000, 'margin' => 2500],

            // iFlix (AAB.AAAZ)
            ['code' => '77D061', 'name' => 'iFlix VIP 1 Bulan', 'category' => 'streaming', 'operator' => 'iFlix', 'prefix_id' => 'AAB.AAAZ', 'base_price' => 39000, 'margin' => 2000],

            // Viu (AAB.AABA)
            ['code' => '77D071', 'name' => 'Viu Premium 1 Bulan',  'category' => 'streaming', 'operator' => 'Viu', 'prefix_id' => 'AAB.AABA', 'base_price' => 33000,  'margin' => 2000],
            ['code' => '77D081', 'name' => 'Viu Premium 6 Bulan',  'category' => 'streaming', 'operator' => 'Viu', 'prefix_id' => 'AAB.AABA', 'base_price' => 165000, 'margin' => 4500],
            ['code' => '77D091', 'name' => 'Viu Premium 12 Bulan', 'category' => 'streaming', 'operator' => 'Viu', 'prefix_id' => 'AAB.AABA', 'base_price' => 290000, 'margin' => 6000],
        ];
    }
}
