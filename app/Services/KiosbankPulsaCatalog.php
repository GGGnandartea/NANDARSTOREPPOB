<?php

namespace App\Services;

class KiosbankPulsaCatalog
{
    public const OPERATORS = [
        '21' => 'Telkomsel', '11' => 'Indosat', '31' => 'XL', '41' => 'Tri',
        '51' => 'Axis', '81' => 'Smartfren', 'byu' => 'by.U', 'wifi' => 'WiFi ID',
    ];

    public static function products(): array
    {
        return json_decode(file_get_contents(resource_path('data/kiosbank-pulsa.json')), true, 512, JSON_THROW_ON_ERROR)['products'];
    }

    public static function metadata(string $code, string $name): ?array
    {
        if (!preg_match('/^5[A-Z0-9]{5}$/', $code)) {
            return null;
        }

        $prefix = preg_match('/^50[ABC][0-9]21$/', $code) || preg_match('/^BY[. ]?U\b/i', $name) ? 'byu'
            : (str_ends_with($code, '01') ? 'wifi' : substr($code, -2));
        if (!isset(self::OPERATORS[$prefix])) {
            return null;
        }

        // Extensions and voice-only bundles are not internet quotas.
        $category = match (true) {
            str_starts_with($code, '5DM') => 'masa_aktif',
            preg_match('/^(TALKMANIA|XL NELPON)/i', $name) === 1 => 'telepon',
            str_starts_with($code, '5D'), $prefix === 'wifi' => 'data',
            default => 'pulsa',
        };

        return ['category' => $category, 'operator' => self::OPERATORS[$prefix], 'prefix_id' => $prefix];
    }

    /**
     * Estimasi harga dasar (base_price) untuk simulasi/pengujian Development Mode
     * ketika server sandbox Kiosbank belum menyediakan daftar harga (rc 19).
     */
    public static function developmentBasePrice(string $code, string $name): int
    {
        if (preg_match('/\b(\d{1,3}(?:\.\d{3})+)\b/', $name, $matches)) {
            $nominal = (int) str_replace('.', '', $matches[1]);
            if ($nominal >= 1000) {
                return $nominal + 500;
            }
        }

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*GB/i', $name, $matches)) {
            $gb = (float) str_replace(',', '.', $matches[1]);
            return match (true) {
                $gb <= 1 => 5000,
                $gb <= 3 => 12000,
                $gb <= 6 => 20000,
                $gb <= 12 => 32000,
                $gb <= 25 => 50000,
                $gb <= 50 => 75000,
                default => 100000,
            };
        }

        if (preg_match('/(\d+)\s*MB/i', $name)) {
            return 5000;
        }

        if (preg_match('/(\d+)\s*(?:HARI|HR)\b/i', $name, $matches)) {
            $days = (int) $matches[1];
            return $days <= 7 ? 6000 : ($days <= 15 ? 10000 : 15000);
        }

        return 10000;
    }
}
