<?php

namespace VentureDrake\LaravelCrm\Livewire\Whatsapp\Concerns;

use Illuminate\Database\Eloquent\Collection;
use VentureDrake\LaravelCrm\Models\Tenant;
use VentureDrake\LaravelCrm\Models\TenantUser;

trait ResolvesWhatsappTenant
{
    protected ?Collection $resolvedTenants = null;

    protected function availableTenants(): Collection
    {
        if ($this->resolvedTenants instanceof Collection) {
            return $this->resolvedTenants;
        }

        $user = auth()->user();

        if (! $user) {
            return $this->resolvedTenants = new Collection;
        }

        return $this->resolvedTenants = Tenant::query()
            ->forUser($user)
            ->orderBy('name')
            ->get();
    }

    protected function currentTenant(): ?Tenant
    {
        $tenants = $this->availableTenants();

        if ($tenants->isEmpty()) {
            return null;
        }

        $selectedId = property_exists($this, 'tenantId') ? $this->tenantId : null;

        if ($selectedId) {
            $tenant = $tenants->firstWhere('id', (int) $selectedId);

            if ($tenant) {
                return $tenant;
            }
        }

        return $tenants->first();
    }

    protected function syncSelectedTenant(): void
    {
        if (! property_exists($this, 'tenantId')) {
            return;
        }

        $tenant = $this->currentTenant();

        if ($tenant) {
            $this->tenantId = $tenant->id;
        }
    }

    protected function currentTenantRole(): ?string
    {
        $tenant = $this->currentTenant();
        $user = auth()->user();

        if (! $tenant || ! $user) {
            return null;
        }

        return TenantUser::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->getKey())
            ->value('role');
    }

    protected function canManageCurrentTenant(): bool
    {
        return in_array($this->currentTenantRole(), ['owner', 'admin', 'manager'], true);
    }
}
