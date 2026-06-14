<div class="crm-content">
    <x-mary-header title="Eventos webhook WhatsApp" subtitle="{{ $tenant?->name ?? 'Sin tenant asignado' }}" progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="Buscar evento..." wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>
        <x-slot:actions>
            <select wire:model.live="status" class="select select-bordered select-sm">
                <option value="">Todos</option>
                <option value="received">Recibidos</option>
                <option value="processed">Procesados</option>
                <option value="error">Con error</option>
            </select>
        </x-slot:actions>
    </x-mary-header>

    @include('laravel-crm::livewire.whatsapp.partials.nav')

    <x-mary-card shadow>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Campo</th>
                        <th>Telefono ID</th>
                        <th>Meta object</th>
                        <th>Firma</th>
                        <th>Estado</th>
                        <th>Procesados</th>
                        <th>Recibido</th>
                        <th>Procesado</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td class="font-medium">{{ $event->event_type }}</td>
                            <td>{{ $event->field ?? '-' }}</td>
                            <td>{{ $event->phone_number_id ?? '-' }}</td>
                            <td>{{ $event->meta_object_id ?? 'n/a' }}</td>
                            <td><x-mary-badge value="{{ $event->signature_valid ? 'Valida' : 'Pendiente' }}" class="{{ $event->signature_valid ? 'badge-success' : 'badge-warning' }}" /></td>
                            <td><x-mary-badge value="{{ ucfirst($event->processing_status ?? 'received') }}" class="{{ $event->processing_status === 'error' ? 'badge-error' : 'badge-ghost' }}" /></td>
                            <td>{{ $event->processed_count ?? 0 }}</td>
                            <td>{{ $event->received_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $event->processed_at?->format('Y-m-d H:i') ?? 'Pendiente' }}</td>
                            <td class="max-w-xs truncate">{{ $event->error_message ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-base-content/60 py-8">No hay eventos para este tenant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $events->links() }}
        </div>
    </x-mary-card>
</div>
