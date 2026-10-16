<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopupRequest extends Model
{
    protected $fillable = [
        'user_id', 'invoice_number', 'amount', 'payment_method',
        'status', 'qris_url', 'order_id_gateway'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

