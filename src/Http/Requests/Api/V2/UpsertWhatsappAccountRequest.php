<?php

namespace VentureDrake\LaravelCrm\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertWhatsappAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer'],
            'display_name' => ['required', 'string', 'max:255'],
            'business_account_id' => ['nullable', 'string', 'max:255'],
            'phone_number_id' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'app_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string'],
            'webhook_verify_token' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'connected', 'error', 'disabled'])],
            'last_error' => ['nullable', 'string'],
        ];
    }
}
