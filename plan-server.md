# Plan Server Test - CRM Laravel en subdominios qpsecure.cloud

> Validado contra este repositorio: `venturedrake/laravel-crm` es un paquete
> Laravel, no una app completa. La implementacion ejecutable para `server-test`
> quedo documentada en `docs/deployment/server-test-implementation.md` y genera
> el host app Laravel dentro del build Docker para poder publicar `/crm`.

## Objetivo

Preparar la rama `server-test` para desplegar el CRM Laravel en servidor usando este mapa de subdominios:

```text
crm1.qpsecure.cloud
crm1app.qpsecure.cloud
admincrm1.qpsecure.cloud
crmapi.qpsecure.cloud
```

El despliegue debe funcionar detras de Nginx Proxy Manager, mantener las bases de datos fuera de exposicion publica, permitir pruebas del modulo WhatsApp y dejar listo el endpoint de webhooks Meta WhatsApp por HTTPS.

## Mapa de Subdominios

Uso propuesto para cada host:

```text
crm1.qpsecure.cloud
Host principal del CRM Laravel y modulo WhatsApp.

crm1app.qpsecure.cloud
Frontend publico o app cliente separada que consume APIs.

admincrm1.qpsecure.cloud
Frontend administrativo separado, si se decide desacoplar panel admin del host principal.

crmapi.qpsecure.cloud
API publica o privada consumida por los frontends del ecosistema.
```

Recomendacion operativa:

- Mantener `crm1.qpsecure.cloud` como host principal del CRM Laravel.
- Usar `crmapi.qpsecure.cloud` para APIs desacopladas que deban ser consumidas por otros frontends.
- Usar `crm1app.qpsecure.cloud` y `admincrm1.qpsecure.cloud` solo si de verdad van a existir como apps separadas.
- Si admin y CRM viven en la misma app Laravel, no hace falta duplicarlos en hosts distintos todavia.

## Decision Arquitectonica

### Recomendacion para Nginx Proxy Manager

Si actualmente el servidor usa Nginx Proxy Manager como punto unico de entrada, es recomendable mantenerlo asi.

Patron recomendado:

```text
Internet
  |
  | HTTPS 443
  v
Nginx Proxy Manager
  |
  | red docker compartida proxy
  v
crm1 web/app container
  |
  | red privada interna crm
  v
mysql / redis / queue / scheduler
```

Motivo:

- Solo Nginx Proxy Manager expone puertos publicos `80/443`.
- Las apps no publican puertos host salvo que sea una prueba temporal.
- MySQL y Redis quedan en red privada, sin acceso publico.
- Cada frontend o API se agrega a la red del proxy solo si necesita recibir trafico HTTP/HTTPS externo.
- La terminacion TLS queda centralizada en Nginx Proxy Manager.

No es recomendable agregar MySQL, Redis, workers o servicios internos a la red de Nginx Proxy Manager.

### Sobre CORS

Si las apps frontend consumen APIs desde el navegador, CORS es necesario cuando frontend y API tienen origen distinto.

Ejemplos donde hay CORS:

```text
https://crm1app.qpsecure.cloud   -> https://crmapi.qpsecure.cloud
https://admincrm1.qpsecure.cloud -> https://crmapi.qpsecure.cloud
https://crm1.qpsecure.cloud      -> https://crmapi.qpsecure.cloud
```

Ejemplos donde no deberia necesitarse CORS:

```text
https://crm1.qpsecure.cloud/crm
https://crm1.qpsecure.cloud/webhooks/meta/whatsapp
https://crm1.qpsecure.cloud/api/...
https://crmapi.qpsecure.cloud si frontend y API se sirven detras del mismo host no aplica, pero con subdominios separados si aplica
```

Recomendacion:

- Para este CRM, preferir mismo origen siempre que sea posible: `crm1.qpsecure.cloud`.
- Si se va a separar frontend y API, el par recomendado es `crm1app.qpsecure.cloud` o `admincrm1.qpsecure.cloud` consumiendo `crmapi.qpsecure.cloud`.
- Para APIs consumidas por otros frontends, permitir CORS solo por dominios concretos, no `*`.
- Si se usan cookies/sesion, configurar `supports_credentials=true` y no usar wildcard.
- Si se usan tokens Bearer, CORS sigue aplicando en navegador, pero no requiere cookies.
- Los webhooks Meta no dependen de CORS porque son llamadas servidor a servidor.

