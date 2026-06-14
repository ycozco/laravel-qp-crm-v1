<?php

use Illuminate\Support\Facades\Route;
use VentureDrake\LaravelCrm\Http\Controllers\WhatsappWebhookController;

$path = trim(config('meta-whatsapp.webhook.path', '/webhooks/meta/whatsapp'), '/');

Route::match(['GET', 'POST'], $path, WhatsappWebhookController::class)
    ->name('laravel-crm.webhooks.meta.whatsapp');
