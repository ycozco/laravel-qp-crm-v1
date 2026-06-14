<?php

namespace VentureDrake\LaravelCrm\Livewire\Whatsapp\Concerns;

use VentureDrake\LaravelCrm\Models\Tenant;

trait ResolvesWhatsappTenant
{
    protected function currentTenant(): ?Tenant
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return Tenant::query()
            ->forUser($user)
            ->orderBy('name')
            ->first();
    }
}
