<?php

namespace VentureDrake\LaravelCrm\Livewire\Whatsapp;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VentureDrake\LaravelCrm\Livewire\Whatsapp\Concerns\ResolvesWhatsappTenant;
use VentureDrake\LaravelCrm\Models\WhatsappConversation;

class WhatsappConversationIndex extends Component
{
    use ResolvesWhatsappTenant, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $tenant = $this->currentTenant();

        $conversations = WhatsappConversation::query()
            ->withCount('messages')
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id), fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $search) {
                    $search->where('contact_name', 'like', "%{$this->search}%")
                        ->orWhere('contact_phone', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, fn (Builder $query) => $query->where('status', $this->status))
            ->latest('last_message_at')
            ->paginate(15);

        return view('laravel-crm::livewire.whatsapp.conversation-index', [
            'tenant' => $tenant,
            'conversations' => $conversations,
        ]);
    }
}
