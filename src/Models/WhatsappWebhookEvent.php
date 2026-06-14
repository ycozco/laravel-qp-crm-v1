<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappWebhookEvent extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload_json' => 'array',
        'signature_valid' => 'boolean',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'whatsapp_webhook_events';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TenantWhatsappAccount::class, 'tenant_whatsapp_account_id');
    }
}
