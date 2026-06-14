# Plan Server Test - despliegue realista de `laravel-crm` en `qpsecure.cloud`

> Validado contra este repositorio en la rama `server-test`.
> Este proyecto es un paquete Laravel y el despliegue ejecutable actual crea
> una sola host app Laravel dentro del build Docker.

## Resumen verificado

- `venturedrake/laravel-crm` es una libreria Laravel, no una app standalone.
- El despliegue actual ya resuelve esto con `infra/docker/Dockerfile.server`.
- La libreria si incluye UI interna lista para `/crm` y el modulo WhatsApp:
  - `/crm/whatsapp`
  - `/crm/whatsapp/settings`
  - `/crm/whatsapp/conversations`
  - `/crm/whatsapp/conversations/{conversation}`
  - `/crm/whatsapp/events`
- La libreria si incluye endpoints API consumibles hoy:
  - `POST /crm/api/v2/auth/token`
  - `GET /crm/api/v2/auth/me`
  - `DELETE /crm/api/v2/auth/token`
  - REST v2 de `leads`, `products`, `organizations`, `people`, `deals`,
    `quotes`, `orders`, `invoices`
- La libreria si incluye webhook Meta WhatsApp real:
  - `GET /webhooks/meta/whatsapp`
  - `POST /webhooks/meta/whatsapp`
- La libreria no incluye todavia:
  - frontend desacoplado real para `crm1app.qpsecure.cloud`
  - app admin separada distinta del CRM interno
  - API REST propia para `tenants`, `tenant users`, `tenant whatsapp accounts`,
    `whatsapp events` o `whatsapp conversations`
  - UI CRUD completa para editar credenciales Meta por tenant
  - sistema de webhooks salientes de eventos CRM
  - Embedded Signup o envio real a Meta

## Decision de despliegue para `server-test`

El despliegue correcto en esta fase es una sola host app Laravel con un solo
stack Docker:

```text
crm1_web
crm1_queue
crm1_scheduler
crm1_mysql
crm1_redis
```

`crm1_web` sera el unico servicio HTTP expuesto a la red de Nginx Proxy
Manager. MySQL, Redis, queue y scheduler quedaran solo en la red privada
interna.

## Mapa de subdominios corregido

```text
admincrm1.qpsecure.cloud
Host principal del CRM interno. Aqui vive la UI de /crm.

crmapi.qpsecure.cloud
Host logico para la API y el webhook, pero apuntando al mismo backend
crm1_web en esta fase.

crm1.qpsecure.cloud
Host de entrada del proyecto: redireccion a admin o landing branded si luego
se personaliza la raiz del host app.

crm1app.qpsecure.cloud
Reservado. No debe documentarse como app desplegada hasta que exista un
frontend desacoplado real fuera de la libreria.
```

## Rutas publicas esperadas

### CRM interno

La UI se montara con:

```text
LARAVEL_CRM_ROUTE_SUBDOMAIN=admincrm1
LARAVEL_CRM_ROUTE_PREFIX=crm
```

Por tanto, las rutas validas de UI quedan en:

```text
https://admincrm1.qpsecure.cloud/crm/login
https://admincrm1.qpsecure.cloud/crm
https://admincrm1.qpsecure.cloud/crm/whatsapp
https://admincrm1.qpsecure.cloud/crm/whatsapp/settings
https://admincrm1.qpsecure.cloud/crm/whatsapp/conversations
https://admincrm1.qpsecure.cloud/crm/whatsapp/events
```

### API existente del paquete

La API seguira expuesta por el mismo contenedor:

```text
https://crmapi.qpsecure.cloud/crm/api/v2/auth/token
https://crmapi.qpsecure.cloud/crm/api/v2/auth/me
https://crmapi.qpsecure.cloud/crm/api/v2/leads
https://crmapi.qpsecure.cloud/crm/api/v2/products
https://crmapi.qpsecure.cloud/crm/api/v2/organizations
https://crmapi.qpsecure.cloud/crm/api/v2/people
https://crmapi.qpsecure.cloud/crm/api/v2/deals
https://crmapi.qpsecure.cloud/crm/api/v2/quotes
https://crmapi.qpsecure.cloud/crm/api/v2/orders
https://crmapi.qpsecure.cloud/crm/api/v2/invoices
```

