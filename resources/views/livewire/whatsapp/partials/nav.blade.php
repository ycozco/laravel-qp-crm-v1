<div class="mb-5 rounded-2xl border border-base-content/10 bg-base-100 p-4 shadow-sm">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
    <div class="tabs tabs-boxed bg-base-200">
        <a href="{{ route('laravel-crm.whatsapp.index', ['tenant' => $tenant?->id]) }}" class="tab {{ request()->routeIs('laravel-crm.whatsapp.index') ? 'tab-active' : '' }}">Resumen</a>
        <a href="{{ route('laravel-crm.whatsapp.settings', ['tenant' => $tenant?->id]) }}" class="tab {{ request()->routeIs('laravel-crm.whatsapp.settings') ? 'tab-active' : '' }}">Conexion</a>
        <a href="{{ route('laravel-crm.whatsapp.conversations.index', ['tenant' => $tenant?->id]) }}" class="tab {{ request()->routeIs('laravel-crm.whatsapp.conversations.*') ? 'tab-active' : '' }}">Conversaciones</a>
        <a href="{{ route('laravel-crm.whatsapp.events', ['tenant' => $tenant?->id]) }}" class="tab {{ request()->routeIs('laravel-crm.whatsapp.events') ? 'tab-active' : '' }}">Webhooks</a>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        @if(isset($availableTenants) && $availableTenants->count() > 1)
            <label class="form-control">
                <div class="label py-0 pb-1">
                    <span class="label-text text-xs uppercase tracking-wide text-base-content/60">Tenant</span>
                </div>
                <select wire:model.live="tenantId" class="select select-bordered select-sm min-w-56">
                    @foreach($availableTenants as $availableTenant)
                        <option value="{{ $availableTenant->id }}">{{ $availableTenant->name }}</option>
                    @endforeach
                </select>
            </label>
        @elseif(isset($tenant) && $tenant)
            <div class="text-sm text-base-content/70">
                Tenant activo: <span class="font-medium text-base-content">{{ $tenant->name }}</span>
            </div>
        @endif

        @if(isset($tenantRole) && $tenantRole)
            <x-mary-badge value="Rol {{ ucfirst($tenantRole) }}" class="badge-ghost" />
        @endif
    </div>
    </div>
</div>
