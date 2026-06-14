<div class="crm-content">
    <x-mary-header title="Configuracion WhatsApp" subtitle="{{ $tenant?->name ?? 'Sin tenant asignado' }}" progress-indicator />

    @include('laravel-crm::livewire.whatsapp.partials.nav')

    @if (session('whatsapp_settings_success'))
        <x-mary-alert icon="o-check-circle" class="alert-success mb-4">
            {{ session('whatsapp_settings_success') }}
        </x-mary-alert>
    @endif

    <div class="grid gap-4 xl:grid-cols-3">
        <x-mary-card title="Estado actual" shadow separator class="xl:col-span-1">
            <div class="space-y-4">
                <x-mary-alert icon="o-information-circle" class="alert-info">
                    El webhook publico ya acepta verificacion GET y eventos POST. Esta vista ya permite crear y editar la cuenta por tenant. El envio real y Embedded Signup siguen fuera de esta fase.
                </x-mary-alert>

                <dl class="grid gap-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-base-content/60">Tenant</dt><dd class="font-medium">{{ $tenant?->name ?? 'No disponible' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-base-content/60">Estado</dt><dd><x-mary-badge value="{{ ucfirst($account?->status ?? 'pending') }}" class="{{ $account?->status === 'connected' ? 'badge-success' : 'badge-warning' }}" /></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-base-content/60">Callback URL</dt><dd class="font-medium text-right break-all">{{ $callbackUrl }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-base-content/60">Token</dt><dd class="font-medium">{{ $account?->maskedToken() ?? 'Pendiente' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-base-content/60">Verify token</dt><dd class="font-medium">{{ $account?->maskedVerifyToken() ?? 'Pendiente' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-base-content/60">Firma HMAC</dt><dd><x-mary-badge value="{{ $signatureRequired ? 'Obligatoria' : 'Acepta pruebas sin firma' }}" class="{{ $signatureRequired ? 'badge-success' : 'badge-warning' }}" /></dd></div>
                </dl>

                <div class="rounded-md bg-base-200 p-4 text-sm">
                    <h3 class="font-semibold mb-3">Persistencia necesaria</h3>
                    <ul class="list-disc pl-5 space-y-1 text-base-content/75">
                        <li><code>app_id</code></li>
                        <li><code>business_account_id</code></li>
                        <li><code>phone_number_id</code></li>
                        <li><code>access_token_encrypted</code> cifrado</li>
                        <li><code>webhook_verify_token</code></li>
                        <li><code>META_WHATSAPP_APP_SECRET</code> en entorno</li>
                    </ul>
                </div>
            </div>
        </x-mary-card>

        <div class="space-y-4 xl:col-span-2">
            <x-mary-card title="Tenant activo" shadow separator>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="text-sm text-base-content/70">
                        @if($creatingTenant ?? false)
                            Estas creando un tenant nuevo. El tenant activo seguira visible en la navegacion hasta que guardes este formulario.
                        @elseif($tenant)
                            Edita el tenant actual o crea uno nuevo si necesitas separar clientes o entornos dentro de la misma cuenta.
                        @else
                            Todavia no existe un tenant asociado a este usuario. Crea uno para habilitar WhatsApp.
                        @endif
                    </div>

                    <div class="flex gap-2">
                        <x-mary-button label="Nuevo tenant" icon="o-building-office-2" wire:click="startTenantCreate" class="btn-outline" />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3 mt-4">
                    <x-mary-input label="Nombre" wire:model.defer="tenant_name" />
                    <x-mary-input label="Slug" wire:model.defer="tenant_slug" hint="Solo letras, numeros y guiones" />
                    <label class="form-control">
                        <div class="label"><span class="label-text">Estado</span></div>
                        <select wire:model.defer="tenant_status" class="select select-bordered">
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </label>
                </div>

                <div class="mt-4 flex justify-end">
                    <x-mary-button label="Guardar tenant" icon="o-check" wire:click="saveTenant" class="btn-primary text-white" />
                </div>
            </x-mary-card>

            <x-mary-card title="Cuentas WhatsApp Business" shadow separator>
                @unless($canManage)
                    <x-mary-alert icon="o-lock-closed" class="alert-warning mb-4">
                        Tu rol actual es de solo lectura para esta configuracion. Necesitas rol owner, admin o manager para editar cuentas.
                    </x-mary-alert>
                @endunless

                <div class="flex items-center justify-between gap-4 mb-4">
                    <p class="text-sm text-base-content/70">Puedes registrar varias cuentas por tenant y elegir cual editar desde la lista.</p>
                    @if($canManage)
                        <x-mary-button label="Nueva cuenta" icon="o-plus" wire:click="startAccountCreate" class="btn-outline" />
                    @endif
                </div>

                <div class="overflow-x-auto mb-5">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cuenta</th>
                                <th>Telefono</th>
                                <th>Estado</th>
                                <th>Verify token</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $row)
                                <tr class="{{ $editingAccountId === $row->id ? 'bg-base-200/60' : '' }}">
                                    <td class="font-medium">{{ $row->display_name }}</td>
                                    <td>{{ $row->phone_number ?? '-' }}</td>
                                    <td><x-mary-badge value="{{ ucfirst($row->status) }}" class="{{ $row->status === 'connected' ? 'badge-success' : 'badge-warning' }}" /></td>
                                    <td>{{ $row->maskedVerifyToken() }}</td>
                                    <td class="text-right">
                                        <div class="inline-flex gap-2">
                                            <x-mary-button icon="o-pencil-square" wire:click="editAccount({{ $row->id }})" class="btn-sm btn-square btn-outline" />
                                            @if($canManage)
                                                <x-mary-button icon="o-trash" wire:click="deleteAccount({{ $row->id }})" class="btn-sm btn-square btn-outline text-error" />
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-base-content/60">No hay cuentas registradas para este tenant.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <x-mary-input label="Display name" wire:model.defer="display_name" />
                    <x-mary-input label="Business account ID" wire:model.defer="business_account_id" />
                    <x-mary-input label="Phone number ID" wire:model.defer="phone_number_id" />
                    <x-mary-input label="Telefono" wire:model.defer="phone_number" />
                    <x-mary-input label="App ID" wire:model.defer="app_id" />
                    <x-mary-input label="Verify token" wire:model.defer="webhook_verify_token" />
                    <x-mary-input type="password" label="Access token" wire:model.defer="access_token" hint="Solo se sobreescribe si escribes un nuevo valor." />
                    <label class="form-control">
                        <div class="label"><span class="label-text">Estado</span></div>
                        <select wire:model.defer="status" class="select select-bordered">
                            <option value="pending">Pending</option>
                            <option value="connected">Connected</option>
                            <option value="error">Error</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </label>
                    <div class="md:col-span-2">
                        <x-mary-textarea label="Ultimo error" wire:model.defer="last_error" rows="3" />
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <x-mary-button label="Guardar cuenta" icon="o-check" wire:click="saveAccount" class="btn-primary text-white" />
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
