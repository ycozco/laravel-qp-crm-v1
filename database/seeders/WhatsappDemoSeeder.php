<?php

namespace VentureDrake\LaravelCrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Models\Tenant;
use VentureDrake\LaravelCrm\Models\TenantWhatsappAccount;
use VentureDrake\LaravelCrm\Models\WhatsappConversation;
use VentureDrake\LaravelCrm\Models\WhatsappMessage;
use VentureDrake\LaravelCrm\Models\WhatsappTemplate;
use VentureDrake\LaravelCrm\Models\WhatsappWebhookEvent;

class WhatsappDemoSeeder extends Seeder
{
    public function run(): void
    {
        $userClass = class_exists('\App\Models\User') ? \App\Models\User::class : \App\User::class;

        DB::transaction(function () use ($userClass) {
            $tenant = Tenant::updateOrCreate(
                ['slug' => 'demo-crm-laravel'],
                ['name' => 'Demo CRM Laravel', 'status' => 'active']
            );

            $userClass::whereIn('email', ['admin@crm-laravel.local', 'sales@crm-laravel.local'])
                ->get()
                ->each(fn ($user) => $tenant->users()->syncWithoutDetaching([
                    $user->getKey() => ['role' => $user->email === 'admin@crm-laravel.local' ? 'owner' : 'agent'],
                ]));

            $account = TenantWhatsappAccount::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'phone_number_id' => 'demo-phone-number-id-001',
                ],
                [
                    'display_name' => 'WhatsApp Demo CRM',
                    'business_account_id' => 'demo-business-account-001',
                    'phone_number' => '+51 999 000 111',
                    'app_id' => 'demo-meta-app-001',
                    'access_token_encrypted' => Crypt::encryptString('demo-meta-token-do-not-use'),
                    'webhook_verify_token' => 'demo-verify-token-local',
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
                        'meta_message_id' => 'demo_'.str()->uuid(),
                        'direction' => $message['direction'],
                        'type' => 'text',
                        'body' => $message['body'],
                        'status' => $message['status'],
                        'payload_json' => ['demo' => true],
                        'sent_at' => now()->subMinutes($message['minutes']),
                    ]);
                }
            }

            WhatsappWebhookEvent::where('tenant_id', $tenant->id)->delete();

            WhatsappWebhookEvent::create([
                'tenant_id' => $tenant->id,
                'event_type' => 'messages',
                'meta_object_id' => 'demo-message-object-001',
                'signature_valid' => true,
                'payload_json' => ['object' => 'whatsapp_business_account', 'demo' => true],
                'received_at' => now()->subMinutes(42),
                'processed_at' => now()->subMinutes(41),
            ]);

            WhatsappWebhookEvent::create([
                'tenant_id' => $tenant->id,
                'event_type' => 'message_status',
                'meta_object_id' => 'demo-status-object-001',
                'signature_valid' => true,
                'payload_json' => ['statuses' => [['status' => 'delivered']], 'demo' => true],
                'received_at' => now()->subMinutes(38),
                'processed_at' => now()->subMinutes(37),
            ]);

            WhatsappTemplate::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'bienvenida_demo', 'language' => 'es'],
                [
                    'category' => 'UTILITY',
                    'status' => 'approved',
                    'body' => 'Hola {{1}}, gracias por contactar con nosotros.',
                    'payload_json' => ['demo' => true],
                ]
            );
        });
    }
}
