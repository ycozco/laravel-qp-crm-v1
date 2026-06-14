<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $guarded = ['id'];

    public function getTable()
    {
        return config('laravel-crm.db_table_prefix').'tenants';
    }

    public function scopeForUser(Builder $query, $user): Builder
    {
        return $query->whereHas('users', fn (Builder $users) => $users->whereKey($user->getKey()));
    }

    public function users(): BelongsToMany
    {
        $userClass = class_exists('\App\Models\User') ? \App\Models\User::class : \App\User::class;

        return $this->belongsToMany($userClass, config('laravel-crm.db_table_prefix').'tenant_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function whatsappAccounts(): HasMany
    {
        return $this->hasMany(TenantWhatsappAccount::class);
    }

    public function whatsappConversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class);
    }

    public function whatsappWebhookEvents(): HasMany
    {
        return $this->hasMany(WhatsappWebhookEvent::class);
    }

    public function whatsappTemplates(): HasMany
    {
        return $this->hasMany(WhatsappTemplate::class);
    }
}
