<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantWhatsappAccount extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'connected_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'tenant_whatsapp_accounts';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class);
    }

    public function maskedToken(): string
    {
        return $this->access_token_encrypted ? 'Configurado y cifrado' : 'Pendiente';
    }

    public function maskedVerifyToken(): string
    {
        if (! $this->webhook_verify_token) {
            return 'Pendiente';
        }

        $token = (string) $this->webhook_verify_token;

        if (strlen($token) <= 4) {
            return str_repeat('*', strlen($token));
        }

        return str_repeat('*', max(strlen($token) - 4, 0)).substr($token, -4);
    }
}
