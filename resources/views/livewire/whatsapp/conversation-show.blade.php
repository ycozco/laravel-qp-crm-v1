<div class="crm-content">
    <x-mary-header title="{{ $conversation->contact_name ?? $conversation->contact_phone }}" subtitle="{{ $conversation->contact_phone }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Volver" icon="o-arrow-left" link="{{ url(route('laravel-crm.whatsapp.conversations.index')) }}" class="btn-outline" responsive />
        </x-slot:actions>
    </x-mary-header>

    @include('laravel-crm::livewire.whatsapp.partials.nav')

    <x-mary-card title="Mensajes" shadow separator>
        <div class="space-y-4">
            @forelse($messages as $message)
                <div class="chat {{ $message->direction === 'outbound' ? 'chat-end' : 'chat-start' }}">
                    <div class="chat-header text-xs text-base-content/60">
                        {{ ucfirst($message->direction) }} · {{ $message->sent_at?->format('Y-m-d H:i') }}
                    </div>
                    <div class="chat-bubble {{ $message->direction === 'outbound' ? 'chat-bubble-primary' : '' }}">
                        {{ $message->body }}
                    </div>
                    <div class="chat-footer text-xs text-base-content/60">{{ ucfirst($message->status) }}</div>
                </div>
            @empty
                <p class="text-sm text-base-content/60">No hay mensajes registrados.</p>
            @endforelse
        </div>
    </x-mary-card>
</div>