## Dominio y Rutas

Hosts publicos:

```text
https://crm1.qpsecure.cloud
https://crm1app.qpsecure.cloud
https://admincrm1.qpsecure.cloud
https://crmapi.qpsecure.cloud
```

CRM principal:

```text
https://crm1.qpsecure.cloud/crm
```

Modulo WhatsApp:

```text
https://crm1.qpsecure.cloud/crm/whatsapp
https://crm1.qpsecure.cloud/crm/whatsapp/settings
https://crm1.qpsecure.cloud/crm/whatsapp/conversations
https://crm1.qpsecure.cloud/crm/whatsapp/events
```

Webhook Meta WhatsApp:

```text
GET  https://crm1.qpsecure.cloud/webhooks/meta/whatsapp
POST https://crm1.qpsecure.cloud/webhooks/meta/whatsapp
```

API separada si se usa:

```text
https://crmapi.qpsecure.cloud
```

## Red Docker

### Red externa del proxy

Identificar el nombre real de la red de Nginx Proxy Manager:

```bash
docker network ls
```

Nombres comunes:

```text
nginx-proxy-manager_default
npm_default
proxy
npm_proxy
```

Para este plan se usara un placeholder:

```text
npm_proxy
```

Si la red real tiene otro nombre, cambiarlo en el compose.

### Redes propuestas

```text
npm_proxy          externa, compartida con Nginx Proxy Manager
crm1_internal      privada, solo servicios del CRM
```

Servicios conectados a `npm_proxy`:

```text
crm1_web
```

Servicios conectados solo a `crm1_internal`:

```text
crm1_mysql
crm1_redis
crm1_queue
crm1_scheduler
```

`crm1_web` debe estar en ambas redes:

```text
npm_proxy
crm1_internal
```

## Nginx Proxy Manager

Crear un Proxy Host para el CRM principal:

```text
Domain Names: crm1.qpsecure.cloud
Scheme: http
Forward Hostname / IP: crm1_web
Forward Port: 8088
```

Si se despliegan apps separadas, crear tambien:

```text
Domain Names: crm1app.qpsecure.cloud
Forward Hostname / IP: <frontend-app-container>
Forward Port: <frontend-port>

Domain Names: admincrm1.qpsecure.cloud
Forward Hostname / IP: <admin-app-container>
Forward Port: <admin-port>

Domain Names: crmapi.qpsecure.cloud
Forward Hostname / IP: <api-container>
Forward Port: <api-port>
```

SSL:

```text
Request a new SSL Certificate
Force SSL: enabled
HTTP/2 Support: enabled
HSTS: enabled solo cuando se confirme que todo funciona
Block Common Exploits: enabled
Websockets Support: enabled
```

Advanced recomendado:

```nginx
client_max_body_size 20m;
proxy_read_timeout 120s;
proxy_connect_timeout 120s;
proxy_send_timeout 120s;
```

No publicar `8088` hacia el host en produccion si Nginx Proxy Manager ya enruta por red Docker. Para pruebas se puede publicar temporalmente en localhost.

## Variables de Entorno

Archivo objetivo:

```text
.env.server-test
```

Base:

```text
APP_NAME="QP CRM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://crm1.qpsecure.cloud
APP_KEY=

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=crm1_mysql
DB_PORT=3306
DB_DATABASE=crm1
DB_USERNAME=crm1
DB_PASSWORD=<password-seguro>

REDIS_HOST=crm1_redis
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DRIVER=database
SESSION_DOMAIN=.qpsecure.cloud
SESSION_SECURE_COOKIE=true

LARAVEL_CRM_ROUTE_PREFIX=crm
LARAVEL_CRM_USER_INTERFACE=true
LARAVEL_CRM_OWNER=admin@crm-laravel.local
```

WhatsApp:

```text
META_WHATSAPP_ENABLED=true
META_WHATSAPP_GRAPH_VERSION=v20.0
META_WHATSAPP_APP_ID=
META_WHATSAPP_APP_SECRET=
META_WHATSAPP_WEBHOOK_VERIFY_TOKEN=<token-largo-aleatorio>
META_WHATSAPP_WEBHOOK_PATH=/webhooks/meta/whatsapp
META_WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=true
META_WHATSAPP_WEBHOOK_ALLOW_FALLBACK_TENANT=false
```

