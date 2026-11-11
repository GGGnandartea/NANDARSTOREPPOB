<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ResellerApiClient extends Model
{
    protected $fillable = [
        'user_id',
        'api_key',
        'secret_key_encrypted',
        'secret_key_hint',
        'webhook_url',
        'webhook_secret_encrypted',
        'webhook_secret_hint',
        'ip_whitelist',
        'is_enabled',
        'admin_suspended',
        'admin_suspend_reason',
        'custom_rate_limit',
        'last_used_at',
        'last_used_ip',
        'total_requests',
        'total_orders',
        'rotated_at',
    ];

    protected $hidden = [
        'secret_key_encrypted',
        'webhook_secret_encrypted',
    ];

    protected $casts = [
        'is_enabled'        => 'boolean',
        'admin_suspended'   => 'boolean',
        'custom_rate_limit' => 'integer',
        'total_requests'    => 'integer',
        'total_orders'      => 'integer',
        'last_used_at'      => 'datetime',
        'rotated_at'        => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plainSecretKey(): ?string
    {
        if (empty($this->secret_key_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->secret_key_encrypted);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function plainWebhookSecret(): ?string
    {
        if (empty($this->webhook_secret_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->webhook_secret_encrypted);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function parsedIpWhitelist(): array
    {
        if (empty($this->ip_whitelist)) {
            return [];
        }

        $parts = preg_split('/[\r\n,;]+/', (string) $this->ip_whitelist) ?: [];
        $clean = [];
        foreach ($parts as $part) {
            $item = trim($part);
            if ($item !== '') {
                $clean[] = $item;
            }
        }

        return array_values(array_unique($clean));
    }
}
