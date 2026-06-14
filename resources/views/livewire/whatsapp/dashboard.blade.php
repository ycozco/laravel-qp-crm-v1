<div class="crm-content">
    <x-mary-header title="WhatsApp" subtitle="{{ $tenant?->name ?? 'Sin tenant asignado' }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Configurar" icon="o-cog-6-tooth" link="{{ route('laravel-crm.whatsapp.settings', ['tenant' => $tenant?->id]) }}" class="btn-outline" responsive />
            <x-mary-button label="Conversaciones" icon="o-chat-bubble-left-right" link="{{ route('laravel-crm.whatsapp.conversations.index', ['tenant' => $tenant?->id]) }}" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>

    @include('laravel-crm::livewire.whatsapp.partials.nav')

    @unless($tenant)
        <x-mary-alert icon="o-exclamation-triangle" class="alert-warning mb-4">
            Este usuario no tiene un tenant CRM asignado. Crea uno desde Configuracion para habilitar el modulo o ejecuta el seeder demo en server-test.
        </x-mary-alert>
    @endunless

    <div class="grid lg:grid-cols-4 gap-4 mb-6">
        <x-mary-stat title="Estado" value="{{ ucfirst($account?->status ?? 'pendiente') }}" icon="o-signal" color="{{ $account?->status === 'connected' ? 'text-success' : 'text-warning' }}" description="{{ $account?->display_name ?? 'Cuenta Meta demo pendiente' }}" class="shadow-sm" />
        <x-mary-stat title="Conversaciones" value="{{ $conversationCount }}" icon="o-chat-bubble-left-right" color="text-primary" description="{{ $openConversationCount }} abiertas" class="shadow-sm" />
        <x-mary-stat title="Mensajes" value="{{ $messageCount }}" icon="o-envelope" color="text-info" description="Entrantes y salientes demo" class="shadow-sm" />
        <x-mary-stat title="Token Meta" value="{{ $account?->maskedToken() ?? 'Pendiente' }}" icon="o-lock-closed" color="text-secondary" description="Nunca se muestra completo" class="shadow-sm" />
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
        <x-mary-card title="Cuenta WhatsApp Business" shadow separator>
            <dl class="grid gap-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-base-content/60">Telefono</dt><dd class="font-medium">{{ $account?->phone_number ?? 'No configurado' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-base-content/60">Phone number ID</dt><dd class="font-medium">{{ $account?->phone_number_id ?? 'No configurado' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-base-content/60">Business account ID</dt><dd class="font-medium">{{ $account?->business_account_id ?? 'No configurado' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-base-content/60">App ID</dt><dd class="font-medium">{{ $account?->app_id ?? 'No configurado' }}</dd></div>
            </dl>
        </x-mary-card>

        <x-mary-card title="Ultimos eventos webhook" shadow separator>
            <div class="space-y-3">
                @forelse($latestEvents as $event)
                    <div class="flex items-center justify-between gap-4 border-b border-base-content/10 pb-3 last:border-0 last:pb-0">
                        <div>
                            <div class="font-medium">{{ $event->event_type }}</div>
                            <div class="text-xs text-base-content/60">{{ $event->received_at?->diffForHumans() }}</div>
                        </div>
                        <x-mary-badge value="{{ $event->signature_valid ? 'firma ok' : 'sin firma' }}" class="{{ $event->signature_valid ? 'badge-success' : 'badge-warning' }}" />
                    </div>
                @empty
                    <p class="text-sm text-base-content/60">Sin eventos recibidos todavia.</p>
                @endforelse
            </div>
        </x-mary-card>
    </div>
</div>
