<?php

namespace VentureDrake\LaravelCrm\Http\Resources\Api\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class WhatsappAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'display_name' => $this->display_name,
            'business_account_id' => $this->business_account_id,
            'phone_number_id' => $this->phone_number_id,
            'phone_number' => $this->phone_number,
            'app_id' => $this->app_id,
            'status' => $this->status,
            'last_error' => $this->last_error,
            'token_status' => $this->maskedToken(),
            'verify_token_status' => $this->maskedVerifyToken(),
            'has_access_token' => (bool) $this->access_token_encrypted,
            'has_verify_token' => (bool) $this->webhook_verify_token,
            'connected_at' => $this->connected_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
