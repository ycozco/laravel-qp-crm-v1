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

    <div class="grid gap-4 md:grid-cols-3 mb-4">
        <x-mary-card title="Vista" shadow separator>
            <p class="text-sm text-base-content/70">Listado de conversaciones por tenant con filtro por estado y busqueda por contacto o telefono.</p>
        </x-mary-card>
        <x-mary-card title="Filtro actual" shadow separator>
            <p class="text-sm text-base-content/70">{{ $status ? 'Estado: '.ucfirst($status) : 'Mostrando todos los estados.' }}</p>
        </x-mary-card>
        <x-mary-card title="Busqueda" shadow separator>
            <p class="text-sm text-base-content/70">{{ filled($search) ? 'Busqueda activa: '.$search : 'Sin texto de busqueda aplicado.' }}</p>
        </x-mary-card>
    </div>

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
                                <x-mary-button icon="o-eye" link="{{ route('laravel-crm.whatsapp.conversations.show', ['conversation' => $conversation, 'tenant' => $tenant?->id]) }}" class="btn-sm btn-square btn-outline" />
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
