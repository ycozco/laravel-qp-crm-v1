<?php

namespace VentureDrake\LaravelCrm\Livewire\Whatsapp;

use Livewire\Attributes\Url;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Whatsapp\Concerns\ResolvesWhatsappTenant;
use VentureDrake\LaravelCrm\Models\TenantWhatsappAccount;
use VentureDrake\LaravelCrm\Models\WhatsappConversation;
use VentureDrake\LaravelCrm\Models\WhatsappMessage;
use VentureDrake\LaravelCrm\Models\WhatsappWebhookEvent;

class WhatsappDashboard extends Component
{
    use ResolvesWhatsappTenant;

    #[Url(as: 'tenant')]
    public ?int $tenantId = null;

    public function render()
    {
        $this->syncSelectedTenant();

        $tenant = $this->currentTenant();

        return view('laravel-crm::livewire.whatsapp.dashboard', [
            'tenant' => $tenant,
            'availableTenants' => $this->availableTenants(),
            'tenantRole' => $this->currentTenantRole(),
            'canManage' => $this->canManageCurrentTenant(),
            'account' => $tenant ? TenantWhatsappAccount::where('tenant_id', $tenant->id)->latest()->first() : null,
            'conversationCount' => $tenant ? WhatsappConversation::where('tenant_id', $tenant->id)->count() : 0,
            'openConversationCount' => $tenant ? WhatsappConversation::where('tenant_id', $tenant->id)->where('status', 'open')->count() : 0,
            'messageCount' => $tenant ? WhatsappMessage::where('tenant_id', $tenant->id)->count() : 0,
            'latestEvents' => $tenant ? WhatsappWebhookEvent::where('tenant_id', $tenant->id)->latest('received_at')->limit(5)->get() : collect(),
        ]);
    }
}
