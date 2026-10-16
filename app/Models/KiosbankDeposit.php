<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KiosbankDeposit extends Model
{
    protected $table = 'kiosbank_deposits';

    protected $fillable = [
        'invoice_number',
        'amount',
        'payment_method',
        'status',
        'bank_account_name',
        'bank_account_number',
        'unique_code',
        'total_payment',
        'notes',
        'proof_image',
    ];
}

