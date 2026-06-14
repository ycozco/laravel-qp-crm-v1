<div class="crm-content">
    <x-mary-header title="{{ $conversation->contact_name ?? $conversation->contact_phone }}" subtitle="{{ ($tenant?->name ? $tenant->name.' - ' : '').$conversation->contact_phone }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Volver" icon="o-arrow-left" link="{{ route('laravel-crm.whatsapp.conversations.index', ['tenant' => $tenant?->id]) }}" class="btn-outline" responsive />
        </x-slot:actions>
    </x-mary-header>

    @include('laravel-crm::livewire.whatsapp.partials.nav')

    <div class="grid gap-4 md:grid-cols-3 mb-4">
        <x-mary-stat title="Contacto" value="{{ $conversation->contact_name ?? 'Sin nombre' }}" icon="o-user" color="text-primary" description="{{ $conversation->contact_phone }}" class="shadow-sm" />
        <x-mary-stat title="Estado" value="{{ ucfirst($conversation->status) }}" icon="o-chat-bubble-left-right" color="{{ $conversation->status === 'open' ? 'text-success' : 'text-warning' }}" description="Conversacion actual" class="shadow-sm" />
        <x-mary-stat title="Mensajes" value="{{ $messages->count() }}" icon="o-envelope" color="text-info" description="Cargados en esta vista" class="shadow-sm" />
    </div>

    <x-mary-card title="Mensajes" shadow separator>
        <div class="space-y-4">
            @forelse($messages as $message)
                <div class="chat {{ $message->direction === 'outbound' ? 'chat-end' : 'chat-start' }}">
                    <div class="chat-header text-xs text-base-content/60">
                        {{ ucfirst($message->direction) }} - {{ $message->sent_at?->format('Y-m-d H:i') }}
                    </div>
                    <div class="chat-bubble {{ $message->direction === 'outbound' ? 'chat-bubble-primary' : '' }}">
                        {{ $message->body }}
                    </div>
                    <div class="chat-footer text-xs text-base-content/60">{{ ucfirst($message->status) }}</div>
                    @if($message->error_code || $message->error_title || $message->error_details)
                        <div class="mt-1 max-w-xl text-xs text-error">
                            {{ $message->error_code }} {{ $message->error_title }} {{ $message->error_details }}
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-base-content/60">No hay mensajes registrados.</p>
            @endforelse
        </div>
    </x-mary-card>
</div>
