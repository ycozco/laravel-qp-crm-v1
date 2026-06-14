# Server Test - Implementacion ejecutable

## Validacion de `plan-server.md`

El plan era correcto en red, dominios y aislamiento, pero tenia una brecha
importante frente a este repositorio:

- este repo es un paquete Laravel (`type: library`), no una app Laravel lista
  para publicar en `/crm`;
- por tanto, el despliegue necesitaba generar un host app Laravel dentro del
  build para que `crm1.qpsecure.cloud` pudiera responder de verdad.

La implementacion agregada resuelve eso sin salir de este directorio.

## Archivos nuevos

```text
.env.server-test.example
infra/docker/Dockerfile.server
infra/docker/compose.server-test.yml
infra/docker/apache/crm.conf
infra/docker/scripts/bootstrap.sh
infra/docker/scripts/start-web.sh
infra/docker/scripts/run-worker.sh
infra/docker/scripts/run-scheduler.sh
infra/docker/scripts/patch-host-app.php
```

## Arquitectura resultante

```text
Internet
  -> Nginx Proxy Manager
  -> crm1_web:80
     -> host Laravel generado en build + paquete venturedrake/laravel-crm
     -> crm1_mysql:3306
     -> crm1_redis:6379
     -> crm1_queue
     -> crm1_scheduler
```

## Que hace el build

`infra/docker/Dockerfile.server`:

1. crea una app Laravel `^12.0`;
2. instala este paquete por `path repository`;
3. publica config, migraciones y assets del CRM;
4. parchea el host app para:
   - agregar `HasRoles`, `HasCrmAccess`, `HasCrmTeams` al `User`;
   - habilitar `TrustProxies`;
   - cerrar CORS por origenes concretos;
5. deja una imagen lista para `web`, `queue` y `scheduler`.
6. usa Redis para `queue`, `cache` y `session`, evitando migraciones runtime
   innecesarias para el server test.

## Servicios que deben salir ahora

- `crm1.qpsecure.cloud` debe apuntar a `crm1_web:80`.
- `crmapi.qpsecure.cloud` puede apuntar al mismo `crm1_web:80` si vas a
  exponer la API desde la misma app Laravel y usar `CORS_ALLOWED_ORIGINS`.
- `crm1app.qpsecure.cloud` y `admincrm1.qpsecure.cloud` deben esperar hasta que
  existan frontends separados. Hoy este repo no contiene esas apps.

## Pasos de despliegue

Desde la raiz de este repo:

```bash
cp .env.server-test.example .env.server-test
docker compose --env-file .env.server-test -f infra/docker/compose.server-test.yml build --no-cache
docker compose --env-file .env.server-test -f infra/docker/compose.server-test.yml up -d
```

Red externa detectada en este servidor:

```text
main_npm_network
```

## Nginx Proxy Manager

Host obligatorio:

```text
Domain Names: crm1.qpsecure.cloud
Scheme: http
Forward Hostname / IP: crm1_web
Forward Port: 80
Websockets Support: enabled
Block Common Exploits: enabled
```

Host opcional sobre el mismo backend:

```text
Domain Names: crmapi.qpsecure.cloud
Scheme: http
Forward Hostname / IP: crm1_web
Forward Port: 80
```

Advanced:

```nginx
client_max_body_size 20m;
proxy_read_timeout 120s;
proxy_connect_timeout 120s;
proxy_send_timeout 120s;
```

## Verificacion esperada

Interna:

```bash
docker compose --env-file .env.server-test -f infra/docker/compose.server-test.yml ps
docker compose --env-file .env.server-test -f infra/docker/compose.server-test.yml logs web --tail=100
docker exec crm1_web curl -I http://127.0.0.1/up
docker exec crm1_web curl -I http://127.0.0.1/crm
```

Publica:

```bash
curl -I https://crm1.qpsecure.cloud
curl -I https://crm1.qpsecure.cloud/crm
curl "https://crm1.qpsecure.cloud/webhooks/meta/whatsapp?hub.mode=subscribe&hub.challenge=ok123&hub.verify_token=<token>"
```

## Resultado esperado

- `crm1_web` es el unico servicio unido a `npm_proxy`;
- MySQL y Redis quedan solo en `crm1_internal`;
- el host app se inicializa solo la primera vez;
- `APP_KEY` se genera si no fue fijada en `.env.server-test`;
- el webhook Meta queda servido por HTTPS en
  `https://crm1.qpsecure.cloud/webhooks/meta/whatsapp`.
