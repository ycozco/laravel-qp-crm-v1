<?php

namespace VentureDrake\LaravelCrm\Livewire\Whatsapp;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Whatsapp\Concerns\ResolvesWhatsappTenant;
use VentureDrake\LaravelCrm\Models\TenantWhatsappAccount;

class WhatsappSettings extends Component
{
    use ResolvesWhatsappTenant;

    public function render()
    {
        $tenant = $this->currentTenant();

        return view('laravel-crm::livewire.whatsapp.settings', [
            'tenant' => $tenant,
            'account' => $tenant ? TenantWhatsappAccount::where('tenant_id', $tenant->id)->latest()->first() : null,
            'callbackUrl' => url(config('meta-whatsapp.webhook.path', '/webhooks/meta/whatsapp')),
            'signatureRequired' => (bool) config('meta-whatsapp.webhook.require_signature'),
        ]);
    }
}
