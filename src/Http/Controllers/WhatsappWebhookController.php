<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use VentureDrake\LaravelCrm\Models\Tenant;
use VentureDrake\LaravelCrm\Models\TenantWhatsappAccount;
use VentureDrake\LaravelCrm\Models\WhatsappConversation;
use VentureDrake\LaravelCrm\Models\WhatsappMessage;
use VentureDrake\LaravelCrm\Models\WhatsappWebhookEvent;

class WhatsappWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->isMethod('GET')) {
            return $this->verify($request);
        }

        return $this->receive($request);
    }

    protected function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));

        if ($mode === 'subscribe' && $challenge && $this->verifyTokenMatches($token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Webhook verification failed.', 403);
    }

    protected function receive(Request $request): Response
    {
        $payload = $request->json()->all();
        $signatureValid = $this->signatureIsValid($request);

        if (config('meta-whatsapp.webhook.require_signature') && ! $signatureValid) {
            return response('Invalid webhook signature.', 403);
        }

        $processed = 0;

        foreach (Arr::get($payload, 'entry', []) as $entry) {
            foreach (Arr::get($entry, 'changes', []) as $change) {
                $account = $this->resolveAccount($entry, $change);
                $tenant = $account?->tenant ?? $this->fallbackTenant();

                if (! $tenant) {
                    Log::warning('Meta WhatsApp webhook ignored because tenant could not be resolved.', [
                        'entry_id' => Arr::get($entry, 'id'),
                        'field' => Arr::get($change, 'field'),
                    ]);

                    continue;
                }

                $event = $this->storeEvent($tenant, $account, $entry, $change, $signatureValid);
                $processed += $this->processChange($tenant, $account, $event, $change);
            }
        }

        return response()->json(['success' => true, 'processed' => $processed]);
    }

    protected function verifyTokenMatches(?string $token): bool
    {
        if (! $token) {
            return false;
        }

        $globalToken = config('meta-whatsapp.webhook.verify_token');

        if ($globalToken && hash_equals($globalToken, $token)) {
            return true;
        }

        return TenantWhatsappAccount::where('webhook_verify_token', $token)->exists();
    }

    protected function signatureIsValid(Request $request): bool
    {
        $appSecret = config('meta-whatsapp.app_secret');
        $signature = $request->header('X-Hub-Signature-256');

        if (! $appSecret || ! $signature || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $signature);
    }

    protected function resolveAccount(array $entry, array $change): ?TenantWhatsappAccount
    {
        $phoneNumberId = Arr::get($change, 'value.metadata.phone_number_id');
        $wabaId = Arr::get($entry, 'id');

        return TenantWhatsappAccount::query()
            ->when($phoneNumberId, fn ($query) => $query->where('phone_number_id', $phoneNumberId))
            ->when(! $phoneNumberId && $wabaId, fn ($query) => $query->where('business_account_id', $wabaId))
            ->first();
    }

    protected function fallbackTenant(): ?Tenant
    {
        if (! config('meta-whatsapp.webhook.allow_fallback_tenant')) {
            return null;
        }

        return Tenant::query()->where('status', 'active')->orderBy('id')->first();
    }

    protected function storeEvent(Tenant $tenant, ?TenantWhatsappAccount $account, array $entry, array $change, bool $signatureValid): WhatsappWebhookEvent
    {
        $value = Arr::get($change, 'value', []);
        $errors = $this->extractErrors($value);

        return WhatsappWebhookEvent::create([
            'tenant_id' => $tenant->id,
            'tenant_whatsapp_account_id' => $account?->id,
            'phone_number_id' => Arr::get($value, 'metadata.phone_number_id'),
            'event_type' => $this->eventType($change),
            'field' => Arr::get($change, 'field'),
            'meta_object_id' => Arr::get($entry, 'id'),
            'signature_valid' => $signatureValid,
            'payload_json' => $change,
            'received_at' => $this->timestamp(Arr::get($entry, 'time')) ?? now(),
            'error_message' => $errors ? $this->formatErrors($errors) : null,
            'processing_status' => 'received',
        ]);
    }

    protected function processChange(Tenant $tenant, ?TenantWhatsappAccount $account, WhatsappWebhookEvent $event, array $change): int
    {
        $value = Arr::get($change, 'value', []);
        $count = 0;

        foreach (Arr::get($value, 'messages', []) as $message) {
            $this->upsertMessage($tenant, $account, $event, $value, $message);
            $count++;
        }

        foreach (Arr::get($value, 'statuses', []) as $status) {
            $this->applyStatus($tenant, $event, $status);
            $count++;
        }

        foreach (Arr::get($value, 'history', []) as $history) {
            foreach (Arr::get($history, 'threads', []) as $thread) {
                foreach (Arr::get($thread, 'messages', []) as $message) {
                    $this->upsertMessage($tenant, $account, $event, $value, $message, $thread['id'] ?? null);
                    $count++;
                }
            }
        }

        $event->update([
            'processed_at' => now(),
            'processed_count' => $count,
            'processing_status' => $event->error_message ? 'error' : 'processed',
        ]);

        return $count;
    }

    protected function upsertMessage(Tenant $tenant, ?TenantWhatsappAccount $account, WhatsappWebhookEvent $event, array $value, array $message, ?string $threadContact = null): WhatsappMessage
    {
        $businessPhone = Arr::get($value, 'metadata.display_phone_number');
        $from = Arr::get($message, 'from');
        $contactPhone = $threadContact ?: $from;
        $direction = ($businessPhone && $from === $businessPhone) ? 'outbound' : 'inbound';
        $contact = $this->contactFor($value, $contactPhone);

        $conversation = WhatsappConversation::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'contact_phone' => $contactPhone,
            ],
            [
                'tenant_whatsapp_account_id' => $account?->id,
                'contact_name' => $contact['name'] ?? null,
                'status' => 'open',
                'last_message_at' => $this->timestamp(Arr::get($message, 'timestamp')) ?? now(),
            ]
        );

        return WhatsappMessage::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'meta_message_id' => Arr::get($message, 'id'),
            ],
            [
                'whatsapp_conversation_id' => $conversation->id,
                'whatsapp_webhook_event_id' => $event->id,
                'direction' => $direction,
                'type' => Arr::get($message, 'type', 'unknown'),
                'body' => $this->messageBody($message),
                'status' => strtolower(Arr::get($message, 'history_context.status', $direction === 'inbound' ? 'received' : 'sent')),
                'payload_json' => $message,
                'sent_at' => $this->timestamp(Arr::get($message, 'timestamp')) ?? now(),
            ]
        );
    }

    protected function applyStatus(Tenant $tenant, WhatsappWebhookEvent $event, array $status): void
    {
        $message = WhatsappMessage::where('tenant_id', $tenant->id)
            ->where('meta_message_id', Arr::get($status, 'id'))
            ->first();

        if (! $message) {
            return;
        }

        $error = Arr::first(Arr::get($status, 'errors', []));

        $message->update([
            'whatsapp_webhook_event_id' => $event->id,
            'status' => strtolower(Arr::get($status, 'status', $message->status)),
            'error_code' => $error ? (string) Arr::get($error, 'code') : null,
            'error_title' => $error ? Arr::get($error, 'title') : null,
            'error_details' => $error ? Arr::get($error, 'error_data.details', Arr::get($error, 'message')) : null,
            'payload_json' => array_merge($message->payload_json ?? [], ['latest_status' => $status]),
        ]);
    }

    protected function contactFor(array $value, ?string $phone): array
    {
        if (! $phone) {
            return [];
        }

        foreach (Arr::get($value, 'contacts', []) as $contact) {
            if (Arr::get($contact, 'wa_id') === $phone) {
                return ['name' => Arr::get($contact, 'profile.name')];
            }
        }

        return [];
    }

    protected function messageBody(array $message): ?string
    {
        $type = Arr::get($message, 'type');

        return match ($type) {
            'text' => Arr::get($message, 'text.body'),
            'button' => Arr::get($message, 'button.text', Arr::get($message, 'button.payload')),
            'interactive' => Arr::get($message, 'interactive.button_reply.title', Arr::get($message, 'interactive.list_reply.title')),
            'image', 'video', 'audio', 'document', 'sticker' => Arr::get($message, $type.'.caption', '['.$type.'] '.Arr::get($message, $type.'.id')),
            'media_placeholder' => '[media_placeholder]',
            default => $type ? '['.$type.']' : null,
        };
    }

    protected function eventType(array $change): string
    {
        $value = Arr::get($change, 'value', []);

        if (Arr::get($value, 'messages')) {
            return 'messages';
        }

        if (Arr::get($value, 'statuses')) {
            return 'message_status';
        }

        return Arr::get($change, 'field', 'unknown');
    }

    protected function extractErrors(array $value): array
    {
        $errors = Arr::get($value, 'errors', []);

        foreach (Arr::get($value, 'history', []) as $history) {
            $errors = array_merge($errors, Arr::get($history, 'errors', []));
        }

        foreach (Arr::get($value, 'statuses', []) as $status) {
            $errors = array_merge($errors, Arr::get($status, 'errors', []));
        }

        return $errors;
    }

    protected function formatErrors(array $errors): string
    {
        return collect($errors)->map(function ($error) {
            return trim(implode(' ', array_filter([
                Arr::get($error, 'code'),
                Arr::get($error, 'title'),
                Arr::get($error, 'message'),
                Arr::get($error, 'error_data.details'),
            ])));
        })->implode(' | ');
    }

    protected function timestamp($timestamp): ?Carbon
    {
        if (! $timestamp) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $timestamp);
    }
}
