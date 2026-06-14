# Laravel QP CRM v1

[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

CRM Laravel preparado por `ycozco` para pruebas locales, despliegue en servidor y evolucion multitenant. Esta version incluye el core CRM, un MVP de frontend WhatsApp y un receptor de webhooks Meta WhatsApp listo para validacion en `server-test`.

## Estado Actual

- CRM Laravel ejecutandose como paquete dentro de un host Laravel.
- Frontend interno con Blade, Livewire, Mary UI, Tailwind y Vite.
- Modulo WhatsApp visible en el menu CRM.
- Datos demo para revisar tenant, cuenta WhatsApp, conversaciones, mensajes y eventos.
- Endpoint publico para webhooks Meta WhatsApp.
- Procesamiento basico de mensajes entrantes, historial, estados y errores Meta.

## Ramas

```text
main        Rama central con frontend WhatsApp MVP y webhook Meta integrado.
local-test  Rama para pruebas locales.
server-test Rama para pruebas de servidor, HTTPS, Meta Webhooks y hardening.
```

## Rutas Principales

CRM local:

```text
http://127.0.0.1:8088/crm
```

Modulo WhatsApp:

```text
http://127.0.0.1:8088/crm/whatsapp
http://127.0.0.1:8088/crm/whatsapp/settings
http://127.0.0.1:8088/crm/whatsapp/conversations
http://127.0.0.1:8088/crm/whatsapp/events
```

Webhook Meta WhatsApp:

```text
GET  http://127.0.0.1:8088/webhooks/meta/whatsapp
POST http://127.0.0.1:8088/webhooks/meta/whatsapp
```

## Acceso Demo

```text
admin@crm-laravel.local
Secret123!
```

```text
sales@crm-laravel.local
Secret123!
```

## WhatsApp MVP

El modulo incluye:

- Dashboard WhatsApp por tenant.
- Pantalla de configuracion Meta por tenant.
- Bandeja de conversaciones.
- Detalle de mensajes.
- Registro de eventos webhook.
- Tokens enmascarados.
- Filtros y paginacion.
- Consultas filtradas por `tenant_id`.

## Webhooks Meta WhatsApp

El endpoint implementado soporta:

- Verificacion GET con `hub.challenge`.
- Validacion de `hub.verify_token`.
- Recepcion POST de payloads JSON.
- Validacion HMAC con `X-Hub-Signature-256` cuando se configura `META_WHATSAPP_APP_SECRET`.
- Resolucion de tenant por `phone_number_id` o WABA id.
- Persistencia de payload bruto.
- Creacion/actualizacion de conversaciones.
- Guardado de mensajes entrantes e historial.
- Actualizacion de estados de mensajes.
- Captura de errores Meta en mensajes y eventos.

Variables relevantes:

```text
META_WHATSAPP_ENABLED=true
META_WHATSAPP_GRAPH_VERSION=v20.0
META_WHATSAPP_APP_ID=
META_WHATSAPP_APP_SECRET=
META_WHATSAPP_WEBHOOK_VERIFY_TOKEN=
META_WHATSAPP_WEBHOOK_PATH=/webhooks/meta/whatsapp
META_WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=false
META_WHATSAPP_WEBHOOK_ALLOW_FALLBACK_TENANT=true
```

En servidor se recomienda:

```text
META_WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=true
META_WHATSAPP_WEBHOOK_ALLOW_FALLBACK_TENANT=false
APP_DEBUG=false
APP_URL=https://crm.tu-dominio.com
```

## Docker Local

Host local esperado:

```text
D:\proyectos\crm_laravel_host
```

Servicio:

```text
crm_laravel_host_web
```

Puerto:

```text
8088
```

Comandos utiles desde el host Laravel:

```bash
docker compose ps
docker compose exec -T web php artisan route:list --path=crm/whatsapp
docker compose exec -T web php artisan route:list --path=webhooks/meta/whatsapp
docker compose exec -T web php artisan migrate --force
```

## Documentacion Interna

```text
docs/repository/branch-model.md
docs/repository/feature-workflow.md
docs/deployment/local-test.md
docs/deployment/server-test.md
docs/deployment/server-test-webhooks-meta-whatsapp.md
```

## Siguiente Fase

- Endurecer despliegue server-test con HTTPS.
- Configurar app Meta real.
- Desactivar fallback tenant en servidor.
- Agregar pruebas feature para GET/POST webhook.
- Implementar envio real de mensajes por Graph API.
- Implementar Embedded Signup.
- Preparar flujo completo multitenant.

## Autor

`ycozco`

## Licencia

MIT. Ver [LICENSE.md](LICENSE.md).
