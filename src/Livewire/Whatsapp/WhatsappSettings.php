<?php

namespace VentureDrake\LaravelCrm\Livewire\Whatsapp;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use VentureDrake\LaravelCrm\Livewire\Whatsapp\Concerns\ResolvesWhatsappTenant;
use VentureDrake\LaravelCrm\Models\Tenant;
use VentureDrake\LaravelCrm\Models\TenantWhatsappAccount;

class WhatsappSettings extends Component
{
    use ResolvesWhatsappTenant;

    #[Url(as: 'tenant')]
    public ?int $tenantId = null;

    public bool $creatingTenant = false;

    public ?int $editingAccountId = null;

    public string $tenant_name = '';

    public string $tenant_slug = '';

    public string $tenant_status = 'active';

    public string $display_name = '';

    public string $business_account_id = '';

    public string $phone_number_id = '';

    public string $phone_number = '';

    public string $app_id = '';

    public string $webhook_verify_token = '';

    public string $access_token = '';

    public string $status = 'pending';

    public string $last_error = '';

    public function mount(): void
    {
        $this->syncSelectedTenant();
        $this->fillForms();
    }

    public function updatedTenantId(): void
    {
        $this->creatingTenant = false;
        $this->fillForms();
    }

    public function startTenantCreate(): void
    {
        $this->creatingTenant = true;
        $this->tenantId = null;
        $this->tenant_name = '';
        $this->tenant_slug = '';
        $this->tenant_status = 'active';
        $this->editingAccountId = null;
        $this->resetAccountForm();
    }

