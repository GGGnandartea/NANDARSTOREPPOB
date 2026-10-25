<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Setting;

class PremkuApiService
{
    protected $baseUrl = 'https://premku.com/api/';
    protected $apiKey;

    public function __construct()
    {
        // Mengambil API Key dari database (diatur via panel Admin)
        $this->apiKey = Setting::where('key', 'premku_api_key')->value('value');
    }

    public function profile()
    {
        return Http::post($this->baseUrl . 'profile', [
            'api_key' => $this->apiKey
        ])->json();
    }

    public function getProducts(int $timeout = 30)
    {
        if (!$this->apiKey) {
            return [];
        }

        return Http::connectTimeout(min(3, $timeout))->timeout($timeout)->post($this->baseUrl . 'products', [
            'api_key' => $this->apiKey
        ])->json();
    }

    public function createOrder($productId, $qty, $refId)
    {
        return Http::post($this->baseUrl . 'order', [
            'api_key' => $this->apiKey,
            'product_id' => $productId,
            'qty' => $qty,
            'ref_id' => $refId
        ])->json();
    }

    public function checkStatus($invoice)
    {
        return Http::post($this->baseUrl . 'status', [
            'api_key' => $this->apiKey,
            'invoice' => $invoice
        ])->json();
    }

    public function checkStock($productId)
    {
        return Http::post($this->baseUrl . 'stock', [
            'api_key' => $this->apiKey,
            'product_id' => $productId
        ])->json();
    }

    public function createDeposit($amount)
    {
        return Http::post($this->baseUrl . 'pay', [
            'api_key' => $this->apiKey,
            'amount' => (int) $amount
        ])->json();
    }

    public function checkDepositStatus($invoice)
    {
        return Http::post($this->baseUrl . 'pay_status', [
            'api_key' => $this->apiKey,
            'invoice' => $invoice
        ])->json();
    }

    public function cancelDeposit($invoice)
    {
        return Http::post($this->baseUrl . 'cancel_pay', [
            'api_key' => $this->apiKey,
            'invoice' => $invoice
        ])->json();
    }

    /**
     * Alight Motion API - Buy Bulk (Generate masal, max 200)
     */
    public function amBuyBulk($qty, $refId = null)
    {
        $payload = [
            'api_key' => $this->apiKey,
            'action'  => 'buy_bulk',
            'qty'     => (int) $qty,
        ];
        if ($refId) {
            $payload['ref_id'] = $refId;
        }

        return Http::post($this->baseUrl . 'am', $payload)->json();
    }

    /**
     * Alight Motion API - Buy Single (Kirim link verifikasi ke email)
     */
    public function amBuySingle($email)
    {
        return Http::post($this->baseUrl . 'am', [
            'api_key' => $this->apiKey,
            'action'  => 'buy_single',
            'email'   => $email,
        ])->json();
    }

    /**
     * Alight Motion API - Verify Single (Verifikasi link aktivasi email)
     */
    public function amVerifySingle($invoice, $email, $link)
    {
        return Http::post($this->baseUrl . 'am', [
            'api_key' => $this->apiKey,
            'action'  => 'verify_single',
            'invoice' => $invoice,
            'email'   => $email,
            'link'    => $link,
        ])->json();
    }

    /**
     * Alight Motion API - Cancel Single (Batalkan order pending)
     */
    public function amCancelSingle($invoice)
    {
        return Http::post($this->baseUrl . 'am', [
            'api_key' => $this->apiKey,
            'action'  => 'cancel_single',
            'invoice' => $invoice,
        ])->json();
    }
}
