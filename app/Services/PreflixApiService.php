<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Setting;

class PreflixApiService
{
    protected $baseUrl = 'https://h2h.preflix.my.id/app/';
    protected $idUser;
    protected $keyUser;

    public function __construct()
    {
        // Mengambil kredensial Preflix dari database
        $this->idUser = Setting::where('key', 'preflix_id_user')->value('value');
        $this->keyUser = Setting::where('key', 'preflix_key_user')->value('value');
    }

    // Fungsi Helper Khusus Form URL-Encoded Preflix
    private function request($koneksi, $data = [])
    {
        $payload = array_merge([
            'koneksi' => $koneksi,
            'id_user' => $this->idUser,
            'key_user' => $this->keyUser
        ], $data);

        // Tambahan withoutVerifying() untuk mem-bypass error SSL di XAMPP/Localhost
        return Http::withoutVerifying()->asForm()->post($this->baseUrl, $payload)->json();
    }

    public function getKategori()
    {
        return $this->request('get_kategori');
    }

    public function getReadyProduct()
    {
        return $this->request('get_ready_product');
    }

    public function getStokById($idProduk)
    {
        return $this->request('get_stok_by_id', [
            'id_produk' => $idProduk
        ]);
    }

    public function order($idProduk, $durasi = '', $email = '', $catatan = '')
    {
        $params = [
            'id_produk' => $idProduk,
        ];
        if (!empty($durasi)) {
            $params['durasi'] = $durasi;
        }
        if (!empty($email)) {
            $params['email'] = $email;
        }
        if (!empty($catatan)) {
            $params['catatan'] = $catatan;
        }
        return $this->request('order', $params);
    }

    public function getRiwayat()
    {
        return $this->request('get_riwayat');
    }
}