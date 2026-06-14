# Server Test - Plan de despliegue y webhooks Meta WhatsApp

## Estado actual

`crm_laravel` ya funciona localmente como paquete instalado en un host Laravel separado:

```text
Host app local: D:\proyectos\crm_laravel_host
URL local: http://127.0.0.1:8088/crm
MySQL local: localhost:33068
Redis local: localhost:63798
```

La rama `server-test` se usara para preparar una version orientada a servidor. Esta version debe convertir la configuracion local en un despliegue accesible por HTTPS y agregar los webhooks necesarios para Meta WhatsApp.

## Objetivo de la rama

Construir una version verificable en servidor con:

```text
Backend Laravel desplegado por Docker
Dominio/subdominio publico con HTTPS
Endpoint GET para verificacion de webhook Meta
Endpoint POST para recibir eventos Meta
Registro seguro de payloads
Modelo minimo multitenant para WhatsApp
Pantalla basica CRM para revisar configuracion y eventos
```

## URL objetivo en servidor

Ejemplo recomendado:

```text
https://crm.midominio.com/crm
https://crm.midominio.com/webhooks/meta/whatsapp
```

Para desarrollo local se conserva:

```text
http://127.0.0.1:8088/crm
http://127.0.0.1:8088/webhooks/meta/whatsapp
```

## Variables de entorno requeridas

