<div class="crm-content">
    <x-mary-header title="Configuracion WhatsApp" subtitle="{{ $tenant?->name ?? 'Sin tenant asignado' }}" progress-indicator />

    @include('laravel-crm::livewire.whatsapp.partials.nav')

    <x-mary-card title="Conexion Meta por tenant" shadow separator>
        <div class="grid lg:grid-cols-2 gap-5">
            <div class="space-y-4">
                <x-mary-alert icon="o-information-circle" class="alert-info">
                    Esta pantalla es solo lectura en el MVP. Aqui se conectaran Embedded Signup, envio real y webhook publico.
                </x-mary-alert>

                <dl class="grid gap-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-base-content/60">Tenant</dt><dd class="font-medium">{{ $tenant?->name ?? 'No disponible' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-base-content/60">Estado</dt><dd><x-mary-badge value="{{ ucfirst($account?->status ?? 'pending') }}" class="{{ $account?->status === 'connected' ? 'badge-success' : 'badge-warning' }}" /></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-base-content/60">Token</dt><dd class="font-medium">{{ $account?->maskedToken() ?? 'Pendiente' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-base-content/60">Verify token</dt><dd class="font-medium">{{ $account?->webhook_verify_token ? 'Configurado' : 'Pendiente' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-md bg-base-200 p-4 text-sm">
                <h3 class="font-semibold mb-3">Donde configurar las credenciales luego</h3>
                <p class="mb-2">Las claves reales deben persistirse por tenant en <code>tenant_whatsapp_accounts</code>:</p>
                <ul class="list-disc pl-5 space-y-1 text-base-content/75">
                    <li><code>app_id</code></li>
                    <li><code>business_account_id</code></li>
                    <li><code>phone_number_id</code></li>
                    <li><code>access_token_encrypted</code> cifrado</li>
                    <li><code>webhook_verify_token</code></li>
                </ul>
            </div>
        </div>
    </x-mary-card>
</div>
