<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerApiLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'endpoint',
        'method',
        'ip_address',
        'status_code',
        'ref_id',
        'invoice_number',
        'error_code',
        'response_time_ms',
        'summary',
        'created_at',
    ];

    protected $casts = [
        'status_code'      => 'integer',
        'response_time_ms' => 'integer',
        'created_at'       => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
