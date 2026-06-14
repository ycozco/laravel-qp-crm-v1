# Modelo de ramas y entornos

Este repositorio usara tres ramas largas:

```text
main
local-test
server-test
```

## main

`main` es la rama central del producto. Aqui vive todo lo que debe ser igual para local y servidor:

```text
src/
config/
database/
resources/
public/
tests/
docs/ compartidos
```

Regla:

```text
Toda funcionalidad nueva entra primero a main, no directamente a local-test o server-test.
```

Ejemplos de cambios que deben ir en `main`:

```text
Pantallas CRM
Vistas Livewire
Controladores
Servicios Meta WhatsApp
Migraciones
Modelos
Policies
Tests
Configuracion base sin secretos
Documentacion funcional
```

## local-test

`local-test` es la rama de pruebas locales reproducibles.

Debe contener solamente diferencias de entorno local:

```text
Docker local
Puertos locales
.env.local-test.example
Seeds de prueba
Notas de ejecucion local
```

Valores esperados:

```text
APP_URL=http://127.0.0.1:8088
CRM_URL=http://127.0.0.1:8088/crm
DB_HOST=laravel_crm_mysql
DB_PORT=3306
HOST_DB_PORT=33068
REDIS_HOST=laravel_crm_redis
HOST_REDIS_PORT=63798
```

## server-test

`server-test` es la rama de pruebas en servidor/staging.

Debe contener solamente diferencias de despliegue servidor:

```text
Docker para servidor
Nginx o Nginx Proxy Manager
.env.server-test.example
HTTPS
Queues
Scheduler
Logs
Plan de webhooks publicos
```

Valores esperados:

```text
APP_URL=https://crm.midominio.com
CRM_URL=https://crm.midominio.com/crm
META_WHATSAPP_WEBHOOK_PATH=/api/webhooks/meta/whatsapp
PUBLIC_WEBHOOK_URL=https://crm.midominio.com/api/webhooks/meta/whatsapp
```

## Regla de sincronizacion

El flujo normal es:

```text
feature/*
    -> main
        -> local-test
        -> server-test
```

Nunca se debe desarrollar una pantalla solo en `local-test` o solo en `server-test`.

Si una pantalla se crea en una rama de prueba por urgencia, debe extraerse a una rama `feature/*` y entrar a `main` antes de continuar.

## Archivos que no deben divergir

Estos archivos deben mantenerse equivalentes entre ramas, salvo cambios inevitables de version:

```text
src/**
config/meta-whatsapp.php
config/laravel-crm.php
database/migrations/**
database/seeders/**
resources/**
public/vendor/laravel-crm/**
tests/**
composer.json
composer.lock
package.json
package-lock.json
```

## Archivos que si pueden diferir

```text
infra/docker/compose.local-test.yml
infra/docker/compose.server-test.yml
docs/deployment/local-test.md
docs/deployment/server-test.md
docs/deployment/*.example
nginx/*.conf
```

## Proteccion conceptual de main

Antes de fusionar a `main`:

```text
[ ] La funcionalidad no depende de un puerto local fijo
[ ] No contiene secretos
[ ] No contiene URLs privadas de servidor
[ ] Tiene migraciones reversibles o documentadas
[ ] Tiene pruebas o checklist manual
[ ] El frontend funciona sin depender de una sola rama de entorno
```

