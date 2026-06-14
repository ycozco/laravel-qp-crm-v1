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
}
