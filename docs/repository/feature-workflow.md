# Flujo de features

## Objetivo

Evitar que el frontend, la logica de WhatsApp o las migraciones se dupliquen entre ramas de entorno.

## Ramas de trabajo

Usar ramas cortas para cada cambio:

```text
feature/whatsapp-webhook
feature/whatsapp-inbox
feature/tenant-whatsapp-accounts
feature/meta-embedded-signup
fix/server-test-nginx
fix/local-test-seed
```

## Flujo recomendado

```text
git checkout main
git checkout -b feature/whatsapp-webhook

# implementar codigo comun
# agregar tests/documentacion

git checkout main
git merge feature/whatsapp-webhook

git checkout local-test
git merge main

git checkout server-test
git merge main
```

## Cuando tocar local-test

Solo para:

```text
Puertos locales
Datos demo locales
Docker local
Tunnels para webhook local
Documentacion de ejecucion local
```

Ejemplo:

```text
feature/local-test-ngrok-docs
```

## Cuando tocar server-test

Solo para:

```text
Dominio publico
HTTPS
Proxy
Variables de staging
Workers
Scheduler
Logs
Webhook publico de Meta
```

Ejemplo:

```text
feature/server-test-meta-webhook-public-url
```

## Frontend

Las pantallas CRM deben vivir en `main`.

Ejemplo de pantallas compartidas:

```text
/crm/whatsapp
/crm/whatsapp/settings
/crm/whatsapp/conversations
/crm/whatsapp/conversations/{conversation}
```

Despues de fusionar a `main`, se propagan a:

```text
local-test
server-test
```

## Checklist de feature

```text
[ ] Rama creada desde main
[ ] Sin secretos en archivos versionados
[ ] Migraciones revisadas
[ ] UI accesible desde /crm
[ ] Funciona en local-test al mezclar main
[ ] No rompe server-test al mezclar main
[ ] Documentacion actualizada
```