### Webhook Meta WhatsApp

Tambien sobre el mismo backend:

```text
GET  https://crmapi.qpsecure.cloud/webhooks/meta/whatsapp
POST https://crmapi.qpsecure.cloud/webhooks/meta/whatsapp
```

## Red Docker y proxy

### Redes

```text
main_npm_network   externa, compartida con Nginx Proxy Manager
crm1_internal      privada del stack CRM
```

### Servicios por red

```text
En main_npm_network:
crm1_web

En crm1_internal:
crm1_web
crm1_mysql
crm1_redis
crm1_queue
crm1_scheduler
```

### Reglas

- Solo `crm1_web` entra a la red del proxy.
- DB y Redis nunca se publican.
- No hace falta crear contenedores nuevos para `admincrm1` y `crmapi`; ambos
  pueden enrutar al mismo `crm1_web`.

## Nginx Proxy Manager

Crear estos Proxy Hosts:

```text
Domain Names: admincrm1.qpsecure.cloud
Forward Hostname / IP: crm1_web
Forward Port: 80

Domain Names: crmapi.qpsecure.cloud
Forward Hostname / IP: crm1_web
Forward Port: 80
```

Para `crm1.qpsecure.cloud` en esta fase:

```text
Opcion A: redireccion 301 a https://admincrm1.qpsecure.cloud/crm
Opcion B: apuntar al mismo crm1_web y luego personalizar la raiz del host app
```

No publicar `crm1app.qpsecure.cloud` todavia.

SSL recomendado:

```text
Force SSL: enabled
HTTP/2 Support: enabled
Block Common Exploits: enabled
Websockets Support: enabled
HSTS: activarlo cuando todo este validado
```

Advanced:

```nginx
client_max_body_size 20m;
proxy_read_timeout 120s;
proxy_connect_timeout 120s;
proxy_send_timeout 120s;
```

## Variables de entorno para `server-test`

Archivo objetivo:

```text
.env.server-test
```

Base recomendada:

```text
APP_NAME="QP CRM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://admincrm1.qpsecure.cloud
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=crm1_mysql
DB_PORT=3306
DB_DATABASE=crm1
DB_USERNAME=crm1
DB_PASSWORD=<password-seguro>

REDIS_HOST=crm1_redis
REDIS_PORT=6379

SESSION_DRIVER=redis
SESSION_DOMAIN=.qpsecure.cloud
SESSION_SECURE_COOKIE=true

LARAVEL_CRM_OWNER=admincrm1@qpsecure.cloud
LARAVEL_CRM_ROUTE_SUBDOMAIN=admincrm1
LARAVEL_CRM_ROUTE_PREFIX=crm
LARAVEL_CRM_USER_INTERFACE=true
```

WhatsApp:

```text
META_WHATSAPP_ENABLED=true
META_WHATSAPP_GRAPH_VERSION=v21.0
META_WHATSAPP_APP_ID=
META_WHATSAPP_APP_SECRET=
META_WHATSAPP_WEBHOOK_VERIFY_TOKEN=<token-largo-aleatorio>
META_WHATSAPP_WEBHOOK_PATH=/webhooks/meta/whatsapp
META_WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=true
META_WHATSAPP_WEBHOOK_ALLOW_FALLBACK_TENANT=false
```

### CORS corregido

Regla operativa:

- si solo se usa la UI interna en `admincrm1`, casi no hace falta CORS
- si el navegador consulta `crmapi.qpsecure.cloud`, permitir solo los hosts
  realmente activos

Valor recomendado en esta fase:

```text
CORS_ALLOWED_ORIGINS=https://admincrm1.qpsecure.cloud,https://crm1.qpsecure.cloud
```

No incluir `crm1app.qpsecure.cloud` hasta que exista una app real.

## Compose real usado por este repo

Archivo:

```text
infra/docker/compose.server-test.yml
```

El estado deseado es:

