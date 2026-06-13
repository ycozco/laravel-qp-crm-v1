<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API
    |--------------------------------------------------------------------------
    |
    | This file only defines the host-app defaults needed to start a future
    | WhatsApp integration. It does not implement message sending yet.
    |
    | IMPORTANT FOR SAAS:
    | Tenant-specific access tokens must not be stored here. Store each tenant
    | token encrypted in the future `saas_tenant_integrations.credentials_json`
    | record and resolve it by `phone_number_id` or `waba_id` when processing
    | webhooks.
    |
    */

    'enabled' => env('META_WHATSAPP_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Graph API Version
    |--------------------------------------------------------------------------
    |
    | Example: v20.0, v21.0, v22.0. Keep this explicit so upgrades are
    | deliberate and testable.
    |
    */

    'graph_version' => env('META_WHATSAPP_GRAPH_VERSION', 'v20.0'),

    /*
    |--------------------------------------------------------------------------
    | Meta App Credentials
    |--------------------------------------------------------------------------
    |
    | These identify the Meta app used for webhook verification, app-secret
    | proof, and future embedded signup flows. Do not put tenant phone access
    | tokens here.
    |
    */

    'app_id' => env('META_WHATSAPP_APP_ID'),
    'app_secret' => env('META_WHATSAPP_APP_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    |
    | META_WHATSAPP_WEBHOOK_VERIFY_TOKEN is the token Meta sends to verify the
    | GET webhook challenge. It can be global for the app.
    |
    | The POST webhook must resolve the tenant from the incoming payload,
    | usually by `metadata.phone_number_id` or `metadata.display_phone_number`.
    |
    */

    'webhook' => [
        'path' => env('META_WHATSAPP_WEBHOOK_PATH', '/webhooks/meta/whatsapp'),
        'verify_token' => env('META_WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Manual MVP Tenant Credential Shape
    |--------------------------------------------------------------------------
    |
    | Use this as the encrypted JSON shape for each tenant integration later:
    |
    | {
    |   "waba_id": "tenant WABA ID",
    |   "phone_number_id": "tenant phone number ID",
    |   "business_id": "tenant business ID",
    |   "access_token": "tenant permanent or system-user token"
    | }
    |
    */

    'tenant_credentials_store' => [
        'table' => 'saas_tenant_integrations',
        'provider' => 'meta_whatsapp',
        'encrypted_column' => 'credentials_json',
        'tenant_lookup_keys' => [
            'phone_number_id',
            'waba_id',
        ],
    ],

];