Agregar al `.env` del host Laravel:

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
META_WHATSAPP_WEBHOOK_PATH=/webhooks/meta/whatsapp
META_WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=true
META_WHATSAPP_WEBHOOK_ALLOW_FALLBACK_TENANT=false
```

Regla: los access tokens por tenant no deben vivir en `.env`. Deben guardarse cifrados por tenant en base de datos.

## Webhooks Meta WhatsApp

### GET de verificacion

Ruta:

```text
GET /webhooks/meta/whatsapp
```

Responsabilidad:

```text
Leer hub.mode
Leer hub.verify_token
Leer hub.challenge
Comparar hub.verify_token con META_WHATSAPP_WEBHOOK_VERIFY_TOKEN
Responder hub.challenge con HTTP 200 si coincide
Responder HTTP 403 si no coincide
```

### POST de eventos

Ruta:

```text
POST /webhooks/meta/whatsapp
```

Responsabilidad:

```text
Validar firma X-Hub-Signature-256 con META_WHATSAPP_APP_SECRET
Responder 200 rapido
Persistir payload original
Detectar phone_number_id / waba_id
Resolver tenant asociado
Crear o actualizar conversacion
Guardar mensajes entrantes
Guardar estados de mensajes enviados
Guardar errores de Meta en mensajes y eventos
No guardar eventos sin tenant_id salvo en tabla de errores/auditoria
```

## Estado implementado en server-test

```text
[x] Endpoint publico configurable por META_WHATSAPP_WEBHOOK_PATH
[x] GET hub.challenge con META_WHATSAPP_WEBHOOK_VERIFY_TOKEN o token por cuenta
[x] POST JSON para eventos messages, statuses, history y account events genericos
[x] Validacion X-Hub-Signature-256 cuando META_WHATSAPP_APP_SECRET existe
[x] Modo estricto con META_WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=true
[x] Resolucion tenant por metadata.phone_number_id o WABA id
[x] Registro bruto en whatsapp_webhook_events
[x] Creacion/actualizacion de conversaciones y mensajes inbound/outbound
[x] Actualizacion de estados sent/delivered/read/failed
[x] Captura de errores Meta en error_code, error_title y error_details
[x] Frontend muestra callback URL, firma, eventos, conteo procesado y errores
```

## Tablas minimas

### tenants

Si el core CRM no tiene tenant propio aun, crear una tabla inicial:

```text
id
name
slug
status
created_at
updated_at
```

### tenant_users

```text
id
tenant_id
user_id
role
created_at
updated_at
```

Roles iniciales:

```text
superadmin
admin
agent
```

### tenant_whatsapp_accounts

```text
id
tenant_id
meta_business_id
waba_id
phone_number_id
display_phone_number
access_token_encrypted
status
connected_at
last_error
created_at
updated_at
```

Estados:

```text
connected
disconnected
error
pending
```

### whatsapp_webhook_events

```text
id
tenant_id
tenant_whatsapp_account_id nullable
phone_number_id nullable
event_type
field nullable
payload_json
signature_valid
processed_at nullable
processed_count
processing_status
error_message nullable
created_at
updated_at
```

### whatsapp_conversations

```text
id
tenant_id
phone_number_id
customer_phone
customer_name nullable
last_message_at nullable
status
created_at
updated_at
```

### whatsapp_messages

```text
id
tenant_id
conversation_id
webhook_event_id nullable
meta_message_id nullable
direction
type
body nullable
payload_json
status
error_code nullable
error_title nullable
error_details nullable
sent_at nullable
created_at
updated_at
```

Valores de `direction`:

```text
inbound
outbound
```

Estados iniciales:

```text
received
queued
sent
delivered
read
failed
```

### whatsapp_templates

```text
id
tenant_id
waba_id
name
language
category nullable
status
payload_json nullable
created_at
updated_at
```

## Rutas CRM minimas

Pantallas dentro del CRM:

```text
GET /crm/whatsapp
GET /crm/whatsapp/settings
GET /crm/whatsapp/conversations
GET /crm/whatsapp/conversations/{conversation}
GET /crm/whatsapp/events
```

Primera version de UI:

```text
Configuracion de cuenta WhatsApp por tenant
Estado conectado/desconectado
Listado de eventos recibidos
Bandeja simple de conversaciones
Detalle de mensajes
Errores de Meta visibles en detalle de conversacion y eventos
```

## API interna de envio

Ruta propuesta:

```text
POST /crm/whatsapp/conversations/{conversation}/messages
```

Flujo:

```text
Validar tenant del usuario
Validar que la conversacion pertenece al tenant
Obtener tenant_whatsapp_account por phone_number_id
Descifrar access_token_encrypted
Enviar a Graph API /{phone_number_id}/messages
Guardar respuesta Meta
Guardar meta_message_id
Marcar mensaje como sent o failed
```

## Seguridad minima

Obligatorio antes de exponer a servidor:

```text
HTTPS activo
APP_DEBUG=false
Firma X-Hub-Signature-256 validada
Tokens cifrados con Crypt::encryptString
Nunca imprimir tokens completos en logs
Rate limit para /webhooks/meta/whatsapp
Logs separados para eventos webhook
Todas las consultas WhatsApp filtradas por tenant_id
No mezclar datos entre tenants
Backups de MySQL
```

## Docker para server-test

Crear un compose de servidor separado del workspace de paquete:

```text
infra/docker/compose.server-test.yml
```

Servicios propuestos:

```text
app        PHP-FPM o Laravel Octane/serve para prueba
nginx      publica 80/443 o queda detras de Nginx Proxy Manager
mysql      MySQL 8.4
redis      Redis 7
queue      php artisan queue:work
scheduler  php artisan schedule:work
```

Puertos sugeridos para pruebas sin proxy:

```text
8088 -> app/nginx
33068 -> mysql
63798 -> redis
```

En servidor real, el trafico publico debe entrar por Nginx Proxy Manager o Nginx:

```text
443 -> nginx/proxy -> app
```

## Fases de implementacion

### Fase 1 - Base servidor

```text
[ ] Crear compose.server-test.yml
[ ] Crear Dockerfile/entrypoint para host Laravel
[ ] Documentar .env.server-test.example
[ ] Validar /crm en servidor local por 127.0.0.1:8088
[ ] Validar migraciones fresh en MySQL
```

### Fase 2 - Webhook Meta minimo

```text
[x] Crear config/meta-whatsapp.php en host si no existe
[x] Crear WhatsappWebhookController
[x] Registrar GET /webhooks/meta/whatsapp
[x] Registrar POST /webhooks/meta/whatsapp
[x] Validar hub.challenge
[x] Guardar payloads en whatsapp_webhook_events
[ ] Agregar tests feature para GET y POST
```

### Fase 3 - Tenant WhatsApp

```text
[x] Crear tenants
[x] Crear tenant_users
[x] Crear tenant_whatsapp_accounts
[x] Asociar usuario actual a tenant demo
[x] Resolver tenant por phone_number_id
[ ] Bloquear eventos sin tenant con auditoria
```

### Fase 4 - Bandeja simple

```text
[x] Crear whatsapp_conversations
[x] Crear whatsapp_messages
[x] Procesar mensajes inbound desde webhook
[x] Procesar status updates desde webhook
[x] Pantalla /crm/whatsapp
[x] Pantalla detalle de conversacion
```

### Fase 5 - Envio de mensajes

```text
[ ] Servicio MetaWhatsappClient
[ ] Enviar texto simple
[ ] Guardar respuesta de Meta
[ ] Actualizar status cuando llegue webhook
[x] Mostrar errores de envio/status en UI cuando llegan por webhook
```

### Fase 6 - Preparacion Meta Review

```text
[ ] Servidor publico HTTPS
[ ] Tenant demo
[ ] Usuario admin demo
[ ] Usuario agente demo
[ ] Cuenta WhatsApp test conectada
[ ] Conversacion demo visible
[ ] Logs de webhook visibles en servidor
[ ] Video o pasos de prueba para Meta
```

## Datos demo para server-test

```text
URL local:
http://127.0.0.1:8088/crm

Usuario owner:
admin@crm-laravel.local
Secret123!

Usuario agente:
sales@crm-laravel.local
Secret123!
```

## Criterio de listo para Meta

Minimo para presionar "Verificar y guardar" en Meta:

```text
[ ] URL publica HTTPS del webhook
[x] GET responde hub.challenge
[x] POST responde 200
[x] Eventos confirman recepcion en CRM
[ ] Verify token coincide con Meta
```

Minimo para revision de permisos WhatsApp:

```text
[ ] Tenant demo funcional
[ ] WhatsApp conectado por tenant
[ ] Bandeja muestra mensajes recibidos
[ ] App envia mensaje o plantilla
[ ] Payloads y estados quedan guardados
[ ] No hay tokens visibles en frontend ni logs
```
