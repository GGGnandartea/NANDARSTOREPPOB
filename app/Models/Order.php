<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'invoice_number', 'user_id', 'buyer_name', 'product_id', 'product_name', 'quantity', 'product_type', 'provider',
        'price', 'discount', 'total_amount', 'payment_status', 'order_status', 'account_details',
        'email', 'whatsapp', 'voucher_code', 'discount_amount', 'sn_or_account',
        'api_trx_id', 'notes'
    ];

    protected $appends = ['provider'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function getProviderAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }

        if ($this->product_type === 'ppob') {
            return 'Kiosbank';
        }

        if ($this->product_type === 'api') {
            $apiProdId = $this->product ? ($this->product->api_product_id ?? '') : '';
            if (str_starts_with($apiProdId, 'PREFLIX-')) {
                return 'Preflix';
            }
            if (str_starts_with($apiProdId, 'PREMKU-')) {
                return 'Premku';
            }
            if (str_contains($this->api_trx_id ?? '', 'PREFLIX') || str_contains(strtolower($this->notes ?? ''), 'preflix')) {
                return 'Preflix';
            }
            if (str_contains($this->api_trx_id ?? '', 'PREMKU') || str_contains(strtolower($this->notes ?? ''), 'premku')) {
                return 'Premku';
            }
            if ($this->product_name) {
                $matched = Product::where('name', $this->product_name)->first();
                if ($matched && str_starts_with($matched->api_product_id ?? '', 'PREFLIX-')) return 'Preflix';
                if ($matched && str_starts_with($matched->api_product_id ?? '', 'PREMKU-')) return 'Premku';
            }
            return 'API';
        }

        if ($this->product_type === 'local') {
            if ($this->product && ($this->product->process_type ?? 'auto') === 'manual') {
                return 'Manual Admin';
            }
            return 'Otomatis';
        }

        return 'Lokal';
    }
}