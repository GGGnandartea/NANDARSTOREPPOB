<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class KiosbankConfiguration
{
    public const ENDPOINTS = [
        'development' => ['host' => 'development.kiosbank.com', 'port' => '4432'],
        'production' => ['host' => 'transaksi.kiosbank.com', 'port' => '17109'],
    ];

    public function mode(): string
    {
        $mode = Setting::where('key', 'kiosbank_mode')->value('value');

        return array_key_exists($mode ?? '', self::ENDPOINTS) ? $mode : 'development';
    }

    public function profile(string $mode): array
    {
        if (!isset(self::ENDPOINTS[$mode])) {
            throw new \InvalidArgumentException('Mode Kiosbank tidak valid.');
        }

        $settings = Setting::where('key', 'like', 'kiosbank_%')->pluck('value', 'key')->all();
        $legacyMode = $settings['kiosbank_mode'] ?? 'development';
        $profile = self::ENDPOINTS[$mode] + ['protocol' => 'https'];

        foreach (['username', 'password', 'mitra', 'account_id', 'merchant_id', 'merchant_name', 'counter_id', 'private_key', 'public_key'] as $field) {
            $key = "kiosbank_{$mode}_{$field}";
            $default = config("kiosbank.profiles.{$mode}.{$field}") ?: config("kiosbank.{$field}");
            $legacy = $legacyMode === $mode ? ($settings["kiosbank_{$field}"] ?? '') : '';
            $value = $settings[$key] ?? ($default ?: $legacy);
            $profile[$field] = str_starts_with((string) $value, 'encrypted:')
                ? Crypt::decryptString(substr($value, 10))
                : (string) $value;
        }

        return $profile;
    }

    public function saveProfile(string $mode, array $values): void
    {
        if (!isset(self::ENDPOINTS[$mode])) {
            throw new \InvalidArgumentException('Mode Kiosbank tidak valid.');
        }

        foreach (['username', 'password', 'mitra', 'account_id', 'merchant_id', 'merchant_name', 'counter_id', 'private_key', 'public_key'] as $field) {
            if (!array_key_exists($field, $values)) {
                continue;
            }
            $value = trim((string) $values[$field]);
            if (in_array($field, ['password', 'private_key'], true)) {
                if ($value === '') {
                    continue;
                }
                $value = 'encrypted:'.Crypt::encryptString($value);
            }
            Setting::updateOrCreate(['key' => "kiosbank_{$mode}_{$field}"], ['value' => $value]);
        }
    }
}
