<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\KiosbankService;
use Illuminate\Database\Seeder;

class KiosbankDevSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Simpan Pengaturan Kiosbank Developer Mode
        $settings = [
            'kiosbank_mode'            => 'development',
            'kiosbank_host'            => 'development.kiosbank.com',
            'kiosbank_port'            => '4432',
            'kiosbank_protocol'        => 'https',
            'kiosbank_username'        => 'dji',
            'kiosbank_password'        => 'abcde',
            'kiosbank_mitra'           => 'DJI',
            'kiosbank_account_id'      => 'ujicoba',
            'kiosbank_merchant_id'     => 'DJI000343',
            'kiosbank_merchant_name'   => 'PT.Testing',
            'kiosbank_counter_id'      => '1',
            'kiosbank_markup_type'     => 'fixed',
            'kiosbank_markup_value'    => '1000',
            'kiosbank_enabled'         => '1',
            'kiosbank_sandbox_deposit' => '11251850', // Saldo awal uji coba sesuai dokumentasi
        ];

        foreach ($settings as $k => $v) {
            Setting::updateOrCreate(['key' => $k], ['value' => $v]);
        }

        // 2. Isi data katalog produk PPOB default ke tabel ppob_products
        $service = new KiosbankService();
        $service->ensureCatalogSeeded();
    }
}