- una sola imagen `crm1_qpsecure_app:server-test`
- `crm1_web` corriendo la host app Laravel
- `crm1_queue` y `crm1_scheduler` reutilizando la misma imagen
- `crm1_mysql` y `crm1_redis` como servicios internos

No hace falta redefinir otro compose paralelo para separar frontend/API en esta
fase porque el repo todavia no incluye esas apps.

## Webhook Meta WhatsApp

Configurar en Meta Developers:

```text
Callback URL:
https://crmapi.qpsecure.cloud/webhooks/meta/whatsapp

Verify token:
META_WHATSAPP_WEBHOOK_VERIFY_TOKEN

Campo:
messages
```

Validacion esperada:

```text
GET /webhooks/meta/whatsapp?hub.mode=subscribe&hub.challenge=123&hub.verify_token=<token>
-> HTTP 200
-> body: 123
```

Y para POST firmado:

```text
HTTP 200
{"success":true,"processed":N}
```

## Flujo detallado de datos: API key, token y webhook

### 1. Que credenciales existen de verdad en este proyecto

No hay una sola `apikey` unica del lado CRM. El flujo actual separa:

```text
Credenciales globales de la app Meta
- META_WHATSAPP_APP_ID
- META_WHATSAPP_APP_SECRET
- META_WHATSAPP_WEBHOOK_VERIFY_TOKEN

Credenciales por tenant / cuenta WhatsApp Business
- app_id
- business_account_id
- phone_number_id
- phone_number
- webhook_verify_token
- access_token
```

Regla operativa:

- `APP_SECRET` valida la firma `X-Hub-Signature-256`
- `VERIFY_TOKEN` valida el challenge GET del webhook
- `ACCESS_TOKEN` sirve para futuras llamadas salientes a Graph API
- `phone_number_id` y `business_account_id` permiten resolver a que tenant
  pertenece cada evento entrante

### 2. Donde vive cada dato

```text
En .env.server-test
- META_WHATSAPP_APP_ID
- META_WHATSAPP_APP_SECRET
- META_WHATSAPP_WEBHOOK_VERIFY_TOKEN
- META_WHATSAPP_WEBHOOK_PATH
- META_WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE
- META_WHATSAPP_WEBHOOK_ALLOW_FALLBACK_TENANT
```

```text
En la base de datos del CRM por tenant / cuenta
- display_name
- app_id
- business_account_id
- phone_number_id
- phone_number
- webhook_verify_token
- access_token_encrypted
- status
```

### 3. Flujo completo de configuracion en server-test

#### Paso A. Preparar entorno del backend

Completar en `.env.server-test`:

```text
APP_URL=https://admincrm1.qpsecure.cloud
META_WHATSAPP_ENABLED=true
META_WHATSAPP_APP_ID=<meta-app-id>
META_WHATSAPP_APP_SECRET=<meta-app-secret>
META_WHATSAPP_WEBHOOK_VERIFY_TOKEN=<token-largo-y-unico>
META_WHATSAPP_WEBHOOK_PATH=/webhooks/meta/whatsapp
META_WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=true
META_WHATSAPP_WEBHOOK_ALLOW_FALLBACK_TENANT=false
```

#### Paso B. Desplegar stack y publicar hosts

Publicar en NPM:

```text
admincrm1.qpsecure.cloud -> crm1_web:80
crmapi.qpsecure.cloud -> crm1_web:80
```

Con esto quedan expuestos:

```text
UI interna:
https://admincrm1.qpsecure.cloud/crm/whatsapp/settings

Webhook real:
https://crmapi.qpsecure.cloud/webhooks/meta/whatsapp
```

#### Paso C. Registrar tenant y cuenta en el CRM

Desde:

```text
https://admincrm1.qpsecure.cloud/crm/whatsapp/settings
```

Cargar:

```text
Tenant:
- nombre
- slug
- estado

Cuenta WhatsApp:
- display name
- app_id
- business_account_id
- phone_number_id
- phone_number
- webhook_verify_token
- access_token
- status=connected cuando quede validada
```

Nota:

- `access_token` se guarda cifrado
- `webhook_verify_token` puede coincidir con el global o ser especifico por
  cuenta

#### Paso D. Configurar Meta Developers

