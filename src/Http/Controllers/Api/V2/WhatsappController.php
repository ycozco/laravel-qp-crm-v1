<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Controllers\Api\V2\Concerns\ResolvesTenantContext;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\TenantResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\WhatsappAccountResource;
use VentureDrake\LaravelCrm\Http\Resources\Api\V2\WhatsappWebhookEventResource;
use VentureDrake\LaravelCrm\Models\TenantWhatsappAccount;
use VentureDrake\LaravelCrm\Models\WhatsappConversation;
use VentureDrake\LaravelCrm\Models\WhatsappMessage;
use VentureDrake\LaravelCrm\Models\WhatsappWebhookEvent;

class WhatsappController extends ApiController
{
    use ResolvesTenantContext;

    public function summary(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $account = $tenant ? TenantWhatsappAccount::query()->where('tenant_id', $tenant->id)->latest()->first() : null;

        return response()->json([
            'tenant' => $tenant ? new TenantResource($tenant) : null,
            'role' => $tenant ? $this->tenantRole($request, $tenant) : null,
            'can_manage' => $tenant ? in_array($this->tenantRole($request, $tenant), ['owner', 'admin', 'manager'], true) : false,
            'account' => $account ? new WhatsappAccountResource($account) : null,
            'stats' => [
                'conversation_count' => $tenant ? WhatsappConversation::where('tenant_id', $tenant->id)->count() : 0,
                'open_conversation_count' => $tenant ? WhatsappConversation::where('tenant_id', $tenant->id)->where('status', 'open')->count() : 0,
                'message_count' => $tenant ? WhatsappMessage::where('tenant_id', $tenant->id)->count() : 0,
                'event_count' => $tenant ? WhatsappWebhookEvent::where('tenant_id', $tenant->id)->count() : 0,
            ],
            'latest_events' => $tenant
                ? WhatsappWebhookEventResource::collection(
                    WhatsappWebhookEvent::query()->where('tenant_id', $tenant->id)->latest('received_at')->limit(5)->get()
                )
                : [],
        ]);
    }
}
