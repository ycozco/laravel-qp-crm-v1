<?php

namespace VentureDrake\LaravelCrm\Livewire\Whatsapp;

use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Whatsapp\Concerns\ResolvesWhatsappTenant;
use VentureDrake\LaravelCrm\Models\WhatsappConversation;

class WhatsappConversationShow extends Component
{
    use ResolvesWhatsappTenant;

    public WhatsappConversation $conversation;

    public function mount(WhatsappConversation $conversation): void
    {
        $tenant = $this->currentTenant();

        abort_if(! $tenant || $conversation->tenant_id !== $tenant->id, 404);

        $this->conversation = $conversation;
    }

    public function render()
    {
        return view('laravel-crm::livewire.whatsapp.conversation-show', [
            'messages' => $this->conversation->messages()->where('tenant_id', $this->conversation->tenant_id)->oldest('sent_at')->get(),
        ]);
    }
}