En la app de Meta:

```text
Webhook callback URL
https://crmapi.qpsecure.cloud/webhooks/meta/whatsapp

Verify token
<META_WHATSAPP_WEBHOOK_VERIFY_TOKEN o token de la cuenta>

Campo suscrito
messages
```

#### Paso E. Verificacion GET

Meta enviara algo como:

```text
GET /webhooks/meta/whatsapp
  ?hub.mode=subscribe
  &hub.challenge=123456
  &hub.verify_token=<token>
```

El backend:

```text
1. Lee hub.mode, hub.challenge y hub.verify_token
2. Compara el token contra:
   - config('meta-whatsapp.webhook.verify_token')
   - o cualquier tenant_whatsapp_account.webhook_verify_token
3. Si coincide responde 200 con el challenge plano
4. Si no coincide responde 403
```

#### Paso F. Recepcion POST firmada

Meta enviara:

```text
POST /webhooks/meta/whatsapp
Header: X-Hub-Signature-256=sha256=...
Body JSON con entry[].changes[]
```

El backend procesa asi:

```text
1. Valida la firma con META_WHATSAPP_APP_SECRET
2. Lee entry.id y change.value.metadata.phone_number_id
3. Busca la cuenta por:
   - phone_number_id
   - si no existe, business_account_id
4. Resuelve el tenant propietario
5. Guarda un registro en whatsapp_webhook_events
6. Recorre:
   - messages[]
   - statuses[]
   - history[].threads[].messages[]
7. Crea o actualiza:
   - conversaciones
   - mensajes
   - estados de entrega
8. Responde:
   {"success":true,"processed":N}
```

### 4. Flujo de datos resumido

```text
Meta Developers
  -> GET challenge
  -> POST webhook firmado

Nginx Proxy Manager
  -> crmapi.qpsecure.cloud

crm1_web
  -> /webhooks/meta/whatsapp
  -> valida token / firma
  -> resuelve tenant por phone_number_id o business_account_id
  -> persiste eventos, conversaciones y mensajes en MySQL

UI CRM interna
  -> admincrm1.qpsecure.cloud/crm/whatsapp/settings
  -> administra tenant, token y IDs de Meta
```

### 5. Checklist practico de configuracion

```text
[ ] APP_URL apunta a admincrm1.qpsecure.cloud
[ ] META_WHATSAPP_APP_SECRET cargado en .env.server-test
[ ] META_WHATSAPP_WEBHOOK_VERIFY_TOKEN cargado en .env.server-test
[ ] crmapi.qpsecure.cloud publicado en NPM hacia crm1_web:80
[ ] callback URL en Meta = https://crmapi.qpsecure.cloud/webhooks/meta/whatsapp
[ ] verify token de Meta coincide con el configurado
[ ] cuenta tenant tiene phone_number_id y business_account_id
[ ] access_token fue guardado desde /crm/whatsapp/settings
[ ] GET challenge devuelve HTTP 200
[ ] POST firmado devuelve {"success":true,"processed":N}
```

## Pasos de despliegue

### 1. DNS

Crear o validar:

```text
admincrm1.qpsecure.cloud -> IP publica del servidor
crmapi.qpsecure.cloud -> IP publica del servidor
crm1.qpsecure.cloud -> IP publica del servidor
crm1app.qpsecure.cloud -> IP publica del servidor
```

`crm1app` puede existir a nivel DNS, pero no debe publicarse todavia en NPM.

### 2. Red de Nginx Proxy Manager

Confirmar la red externa real:

```bash
docker network ls
```

En este repo la referencia actual es:

```text
main_npm_network
```

### 3. Codigo

```bash
git clone https://github.com/ycozco/laravel-qp-crm-v1.git crm1
cd crm1
git checkout server-test
```

### 4. Entorno

```bash
cp docs/deployment/env.server-test.example .env.server-test
```

Completar secretos y dejar:

- `APP_URL=https://admincrm1.qpsecure.cloud`
- `LARAVEL_CRM_ROUTE_SUBDOMAIN=admincrm1`
- `CORS_ALLOWED_ORIGINS` solo con hosts activos

### 5. Build y arranque

