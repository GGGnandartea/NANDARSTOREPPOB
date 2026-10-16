<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'whatsapp', 'role', 'balance', 'is_active',
        'is_whatsapp_verified', 'wa_verification_code', 'whatsapp_verified_at',
        'otp_code', 'otp_trigger_code', 'otp_expires_at'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'whatsapp_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_whatsapp_verified' => 'boolean',
        ];
    }

    // Relasi untuk menarik data Mutasi Saldo
    public function transactionLogs() {
        return $this->hasMany(TransactionLog::class)->orderBy('id', 'desc');
    }

    // Relasi untuk menarik data Riwayat Pembelian
    public function orders() {
        return $this->hasMany(Order::class)->orderBy('id', 'desc');
    }
}