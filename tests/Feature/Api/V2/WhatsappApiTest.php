<?php

use VentureDrake\LaravelCrm\Models\Tenant;
use VentureDrake\LaravelCrm\Models\TenantWhatsappAccount;
use VentureDrake\LaravelCrm\Models\WhatsappConversation;
use VentureDrake\LaravelCrm\Models\WhatsappMessage;
use VentureDrake\LaravelCrm\Models\WhatsappWebhookEvent;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function whatsappApiUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'WhatsApp API User',
        'email' => 'whatsapp-api-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

function whatsappApiHeaders(User $user): array
{
    return [
        'Authorization' => 'Bearer '.$user->createToken('whatsapp-api-test')->plainTextToken,
        'Accept' => 'application/json',
    ];
}

function whatsappTenant(string $slug, string $name): Tenant
{
    return Tenant::create([
        'name' => $name,
        'slug' => $slug,
        'status' => 'active',
    ]);
}

function attachTenantRole(Tenant $tenant, User $user, string $role): void
{
    $tenant->users()->syncWithoutDetaching([
        $user->id => ['role' => $role],
    ]);
}

test('GET /tenants returns only tenants accessible to the authenticated user', function () {
    $user = whatsappApiUser();
    $tenantA = whatsappTenant('tenant-a', 'Tenant A');
    $tenantB = whatsappTenant('tenant-b', 'Tenant B');
    $tenantHidden = whatsappTenant('tenant-hidden', 'Tenant Hidden');

    attachTenantRole($tenantA, $user, 'owner');
    attachTenantRole($tenantB, $user, 'agent');

    $response = $this->withHeaders(whatsappApiHeaders($user))
        ->getJson('/crm/api/v2/tenants');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('slug')->all())
        ->toBe(['tenant-a', 'tenant-b'])
        ->not->toContain($tenantHidden->slug);
});

test('GET /whatsapp/summary returns tenant-scoped account and stats', function () {
    $user = whatsappApiUser();
    $tenant = whatsappTenant('tenant-summary', 'Tenant Summary');
    attachTenantRole($tenant, $user, 'manager');

    $account = TenantWhatsappAccount::create([
        'tenant_id' => $tenant->id,
        'display_name' => 'Summary Account',
        'phone_number_id' => 'wamid-summary',
        'phone_number' => '+51 900 000 001',
        'access_token_encrypted' => 'encrypted-value',
        'webhook_verify_token' => 'verify-summary-1234',
        'status' => 'connected',
        'connected_at' => now(),
    ]);

    $conversation = WhatsappConversation::create([
        'tenant_id' => $tenant->id,
        'tenant_whatsapp_account_id' => $account->id,
        'contact_name' => 'Maria',
        'contact_phone' => '+51 999 888 777',
        'status' => 'open',
        'last_message_at' => now(),
    ]);

    WhatsappMessage::create([
        'tenant_id' => $tenant->id,
        'whatsapp_conversation_id' => $conversation->id,
        'meta_message_id' => 'meta-summary-1',
        'direction' => 'inbound',
        'type' => 'text',
        'body' => 'Hola',
        'status' => 'received',
        'payload_json' => ['demo' => true],
        'sent_at' => now(),
    ]);

    WhatsappWebhookEvent::create([
        'tenant_id' => $tenant->id,
        'tenant_whatsapp_account_id' => $account->id,
        'event_type' => 'messages',
        'field' => 'messages',
        'phone_number_id' => 'wamid-summary',
        'meta_object_id' => 'meta-object-summary',
        'signature_valid' => true,
        'payload_json' => ['demo' => true],
        'received_at' => now(),
        'processed_at' => now(),
        'processed_count' => 1,
        'processing_status' => 'processed',
    ]);

    $response = $this->withHeaders(whatsappApiHeaders($user))
        ->getJson('/crm/api/v2/whatsapp/summary?tenant_id='.$tenant->id);

    $response->assertOk();
    $response->assertJsonPath('tenant.slug', 'tenant-summary');
    $response->assertJsonPath('role', 'manager');
    $response->assertJsonPath('can_manage', true);
    $response->assertJsonPath('stats.conversation_count', 1);
    $response->assertJsonPath('stats.open_conversation_count', 1);
    $response->assertJsonPath('stats.message_count', 1);
    $response->assertJsonPath('stats.event_count', 1);
    $response->assertJsonPath('account.display_name', 'Summary Account');
    $response->assertJsonPath('account.has_access_token', true);
    $response->assertJsonPath('account.token_status', 'Configurado y cifrado');
    expect($response->json('account.verify_token_status'))->toEndWith('1234');
});