    public function saveTenant(): void
    {
        if ($this->tenantId && ! $this->canManageCurrentTenant()) {
            throw ValidationException::withMessages([
                'tenant_name' => 'No tienes permisos para editar este tenant.',
            ]);
        }

        $tenant = $this->creatingTenant ? null : $this->currentTenant();

        $data = $this->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'tenant_slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique((new Tenant)->getTable(), 'slug')->ignore($tenant?->id),
            ],
            'tenant_status' => ['required', Rule::in(['active', 'pending', 'disabled'])],
        ]);

        if ($tenant) {
            $tenant->update([
                'name' => $data['tenant_name'],
                'slug' => $data['tenant_slug'],
                'status' => $data['tenant_status'],
            ]);
        } else {
            $tenant = Tenant::create([
                'name' => $data['tenant_name'],
                'slug' => $data['tenant_slug'],
                'status' => $data['tenant_status'],
            ]);

            $tenant->users()->syncWithoutDetaching([
                auth()->id() => ['role' => 'owner'],
            ]);

            $this->resolvedTenants = null;
            $this->tenantId = $tenant->id;
        }

        $this->creatingTenant = false;
        $this->fillForms();

        session()->flash('whatsapp_settings_success', 'Tenant guardado correctamente.');
    }

    public function startAccountCreate(): void
    {
        if (! $this->canManageCurrentTenant()) {
            return;
        }

        $this->editingAccountId = null;
        $this->resetAccountForm();
    }

    public function editAccount(int $accountId): void
    {
        $tenant = $this->currentTenant();

        abort_if(! $tenant, 404);

        $account = TenantWhatsappAccount::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($accountId);

        $this->editingAccountId = $account->id;
        $this->fillAccountForm($account);
    }

    public function deleteAccount(int $accountId): void
    {
        if (! $this->canManageCurrentTenant()) {
            return;
        }

        $tenant = $this->currentTenant();

        abort_if(! $tenant, 404);

        TenantWhatsappAccount::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($accountId)
            ->delete();

        if ($this->editingAccountId === $accountId) {
            $this->editingAccountId = null;
            $this->resetAccountForm();
        }

        session()->flash('whatsapp_settings_success', 'Cuenta WhatsApp eliminada.');
    }

    public function saveAccount(): void
    {
        $tenant = $this->currentTenant();

        if (! $tenant) {
            throw ValidationException::withMessages([
                'display_name' => 'Primero crea o selecciona un tenant.',
            ]);
        }

        if (! $this->canManageCurrentTenant()) {
            throw ValidationException::withMessages([
                'display_name' => 'No tienes permisos para editar esta cuenta.',
            ]);
        }

        $data = $this->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'business_account_id' => ['nullable', 'string', 'max:255'],
            'phone_number_id' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'app_id' => ['nullable', 'string', 'max:255'],
            'webhook_verify_token' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['pending', 'connected', 'error', 'disabled'])],
            'last_error' => ['nullable', 'string'],
        ]);

        $account = $this->editingAccountId
            ? TenantWhatsappAccount::query()->where('tenant_id', $tenant->id)->findOrFail($this->editingAccountId)
            : new TenantWhatsappAccount(['tenant_id' => $tenant->id]);

        $account->fill([
            'tenant_id' => $tenant->id,
            'display_name' => $data['display_name'],
            'business_account_id' => $data['business_account_id'] ?: null,
            'phone_number_id' => $data['phone_number_id'] ?: null,
            'phone_number' => $data['phone_number'] ?: null,
            'app_id' => $data['app_id'] ?: null,
            'webhook_verify_token' => $data['webhook_verify_token'] ?: null,
            'status' => $data['status'],
            'last_error' => $data['last_error'] ?: null,
            'connected_at' => $data['status'] === 'connected'
                ? ($account->connected_at ?? now())
                : null,
        ]);

        if ($data['access_token'] !== '') {
            $account->access_token_encrypted = Crypt::encryptString($data['access_token']);
        }

        $account->save();

        $this->editingAccountId = $account->id;
        $this->fillAccountForm($account->fresh());

        session()->flash('whatsapp_settings_success', 'Cuenta WhatsApp guardada correctamente.');
    }

    public function render()
    {
        $this->syncSelectedTenant();

        $tenant = $this->currentTenant();
        $accounts = $tenant
            ? TenantWhatsappAccount::query()->where('tenant_id', $tenant->id)->latest()->get()
            : collect();

        return view('laravel-crm::livewire.whatsapp.settings', [
            'tenant' => $tenant,
            'availableTenants' => $this->availableTenants(),
            'tenantRole' => $this->currentTenantRole(),
            'canManage' => $this->canManageCurrentTenant(),
            'account' => $accounts->first(),
            'accounts' => $accounts,
            'callbackUrl' => url(config('meta-whatsapp.webhook.path', '/webhooks/meta/whatsapp')),
            'signatureRequired' => (bool) config('meta-whatsapp.webhook.require_signature'),
        ]);
    }

    protected function fillForms(): void
    {
        $this->resolvedTenants = null;
        $this->syncSelectedTenant();
        $this->creatingTenant = false;

        $tenant = $this->currentTenant();

        $this->tenant_name = (string) ($tenant?->name ?? '');
        $this->tenant_slug = (string) ($tenant?->slug ?? '');
        $this->tenant_status = (string) ($tenant?->status ?? 'active');

        $account = $tenant
            ? TenantWhatsappAccount::query()->where('tenant_id', $tenant->id)->latest()->first()
            : null;

        $this->editingAccountId = $account?->id;

        $account ? $this->fillAccountForm($account) : $this->resetAccountForm();
    }

    protected function fillAccountForm(?TenantWhatsappAccount $account): void
    {
        $this->display_name = (string) ($account?->display_name ?? '');
        $this->business_account_id = (string) ($account?->business_account_id ?? '');
        $this->phone_number_id = (string) ($account?->phone_number_id ?? '');
        $this->phone_number = (string) ($account?->phone_number ?? '');
        $this->app_id = (string) ($account?->app_id ?? '');
        $this->webhook_verify_token = (string) ($account?->webhook_verify_token ?? '');
        $this->access_token = '';
        $this->status = (string) ($account?->status ?? 'pending');
        $this->last_error = (string) ($account?->last_error ?? '');
    }

    protected function resetAccountForm(): void
    {
        $this->display_name = '';
        $this->business_account_id = '';
        $this->phone_number_id = '';
        $this->phone_number = '';
        $this->app_id = '';
        $this->webhook_verify_token = '';
        $this->access_token = '';
        $this->status = 'pending';
        $this->last_error = '';
    }
}
