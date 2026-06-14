<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappConversation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'whatsapp_conversations';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TenantWhatsappAccount::class, 'tenant_whatsapp_account_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class);
    }
}
