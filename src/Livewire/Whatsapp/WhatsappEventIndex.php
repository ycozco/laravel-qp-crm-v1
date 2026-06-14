<?php

namespace VentureDrake\LaravelCrm\Livewire\Whatsapp;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VentureDrake\LaravelCrm\Livewire\Whatsapp\Concerns\ResolvesWhatsappTenant;
use VentureDrake\LaravelCrm\Models\WhatsappWebhookEvent;

class WhatsappEventIndex extends Component
{
    use ResolvesWhatsappTenant, WithPagination;

    #[Url(as: 'tenant')]
    public ?int $tenantId = null;

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

    public function updatedTenantId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->syncSelectedTenant();

        $tenant = $this->currentTenant();

        $events = WhatsappWebhookEvent::query()
            ->when($tenant, fn (Builder $query) => $query->where('tenant_id', $tenant->id), fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $search) {
                    $search->where('event_type', 'like', "%{$this->search}%")
                        ->orWhere('field', 'like', "%{$this->search}%")
                        ->orWhere('phone_number_id', 'like', "%{$this->search}%")
                        ->orWhere('meta_object_id', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, fn (Builder $query) => $query->where('processing_status', $this->status))
            ->latest('received_at')
            ->paginate(15);

        return view('laravel-crm::livewire.whatsapp.event-index', [
            'tenant' => $tenant,
            'availableTenants' => $this->availableTenants(),
            'tenantRole' => $this->currentTenantRole(),
            'canManage' => $this->canManageCurrentTenant(),
            'events' => $events,
        ]);
    }
}
