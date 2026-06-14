<?php

use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Whatsapp\WhatsappSettings;
use VentureDrake\LaravelCrm\Models\Tenant;
use VentureDrake\LaravelCrm\Models\TenantWhatsappAccount;

beforeEach(function () {
    $this->actingAsUser(['crm_access' => true]);
});

test('whatsapp settings can create a new tenant without overwriting the current tenant', function () {
    $existingTenant = Tenant::create([
        'name' => 'Existing Tenant',
        'slug' => 'existing-tenant',
        'status' => 'active',
    ]);

    $existingTenant->users()->syncWithoutDetaching([
        auth()->id() => ['role' => 'owner'],
    ]);

    Livewire::test(WhatsappSettings::class)
        ->call('startTenantCreate')
        ->set('tenant_name', 'New Tenant')
        ->set('tenant_slug', 'new-tenant')
        ->set('tenant_status', 'active')
        ->call('saveTenant');

    expect(Tenant::where('slug', 'existing-tenant')->first()->name)->toBe('Existing Tenant');
    expect(Tenant::where('slug', 'new-tenant')->exists())->toBeTrue();
    expect(Tenant::count())->toBe(2);
});

test('tenant owners can create whatsapp accounts from livewire settings', function () {
    $tenant = Tenant::create([
        'name' => 'Owner Tenant',
        'slug' => 'owner-tenant',
        'status' => 'active',
    ]);

    $tenant->users()->syncWithoutDetaching([
        auth()->id() => ['role' => 'owner'],
    ]);

    Livewire::test(WhatsappSettings::class)
        ->set('tenantId', $tenant->id)
        ->set('display_name', 'Owner Account')
        ->set('business_account_id', 'waba-owner-01')
        ->set('phone_number_id', 'owner-phone-01')
        ->set('phone_number', '+51 999 111 222')
        ->set('app_id', 'owner-app-01')
        ->set('webhook_verify_token', 'verify-owner-1234')
        ->set('access_token', 'owner-secret-token')
        ->set('status', 'connected')
        ->call('saveAccount');

    $account = TenantWhatsappAccount::where('tenant_id', $tenant->id)->first();

    expect($account)->not->toBeNull();
    expect($account->display_name)->toBe('Owner Account');
    expect($account->maskedVerifyToken())->toEndWith('1234');
    expect($account->maskedToken())->toBe('Configurado y cifrado');
});

test('tenant agents stay read-only in whatsapp settings account editor', function () {
    $tenant = Tenant::create([
        'name' => 'Agent Tenant',
        'slug' => 'agent-tenant',
        'status' => 'active',
    ]);

    $tenant->users()->syncWithoutDetaching([
        auth()->id() => ['role' => 'agent'],
    ]);

    Livewire::test(WhatsappSettings::class)
        ->set('tenantId', $tenant->id)
        ->set('display_name', 'Blocked Account')
        ->set('status', 'pending')
        ->call('saveAccount')
        ->assertHasErrors(['display_name']);

    expect(TenantWhatsappAccount::where('tenant_id', $tenant->id)->exists())->toBeFalse();
});