CORS si este CRM o `crmapi.qpsecure.cloud` expone API consumida por otros frontends:

```text
CORS_ALLOWED_ORIGINS=https://crm1.qpsecure.cloud,https://crm1app.qpsecure.cloud,https://admincrm1.qpsecure.cloud
```

Si no hay API consumida por otros frontends, mantener CORS cerrado.

## Compose Objetivo

Archivo sugerido:

```text
infra/docker/compose.server-test.yml
```

Estructura propuesta:

```yaml
name: crm1-qpsecure

services:
  web:
    image: crm1_qpsecure_app
    container_name: crm1_web
    build:
      context: ../..
      dockerfile: infra/docker/Dockerfile.server
    env_file:
      - ../../.env.server-test
    depends_on:
      - mysql
      - redis
    networks:
      - npm_proxy
      - crm1_internal
    command: php artisan serve --host=0.0.0.0 --port=8088
    restart: unless-stopped

  queue:
    image: crm1_qpsecure_app
    container_name: crm1_queue
    env_file:
      - ../../.env.server-test
    depends_on:
      - mysql
      - redis
    networks:
      - crm1_internal
    command: php artisan queue:work --tries=3 --timeout=90
    restart: unless-stopped

  scheduler:
    image: crm1_qpsecure_app
    container_name: crm1_scheduler
    env_file:
      - ../../.env.server-test
    depends_on:
      - mysql
      - redis
    networks:
      - crm1_internal
    command: php artisan schedule:work
    restart: unless-stopped

  mysql:
    image: mysql:8.4
    container_name: crm1_mysql
    environment:
      MYSQL_DATABASE: crm1
      MYSQL_USER: crm1
      MYSQL_PASSWORD: <password-seguro>
      MYSQL_ROOT_PASSWORD: <root-password-seguro>
    volumes:
      - crm1_mysql_data:/var/lib/mysql
    networks:
      - crm1_internal
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    container_name: crm1_redis
    volumes:
      - crm1_redis_data:/data
    networks:
      - crm1_internal
    restart: unless-stopped

networks:
  npm_proxy:
    external: true
  crm1_internal:
    driver: bridge

volumes:
  crm1_mysql_data:
  crm1_redis_data:
```

Nota: para mayor solidez en servidor, luego cambiar `php artisan serve` por Nginx + PHP-FPM u Octane. Para `server-test`, `artisan serve` puede ser suficiente si el objetivo es validar dominio, HTTPS, CRM y webhooks.

## Laravel Detras de Proxy

Verificar configuracion de trusted proxies.

Laravel debe reconocer:

```text
X-Forwarded-Proto: https
X-Forwarded-Host: crm1.qpsecure.cloud
```

Si hay problemas con URLs generadas en `http`, cookies inseguras o redirecciones incorrectas:

- revisar middleware `TrustProxies`.
- forzar `APP_URL=https://crm1.qpsecure.cloud`.
- asegurar `SESSION_SECURE_COOKIE=true`.
- limpiar cache:

```bash
php artisan optimize:clear
php artisan config:cache
```

## CORS Recomendado

Para Laravel, revisar `config/cors.php`.

Recomendacion para APIs:

```php
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'https://crm1.qpsecure.cloud,https://crm1app.qpsecure.cloud,https://admincrm1.qpsecure.cloud')),
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN'],
'supports_credentials' => true,
```

No usar:

```text
allowed_origins = *
supports_credentials = true
```

Para webhooks Meta:

```text
CORS no aplica
```

Meta llama desde servidor, no desde navegador.

## Webhooks Meta WhatsApp

En Meta Developers configurar:

```text
Callback URL:
https://crm1.qpsecure.cloud/webhooks/meta/whatsapp

Verify token:
META_WHATSAPP_WEBHOOK_VERIFY_TOKEN

Campo a suscribir:
messages
```

Validacion esperada:

```text
GET /webhooks/meta/whatsapp?hub.mode=subscribe&hub.challenge=123&hub.verify_token=<token>
```

Debe responder:

```text
HTTP 200
123
```

POST esperado:

```text
HTTP 200
{"success":true,"processed":N}
```

Seguridad:

