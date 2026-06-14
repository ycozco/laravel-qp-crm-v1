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
https://crm.midominio.com/api/webhooks/meta/whatsapp
```

Para desarrollo local se conserva:

```text
http://127.0.0.1:8088/crm
http://127.0.0.1:8088/api/webhooks/meta/whatsapp
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
META_WHATSAPP_WEBHOOK_PATH=/api/webhooks/meta/whatsapp
```

Regla: los access tokens por tenant no deben vivir en `.env`. Deben guardarse cifrados por tenant en base de datos.

## Webhooks Meta WhatsApp

### GET de verificacion

Ruta:

```text
GET /api/webhooks/meta/whatsapp
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
POST /api/webhooks/meta/whatsapp
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
No guardar eventos sin tenant_id salvo en tabla de errores/auditoria
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
tenant_id nullable
phone_number_id nullable
waba_id nullable
event_type
payload_json
signature_valid
processed_at nullable
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
meta_message_id nullable
direction
type
body nullable
payload_json
status
sent_at nullable
received_at nullable
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
POST /crm/whatsapp/conversations/{conversation}/messages
```

Primera version de UI:

```text
Configuracion de cuenta WhatsApp por tenant
Estado conectado/desconectado
Listado de eventos recibidos
Bandeja simple de conversaciones
Detalle de mensajes
Formulario para enviar texto simple
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
Rate limit para /api/webhooks/meta/whatsapp
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
[ ] Crear config/meta-whatsapp.php en host si no existe
[ ] Crear MetaWhatsappWebhookController
[ ] Registrar GET /api/webhooks/meta/whatsapp
[ ] Registrar POST /api/webhooks/meta/whatsapp
[ ] Validar hub.challenge
[ ] Guardar payloads en whatsapp_webhook_events
[ ] Agregar tests feature para GET y POST
```

### Fase 3 - Tenant WhatsApp

```text
[ ] Crear tenants
[ ] Crear tenant_users
[ ] Crear tenant_whatsapp_accounts
[ ] Asociar usuario actual a tenant demo
[ ] Resolver tenant por phone_number_id
[ ] Bloquear eventos sin tenant con auditoria
```

### Fase 4 - Bandeja simple

```text
[ ] Crear whatsapp_conversations
[ ] Crear whatsapp_messages
[ ] Procesar mensajes inbound desde webhook
[ ] Procesar status updates desde webhook
[ ] Pantalla /crm/whatsapp
[ ] Pantalla detalle de conversacion
```

### Fase 5 - Envio de mensajes

```text
[ ] Servicio MetaWhatsappClient
[ ] Enviar texto simple
[ ] Guardar respuesta de Meta
[ ] Actualizar status cuando llegue webhook
[ ] Mostrar errores de envio en UI
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
[ ] GET responde hub.challenge
[ ] POST responde 200
[ ] Logs confirman recepcion
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
