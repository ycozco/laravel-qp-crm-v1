<div class="tabs tabs-boxed bg-base-100 mb-4">
    <a href="{{ url(route('laravel-crm.whatsapp.index')) }}" class="tab {{ request()->routeIs('laravel-crm.whatsapp.index') ? 'tab-active' : '' }}">Resumen</a>
    <a href="{{ url(route('laravel-crm.whatsapp.settings')) }}" class="tab {{ request()->routeIs('laravel-crm.whatsapp.settings') ? 'tab-active' : '' }}">Conexion</a>
    <a href="{{ url(route('laravel-crm.whatsapp.conversations.index')) }}" class="tab {{ request()->routeIs('laravel-crm.whatsapp.conversations.*') ? 'tab-active' : '' }}">Conversaciones</a>
    <a href="{{ url(route('laravel-crm.whatsapp.events')) }}" class="tab {{ request()->routeIs('laravel-crm.whatsapp.events') ? 'tab-active' : '' }}">Webhooks</a>
</div>