```bash
docker compose --env-file .env.server-test -f infra/docker/compose.server-test.yml build
docker compose --env-file .env.server-test -f infra/docker/compose.server-test.yml up -d
```

### 6. Bootstrap

El contenedor web ya ejecuta bootstrap automatico:

- genera `APP_KEY` si falta
- corre migraciones
- ejecuta `php artisan laravelcrm:install`
- crea el owner definido por `CRM_OWNER_*`

### 7. Proxy Hosts

Configurar:

```text
admincrm1.qpsecure.cloud -> crm1_web:80
crmapi.qpsecure.cloud -> crm1_web:80
crm1.qpsecure.cloud -> redirect a admincrm1 o mismo backend con landing futura
```

No publicar:

```text
crm1app.qpsecure.cloud
```

### 8. Validacion funcional

UI:

```text
https://admincrm1.qpsecure.cloud/crm/login
https://admincrm1.qpsecure.cloud/crm
https://admincrm1.qpsecure.cloud/crm/whatsapp
```

API:

```text
https://crmapi.qpsecure.cloud/crm/api/v2/auth/token
https://crmapi.qpsecure.cloud/crm/api/v2/auth/me
```

Webhook:

```bash
curl "https://crmapi.qpsecure.cloud/webhooks/meta/whatsapp?hub.mode=subscribe&hub.challenge=ok123&hub.verify_token=<token>"
```

## Credenciales y usuarios

Las credenciales garantizadas por `server-test` son solo las del owner creado
por bootstrap:

```text
CRM_OWNER_NAME
CRM_OWNER_EMAIL
CRM_OWNER_PASSWORD
```

Los usuarios demo `admin@crm-laravel.local` y `sales@crm-laravel.local` estan
garantizados en `local-test`, pero no en `server-test` salvo que se ejecute un
seeder adicional.

Ver `data-prueba.md`.

## Checklist de aceptacion

```text
[ ] admincrm1.qpsecure.cloud responde por HTTPS
[ ] crmapi.qpsecure.cloud responde por HTTPS
[ ] crm1.qpsecure.cloud redirige o sirve la raiz esperada
[ ] crm1app.qpsecure.cloud no se publica todavia
[ ] /crm/login funciona en admincrm1
[ ] /crm/whatsapp carga dashboard
[ ] /crm/whatsapp/settings muestra callback URL HTTPS
[ ] /crm/whatsapp/conversations lista conversaciones
[ ] /crm/whatsapp/events lista eventos
[ ] POST /crm/api/v2/auth/token emite token
[ ] GET /crm/api/v2/auth/me responde con Bearer valido
[ ] GET webhook devuelve hub.challenge
[ ] POST webhook firmado crea o actualiza eventos y mensajes
[ ] CORS no usa wildcard
[ ] DB y Redis no quedan expuestos al publico
```

## Riesgos y limites conocidos

### `crm1app` aun no existe

Riesgo:

```text
Documentar crm1app como app operativa causaria confusion de despliegue.
```

Mitigacion:

```text
Dejarlo reservado a nivel DNS y fuera de Nginx Proxy Manager en esta fase.
```

### `crmapi` no es un backend separado

Riesgo:

```text
Pensar que crmapi tiene contenedor propio cuando hoy comparte crm1_web.
```

Mitigacion:

```text
Documentarlo explicitamente como host logico sobre el mismo backend.
```

### Credenciales demo no garantizadas en servidor

Riesgo:

```text
Esperar usuarios de prueba no creados por bootstrap.
```

Mitigacion:

```text
Usar solo el owner garantizado o crear un seeder especifico para server-test.
```

## Estado deseado al final

```text
Una sola host app Laravel desplegada
admincrm1.qpsecure.cloud como CRM interno principal
crmapi.qpsecure.cloud como alias funcional para API y webhook
crm1.qpsecure.cloud como entrada o redireccion
crm1app.qpsecure.cloud reservado para fase posterior
Modulo WhatsApp visible y funcional en UI interna
Webhook Meta verificado y procesando eventos
DB, Redis, queue y scheduler privados
Nginx Proxy Manager como unico punto publico 80/443
```
