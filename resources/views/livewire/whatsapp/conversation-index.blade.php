<div class="crm-content">
    <x-mary-header title="Conversaciones WhatsApp" subtitle="{{ $tenant?->name ?? 'Sin tenant asignado' }}" progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="Buscar contacto o telefono..." wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>
        <x-slot:actions>
            <select wire:model.live="status" class="select select-bordered select-sm">
                <option value="">Todos</option>
                <option value="open">Abiertas</option>
                <option value="pending">Pendientes</option>
                <option value="closed">Cerradas</option>
            </select>
        </x-slot:actions>
    </x-mary-header>

    @include('laravel-crm::livewire.whatsapp.partials.nav')

    <x-mary-card shadow>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Contacto</th>
                        <th>Telefono</th>
                        <th>Estado</th>
                        <th>Mensajes</th>
                        <th>Ultimo mensaje</th>
                        <th class="text-right">Accion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conversations as $conversation)
                        <tr>
                            <td class="font-medium">{{ $conversation->contact_name ?? 'Sin nombre' }}</td>
                            <td>{{ $conversation->contact_phone }}</td>
                            <td><x-mary-badge value="{{ ucfirst($conversation->status) }}" class="{{ $conversation->status === 'open' ? 'badge-success' : 'badge-ghost' }}" /></td>
                            <td>{{ $conversation->messages_count }}</td>
                            <td>{{ $conversation->last_message_at?->diffForHumans() ?? 'Sin mensajes' }}</td>
                            <td class="text-right">
                                <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.whatsapp.conversations.show', $conversation)) }}" class="btn-sm btn-square btn-outline" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-base-content/60 py-8">No hay conversaciones para este tenant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $conversations->links() }}
        </div>
    </x-mary-card>
</div>
