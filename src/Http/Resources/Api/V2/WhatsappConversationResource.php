<?php

namespace VentureDrake\LaravelCrm\Http\Resources\Api\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class WhatsappConversationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant_whatsapp_account_id' => $this->tenant_whatsapp_account_id,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'status' => $this->status,
            'messages_count' => $this->whenCounted('messages', $this->messages_count),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'account' => $this->whenLoaded('account', fn () => $this->account ? new WhatsappAccountResource($this->account) : null),
            'messages' => WhatsappMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
