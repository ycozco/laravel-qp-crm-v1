<?php

namespace VentureDrake\LaravelCrm\Livewire\Whatsapp;

use Livewire\Attributes\Url;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Whatsapp\Concerns\ResolvesWhatsappTenant;
use VentureDrake\LaravelCrm\Models\WhatsappConversation;

class WhatsappConversationShow extends Component
{
    use ResolvesWhatsappTenant;

    #[Url(as: 'tenant')]
    public ?int $tenantId = null;

    public WhatsappConversation $conversation;

    public function mount(WhatsappConversation $conversation): void
    {
        $this->tenantId ??= $conversation->tenant_id;

        $tenant = $this->currentTenant();

        abort_if(! $tenant || $conversation->tenant_id !== $tenant->id, 404);

        $this->conversation = $conversation;
    }

    public function render()
    {
        return view('laravel-crm::livewire.whatsapp.conversation-show', [
            'tenant' => $this->currentTenant(),
            'availableTenants' => $this->availableTenants(),
            'tenantRole' => $this->currentTenantRole(),
            'canManage' => $this->canManageCurrentTenant(),
            'messages' => $this->conversation->messages()->where('tenant_id', $this->conversation->tenant_id)->oldest('sent_at')->get(),
        ]);
    }
}
