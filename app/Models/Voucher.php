<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'max_uses', 'used', 'expired_at', 'is_active'
    ];

    protected $casts = [
        'expired_at' => 'date',
        'is_active' => 'boolean',
    ];
}