- `META_WHATSAPP_APP_SECRET` obligatorio en servidor.
- `META_WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=true`.
- `META_WHATSAPP_WEBHOOK_ALLOW_FALLBACK_TENANT=false`.
- Cada cuenta WhatsApp debe tener `phone_number_id` correcto en `tenant_whatsapp_accounts`.

## Pasos de Despliegue

### 1. Preparar DNS

Crear registros:

```text
crm1.qpsecure.cloud -> IP publica del servidor
crm1app.qpsecure.cloud -> IP publica del servidor
admincrm1.qpsecure.cloud -> IP publica del servidor
crmapi.qpsecure.cloud -> IP publica del servidor
```

Esperar propagacion y validar:

```bash
nslookup crm1.qpsecure.cloud
nslookup crm1app.qpsecure.cloud
nslookup admincrm1.qpsecure.cloud
nslookup crmapi.qpsecure.cloud
```

### 2. Preparar red de Nginx Proxy Manager

Confirmar red:

```bash
docker network ls
```

Si no existe red compartida:

```bash
docker network create npm_proxy
```

Nginx Proxy Manager debe estar conectado a esa red.

### 3. Subir codigo

En servidor:

```bash
git clone https://github.com/ycozco/laravel-qp-crm-v1.git crm1
cd crm1
git checkout server-test
```

Si se despliega desde `main`, confirmar que contiene el commit de webhook.

### 4. Crear `.env.server-test`

Copiar plantilla y completar secretos:

```bash
cp docs/deployment/env.server-test.example .env.server-test
```

Agregar variables nuevas de WhatsApp y dominio.

### 5. Construir y levantar

```bash
docker compose -f infra/docker/compose.server-test.yml build
docker compose -f infra/docker/compose.server-test.yml up -d
```

### 6. Inicializar Laravel

```bash
docker compose -f infra/docker/compose.server-test.yml exec web php artisan key:generate --force
docker compose -f infra/docker/compose.server-test.yml exec web php artisan migrate --force
docker compose -f infra/docker/compose.server-test.yml exec web php artisan storage:link
docker compose -f infra/docker/compose.server-test.yml exec web php artisan optimize:clear
```

### 7. Configurar Nginx Proxy Manager

Crear Proxy Host para:

```text
crm1.qpsecure.cloud -> crm1_web:8088
```

Si se despliegan frontend app, admin app y API por separado:

```text
crm1app.qpsecure.cloud -> <frontend-app-container>:<frontend-port>
admincrm1.qpsecure.cloud -> <admin-app-container>:<admin-port>
crmapi.qpsecure.cloud -> <api-container>:<api-port>
```

Solicitar certificado SSL Let's Encrypt.

### 8. Validar CRM

```text
https://crm1.qpsecure.cloud/crm
https://crm1.qpsecure.cloud/crm/whatsapp
```

Validar login, menu WhatsApp, conversaciones y eventos.

### 9. Validar webhook

GET:

```bash
curl "https://crm1.qpsecure.cloud/webhooks/meta/whatsapp?hub.mode=subscribe&hub.challenge=ok123&hub.verify_token=<token>"
```

Debe devolver:

```text
ok123
```

POST de prueba sin firma solo en entorno local. En servidor la prueba real debe hacerse desde Meta para incluir firma valida.

### 10. Configurar Meta

En Meta Developers:

```text
WhatsApp > Configuration > Webhooks
Callback URL: https://crm1.qpsecure.cloud/webhooks/meta/whatsapp
Verify token: <token>
Subscribe: messages
```

## Checklist de Seguridad

```text
[ ] DNS crm1.qpsecure.cloud apunta al servidor correcto
[ ] DNS crm1app.qpsecure.cloud apunta al servidor correcto
[ ] DNS admincrm1.qpsecure.cloud apunta al servidor correcto
[ ] DNS crmapi.qpsecure.cloud apunta al servidor correcto
[ ] Nginx Proxy Manager fuerza HTTPS
[ ] APP_DEBUG=false
[ ] APP_URL=https://crm1.qpsecure.cloud
[ ] DB y Redis no exponen puertos publicos
[ ] Solo crm1_web esta en la red de Nginx Proxy Manager
[ ] META_WHATSAPP_APP_SECRET configurado
[ ] META_WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=true
[ ] META_WHATSAPP_WEBHOOK_ALLOW_FALLBACK_TENANT=false
[ ] Tokens de Meta cifrados y nunca visibles en frontend/logs
[ ] Backups MySQL configurados
[ ] Logs revisables por contenedor
[ ] CORS limitado a origenes concretos
[ ] `crmapi.qpsecure.cloud` solo permite `crm1.qpsecure.cloud`, `crm1app.qpsecure.cloud` y `admincrm1.qpsecure.cloud` si corresponde
```

