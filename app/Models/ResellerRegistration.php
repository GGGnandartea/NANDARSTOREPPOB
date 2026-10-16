<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerRegistration extends Model
{
    protected $fillable = [
        'user_id',
        'invoice_number',
        'order_id_gateway',
        'amount',
        'status',
        'qris_url',
        'payment_method',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

