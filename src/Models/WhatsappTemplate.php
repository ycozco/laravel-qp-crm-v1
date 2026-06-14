<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappTemplate extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload_json' => 'array',
    ];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'whatsapp_templates';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
