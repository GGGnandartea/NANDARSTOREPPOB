<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PpobProduct extends Model
{
    protected $table = 'ppob_products';

    protected $fillable = [
        'code',
        'name',
        'category',
        'operator',
        'prefix_id',
        'base_price',
        'margin',
        'price',
        'reseller_price',
        'status',
        'provider',
        'description',
        'image',
        'price_synced_mode',
        'price_synced_at',
    ];

    public function isMobileProduct(): bool
    {
        return $this->provider === 'kiosbank'
            && in_array($this->category, ['pulsa', 'data', 'masa_aktif', 'telepon'], true);
    }

    /**
     * Scope untuk produk yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope untuk kategori
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope untuk operator
     */
    public function scopeOperator($query, string $operator)
    {
        return $query->where('operator', $operator);
    }
}

