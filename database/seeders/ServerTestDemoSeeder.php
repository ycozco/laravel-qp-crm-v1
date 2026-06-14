<?php

namespace VentureDrake\LaravelCrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use VentureDrake\LaravelCrm\Models\Role;
use VentureDrake\LaravelCrm\Models\Tenant;
use VentureDrake\LaravelCrm\Models\TenantWhatsappAccount;
use VentureDrake\LaravelCrm\Models\WhatsappConversation;
use VentureDrake\LaravelCrm\Models\WhatsappMessage;
use VentureDrake\LaravelCrm\Models\WhatsappTemplate;
use VentureDrake\LaravelCrm\Models\WhatsappWebhookEvent;

class ServerTestDemoSeeder extends Seeder
{
    public function run(): void
    {
        $userClass = class_exists('\App\Models\User') ? \App\Models\User::class : \App\User::class;

        DB::transaction(function () use ($userClass) {
            $ownerEmail = (string) env('CRM_OWNER_EMAIL', 'admincrm1@qpsecure.cloud');
            $ownerName = (string) env('CRM_OWNER_NAME', 'Admin CRM1');
            $ownerPassword = (string) env('CRM_OWNER_PASSWORD', env('CRM_DEMO_PASSWORD', 'Secret123!'));
            $demoPassword = (string) env('CRM_DEMO_PASSWORD', 'Secret123!');

            $owner = $this->createOrUpdateUser(
                $userClass,
                $ownerEmail,
                $ownerName,
                $ownerPassword,
                'Owner',
                preserveExistingPassword: true,
            );

            $profiles = [
                ['email' => 'opsadmincrm1@qpsecure.cloud', 'name' => 'Ops Admin CRM1', 'role' => 'Admin', 'tenant_role' => 'admin'],
                ['email' => 'managercrm1@qpsecure.cloud', 'name' => 'Manager CRM1', 'role' => 'Manager', 'tenant_role' => 'manager'],
                ['email' => 'salescrm1@qpsecure.cloud', 'name' => 'Sales CRM1', 'role' => 'Employee', 'tenant_role' => 'agent'],
                ['email' => 'apitestercrm1@qpsecure.cloud', 'name' => 'API Tester CRM1', 'role' => 'Employee', 'tenant_role' => 'agent'],
            ];

            $users = collect([$owner]);

            foreach ($profiles as $profile) {
                $users->push($this->createOrUpdateUser(
                    $userClass,
                    $profile['email'],
                    $profile['name'],
                    $demoPassword,
                    $profile['role'],
                ));
            }

            $tenant = Tenant::updateOrCreate(
                ['slug' => 'qpsecure-server-test'],
                ['name' => 'QP Secure Server Test', 'status' => 'active']
            );

            $tenant->users()->syncWithoutDetaching([
                $owner->getKey() => ['role' => 'owner'],
            ]);

            foreach ($profiles as $profile) {
                $user = $users->firstWhere('email', $profile['email']);

                if ($user) {
                    $tenant->users()->syncWithoutDetaching([
                        $user->getKey() => ['role' => $profile['tenant_role']],
                    ]);
                }
            }

            $account = TenantWhatsappAccount::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'phone_number_id' => 'qpsecure-demo-phone-001',
                ],
                [
                    'display_name' => 'QP Secure WhatsApp Demo',
                    'business_account_id' => 'qpsecure-demo-waba-001',
                    'phone_number' => '+51 999 000 111',
                    'app_id' => 'qpsecure-meta-app-001',
                    'access_token_encrypted' => Crypt::encryptString('qpsecure-demo-token-do-not-use'),
                    'webhook_verify_token' => 'qpsecure-server-test-verify-token',
                    'status' => 'connected',
                    'last_error' => null,
                    'connected_at' => now()->subDay(),
                ]
            );

            $conversations = [
                [
                    'contact_name' => 'Maria Fernandez',
                    'contact_phone' => '+51 987 654 321',
                    'status' => 'open',
                    'messages' => [
                        ['direction' => 'inbound', 'body' => 'Hola, quiero informacion sobre el servicio.', 'status' => 'received', 'minutes' => 42],
                        ['direction' => 'outbound', 'body' => 'Hola Maria, con gusto. Ya estamos revisando tu solicitud.', 'status' => 'sent', 'minutes' => 38],
                    ],
                ],
                [
                    'contact_name' => 'Carlos Ramos',
                    'contact_phone' => '+51 912 345 678',
                    'status' => 'pending',
                    'messages' => [
                        ['direction' => 'inbound', 'body' => 'Necesito confirmar mi cotizacion.', 'status' => 'received', 'minutes' => 120],
                    ],
                ],
                [
                    'contact_name' => 'Lucia Torres',
                    'contact_phone' => '+51 955 111 222',
                    'status' => 'closed',
                    'messages' => [
                        ['direction' => 'inbound', 'body' => 'Gracias, quedo claro.', 'status' => 'read', 'minutes' => 240],
                        ['direction' => 'outbound', 'body' => 'Perfecto Lucia, quedamos atentos.', 'status' => 'delivered', 'minutes' => 230],
                    ],
                ],
            ];

            foreach ($conversations as $conversationData) {
                $conversation = WhatsappConversation::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'contact_phone' => $conversationData['contact_phone'],
                    ],
                    [
                        'tenant_whatsapp_account_id' => $account->id,
                        'contact_name' => $conversationData['contact_name'],
                        'status' => $conversationData['status'],
                        'last_message_at' => now()->subMinutes($conversationData['messages'][array_key_last($conversationData['messages'])]['minutes']),
                    ]
                );