## Checklist Funcional

```text
[ ] https://crm1.qpsecure.cloud responde
[ ] https://crm1app.qpsecure.cloud responde si existe frontend app
[ ] https://admincrm1.qpsecure.cloud responde si existe frontend admin
[ ] https://crmapi.qpsecure.cloud responde si existe API separada
[ ] /crm redirige correctamente a login/dashboard
[ ] Login admin funciona
[ ] /crm/whatsapp carga dashboard
[ ] /crm/whatsapp/settings muestra callback URL HTTPS
[ ] /crm/whatsapp/conversations lista conversaciones
[ ] /crm/whatsapp/events lista eventos
[ ] GET webhook devuelve hub.challenge
[ ] Meta puede verificar callback
[ ] Meta entrega webhook messages
[ ] Se crea conversacion por phone_number_id
[ ] Se guarda mensaje inbound
[ ] Se actualiza estado delivered/read/failed
[ ] Errores Meta aparecen en eventos y detalle de conversacion
```

## Riesgos y Mitigaciones

### CORS abierto

Riesgo:

```text
allowed_origins=*
```

Mitigacion:

```text
Usar solo dominios conocidos de qpsecure.cloud:
crm1.qpsecure.cloud
crm1app.qpsecure.cloud
admincrm1.qpsecure.cloud
crmapi.qpsecure.cloud
```

### Servicios internos en red del proxy

Riesgo:

```text
mysql/redis visibles para otros contenedores innecesarios.
```

Mitigacion:

```text
Solo crm1_web en npm_proxy. DB, Redis, queue y scheduler en crm1_internal.
```

### Firma Meta desactivada

Riesgo:

```text
Cualquier cliente puede publicar payloads falsos al webhook.
```

Mitigacion:

```text
META_WHATSAPP_WEBHOOK_REQUIRE_SIGNATURE=true en servidor.
```

### Fallback tenant activo

Riesgo:

```text
Eventos con phone_number_id desconocido pueden entrar al primer tenant activo.
```

Mitigacion:

```text
META_WHATSAPP_WEBHOOK_ALLOW_FALLBACK_TENANT=false en servidor.
```

### `artisan serve` en produccion

Riesgo:

```text
No es el servidor HTTP mas robusto para trafico real.
```

Mitigacion:

```text
Usarlo solo en server-test. Migrar a Nginx + PHP-FPM u Octane antes de produccion.
```

## Orden Recomendado de Implementacion

```text
1. Crear infra/docker/compose.server-test.yml
2. Crear Dockerfile.server
3. Crear .env.server-test completo para crm1.qpsecure.cloud y subdominios relacionados
4. Levantar stack sin proxy y probar internamente
5. Conectar crm1_web a red de Nginx Proxy Manager
6. Configurar Proxy Host crm1.qpsecure.cloud
7. Configurar crm1app.qpsecure.cloud, admincrm1.qpsecure.cloud y crmapi.qpsecure.cloud si se desplegaran en esta fase
8. Validar HTTPS y login
9. Validar modulo WhatsApp
10. Validar CORS real entre subdominios si hay frontend/API separados
11. Configurar Meta Webhook
12. Probar entrega real messages
13. Revisar logs y hardening
14. Promover ajustes finales a main
```

## Estado Deseado al Final de Server Test

```text
crm1.qpsecure.cloud operativo por HTTPS
crm1app.qpsecure.cloud operativo si existe frontend app
admincrm1.qpsecure.cloud operativo si existe frontend admin
crmapi.qpsecure.cloud operativo si existe API separada
CRM accesible en /crm
Modulo WhatsApp visible y funcional
Webhook Meta verificado desde Meta Developers
Eventos messages guardados
Conversaciones y mensajes creados por tenant
Estados y errores visibles en frontend
DB/Redis privados
Nginx Proxy Manager como unico punto publico 80/443
```