test('POST /whatsapp/accounts lets tenant managers create accounts and masks secrets in the response', function () {
    $user = whatsappApiUser();
    $tenant = whatsappTenant('tenant-store', 'Tenant Store');
    attachTenantRole($tenant, $user, 'owner');

    $response = $this->withHeaders(whatsappApiHeaders($user))
        ->postJson('/crm/api/v2/whatsapp/accounts', [
            'tenant_id' => $tenant->id,
            'display_name' => 'Store Account',
            'business_account_id' => 'waba-store-01',
            'phone_number_id' => 'phone-store-01',
            'phone_number' => '+51 955 000 111',
            'app_id' => 'app-store-01',
            'access_token' => 'top-secret-access-token',
            'webhook_verify_token' => 'verify-token-9876',
            'status' => 'connected',
        ]);

    $response->assertCreated();
    $response->assertJsonPath('data.display_name', 'Store Account');
    $response->assertJsonPath('data.has_access_token', true);
    $response->assertJsonPath('data.has_verify_token', true);
    $response->assertJsonPath('data.token_status', 'Configurado y cifrado');
    expect($response->json('data.verify_token_status'))->toEndWith('9876');

    $account = TenantWhatsappAccount::where('tenant_id', $tenant->id)->first();
    expect($account)->not->toBeNull();
    expect($account->access_token_encrypted)->not->toBe('top-secret-access-token');
});

test('GET /whatsapp/conversations and /whatsapp/events stay scoped to the requested tenant', function () {
    $user = whatsappApiUser();
    $tenantA = whatsappTenant('tenant-conv-a', 'Tenant Conv A');
    $tenantB = whatsappTenant('tenant-conv-b', 'Tenant Conv B');
    attachTenantRole($tenantA, $user, 'owner');

    $accountA = TenantWhatsappAccount::create([
        'tenant_id' => $tenantA->id,
        'display_name' => 'Account A',
        'status' => 'connected',
    ]);

    $accountB = TenantWhatsappAccount::create([
        'tenant_id' => $tenantB->id,
        'display_name' => 'Account B',
        'status' => 'connected',
    ]);

    $conversationA = WhatsappConversation::create([
        'tenant_id' => $tenantA->id,
        'tenant_whatsapp_account_id' => $accountA->id,
        'contact_name' => 'Visible Contact',
        'contact_phone' => '+51 900 111 222',
        'status' => 'open',
        'last_message_at' => now(),
    ]);

    WhatsappConversation::create([
        'tenant_id' => $tenantB->id,
        'tenant_whatsapp_account_id' => $accountB->id,
        'contact_name' => 'Hidden Contact',
        'contact_phone' => '+51 900 333 444',
        'status' => 'open',
        'last_message_at' => now(),
    ]);

    WhatsappMessage::create([
        'tenant_id' => $tenantA->id,
        'whatsapp_conversation_id' => $conversationA->id,
        'meta_message_id' => 'conversation-visible-1',
        'direction' => 'inbound',
        'type' => 'text',
        'body' => 'Mensaje visible',
        'status' => 'received',
        'payload_json' => ['tenant' => 'A'],
        'sent_at' => now(),
    ]);

    WhatsappWebhookEvent::create([
        'tenant_id' => $tenantA->id,
        'tenant_whatsapp_account_id' => $accountA->id,
        'event_type' => 'messages',
        'field' => 'messages',
        'phone_number_id' => 'tenant-a-phone',
        'meta_object_id' => 'tenant-a-event',
        'signature_valid' => true,
        'payload_json' => ['tenant' => 'A'],
        'received_at' => now(),
        'processing_status' => 'processed',
    ]);

    WhatsappWebhookEvent::create([
        'tenant_id' => $tenantB->id,
        'tenant_whatsapp_account_id' => $accountB->id,
        'event_type' => 'messages',
        'field' => 'messages',
        'phone_number_id' => 'tenant-b-phone',
        'meta_object_id' => 'tenant-b-event',
        'signature_valid' => true,
        'payload_json' => ['tenant' => 'B'],
        'received_at' => now(),
        'processing_status' => 'processed',
    ]);

    $conversations = $this->withHeaders(whatsappApiHeaders($user))
        ->getJson('/crm/api/v2/whatsapp/conversations?tenant_id='.$tenantA->id.'&search=Visible');

    $conversations->assertOk();
    expect($conversations->json('meta.total'))->toBe(1);
    expect($conversations->json('data.0.contact_name'))->toBe('Visible Contact');

    $events = $this->withHeaders(whatsappApiHeaders($user))
        ->getJson('/crm/api/v2/whatsapp/events?tenant_id='.$tenantA->id);

    $events->assertOk();
    expect($events->json('meta.total'))->toBe(1);
    expect($events->json('data.0.meta_object_id'))->toBe('tenant-a-event');

    $conversationDetail = $this->withHeaders(whatsappApiHeaders($user))
        ->getJson('/crm/api/v2/whatsapp/conversations/'.$conversationA->id.'?tenant_id='.$tenantA->id);

    $conversationDetail->assertOk();
    expect($conversationDetail->json('data.messages.0.body'))->toBe('Mensaje visible');
});
