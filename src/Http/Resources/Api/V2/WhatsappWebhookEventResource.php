<?php

namespace VentureDrake\LaravelCrm\Http\Resources\Api\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class WhatsappWebhookEventResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant_whatsapp_account_id' => $this->tenant_whatsapp_account_id,
            'event_type' => $this->event_type,
            'field' => $this->field,
            'phone_number_id' => $this->phone_number_id,
            'meta_object_id' => $this->meta_object_id,
            'signature_valid' => (bool) $this->signature_valid,
            'processing_status' => $this->processing_status,
            'processed_count' => $this->processed_count,
            'error_message' => $this->error_message,
            'received_at' => $this->received_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