                WhatsappMessage::where('tenant_id', $tenant->id)
                    ->where('whatsapp_conversation_id', $conversation->id)
                    ->delete();

                foreach ($conversationData['messages'] as $message) {
                    WhatsappMessage::create([
                        'tenant_id' => $tenant->id,
                        'whatsapp_conversation_id' => $conversation->id,
                        'meta_message_id' => 'server_test_'.str()->uuid(),
                        'direction' => $message['direction'],
                        'type' => 'text',
                        'body' => $message['body'],
                        'status' => $message['status'],
                        'payload_json' => ['demo' => true, 'server_test' => true],
                        'sent_at' => now()->subMinutes($message['minutes']),
                    ]);
                }
            }

            WhatsappWebhookEvent::where('tenant_id', $tenant->id)->delete();

            WhatsappWebhookEvent::create([
                'tenant_id' => $tenant->id,
                'tenant_whatsapp_account_id' => $account->id,
                'phone_number_id' => $account->phone_number_id,
                'event_type' => 'messages',
                'field' => 'messages',
                'meta_object_id' => 'server-test-message-object-001',
                'signature_valid' => true,
                'payload_json' => ['object' => 'whatsapp_business_account', 'demo' => true, 'server_test' => true],
                'received_at' => now()->subMinutes(42),
                'processed_at' => now()->subMinutes(41),
                'processed_count' => 1,
                'processing_status' => 'processed',
            ]);

            WhatsappWebhookEvent::create([
                'tenant_id' => $tenant->id,
                'tenant_whatsapp_account_id' => $account->id,
                'phone_number_id' => $account->phone_number_id,
                'event_type' => 'message_status',
                'field' => 'messages',
                'meta_object_id' => 'server-test-status-object-001',
                'signature_valid' => true,
                'payload_json' => ['statuses' => [['status' => 'delivered']], 'demo' => true, 'server_test' => true],
                'received_at' => now()->subMinutes(38),
                'processed_at' => now()->subMinutes(37),
                'processed_count' => 1,
                'processing_status' => 'processed',
            ]);

            WhatsappTemplate::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'bienvenida_qpsecure', 'language' => 'es'],
                [
                    'category' => 'UTILITY',
                    'status' => 'approved',
                    'body' => 'Hola {{1}}, gracias por contactar con QP Secure Solutions.',
                    'payload_json' => ['demo' => true, 'server_test' => true],
                ]
            );
        });
    }

    protected function createOrUpdateUser(
        string $userClass,
        string $email,
        string $name,
        string $password,
        string $roleName,
        bool $preserveExistingPassword = false,
    ) {
        $user = $userClass::firstOrNew(['email' => $email]);

        $user->name = $name;
        $user->crm_access = true;

        if (! $user->exists || ! $preserveExistingPassword || ! $user->password) {
            $user->password = Hash::make($password);
        }

        $user->save();

        if (method_exists($user, 'syncRoles')) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role) {
                $user->syncRoles([$role]);
            }
        }

        return $user->fresh();
    }
}
