# Server Test

`server-test` representa el entorno de pruebas en servidor/staging.

## Objetivo

Validar el CRM en un entorno publico con HTTPS antes de llevarlo a produccion.

## URL objetivo

```text
CRM:
https://crm.midominio.com/crm

Webhook Meta:
https://crm.midominio.com/api/webhooks/meta/whatsapp
```

## Requisitos

```text
[ ] Servidor Linux con Docker
[ ] Dominio o subdominio apuntando al servidor
[ ] HTTPS activo
[ ] Nginx o Nginx Proxy Manager
[ ] MySQL persistente
[ ] Redis persistente
[ ] Queue worker
[ ] Scheduler
[ ] Logs persistentes
```

## Variables minimas

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://crm.midominio.com

LARAVEL_CRM_ROUTE_PREFIX=crm
LARAVEL_CRM_USER_INTERFACE=true

META_WHATSAPP_ENABLED=true
META_WHATSAPP_GRAPH_VERSION=v21.0
META_WHATSAPP_APP_ID=
META_WHATSAPP_APP_SECRET=
META_WHATSAPP_WEBHOOK_VERIFY_TOKEN=
META_WHATSAPP_WEBHOOK_PATH=/api/webhooks/meta/whatsapp
```

## Criterio minimo para Meta

```text
[ ] GET /api/webhooks/meta/whatsapp responde hub.challenge
[ ] POST /api/webhooks/meta/whatsapp responde 200 rapido
[ ] Firma X-Hub-Signature-256 validada
[ ] Payloads quedan registrados
[ ] Eventos se asocian por tenant_id
[ ] Logs permiten diagnosticar llamadas de Meta
```

## Relacion con main

`server-test` no debe tener una version distinta del frontend.

El frontend entra por:

```text
feature/* -> main -> server-test
```

Solo la configuracion de servidor debe ser propia de esta rama.

