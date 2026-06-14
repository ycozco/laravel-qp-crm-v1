# Datos de prueba - `server-test`

Este archivo separa lo que esta garantizado por el bootstrap Docker de lo que
solo existe en `local-test` o depende de seeders manuales.

## URLs operativas en `server-test`

```text
CRM interno:
https://admincrm1.qpsecure.cloud/crm

Login:
https://admincrm1.qpsecure.cloud/crm/login

API:
https://crmapi.qpsecure.cloud/crm/api/v2

Webhook Meta:
https://crmapi.qpsecure.cloud/webhooks/meta/whatsapp
```

## Credenciales garantizadas por bootstrap

El stack `server-test` solo garantiza el owner creado con estas variables del
archivo `.env.server-test`:

```text
CRM_OWNER_NAME
CRM_OWNER_EMAIL
CRM_OWNER_PASSWORD
```

Formato esperado:

```text
Rol: Owner
Nombre: valor de CRM_OWNER_NAME
Email: valor de CRM_OWNER_EMAIL
Password: valor de CRM_OWNER_PASSWORD
Acceso: admincrm1.qpsecure.cloud/crm/login
Notas: usuario creado automaticamente por php artisan laravelcrm:install
```

## Credenciales demo conocidas de `local-test`

Estas credenciales existen en el entorno local reproducible y en los seeders
demo asociados, pero no deben asumirse como presentes en `server-test`:

```text
Owner demo local:
admin@crm-laravel.local
Secret123!

Agente demo local:
sales@crm-laravel.local
Secret123!
```

Uso previsto:

- validar pantallas WhatsApp en local
- validar tenant demo
- revisar conversaciones, mensajes y eventos demo

## Estado actual de multiples niveles de usuario

Hoy `server-test` no garantiza automaticamente estos perfiles:

```text
Admin
Manager
Agent
API tester
```

Para tenerlos en servidor hace falta una tarea separada:

- crear seeder de usuarios
- asignar roles CRM
- asociar usuarios al tenant demo
- documentar credenciales definitivas aqui

## Tenant demo relacionado

El seeder `WhatsappDemoSeeder` trabaja sobre:

```text
Tenant slug: demo-crm-laravel
Tenant name: Demo CRM Laravel
```

Y usa una cuenta WhatsApp demo:

```text
display_name: WhatsApp Demo CRM
phone_number_id: demo-phone-number-id-001
phone_number: +51 999 000 111
status: connected
```

Esto sirve para pruebas de UI y webhook demo, no para produccion.

## Prueba API minima

Emision de token:

```text
POST https://crmapi.qpsecure.cloud/crm/api/v2/auth/token
```

Payload:

```json
{
  "email": "valor de CRM_OWNER_EMAIL",
  "password": "valor de CRM_OWNER_PASSWORD",
  "device_name": "server-test"
}
```

Luego:

```text
GET https://crmapi.qpsecure.cloud/crm/api/v2/auth/me
Authorization: Bearer <token>
```

## Notas operativas

- Si el servidor solo tiene el owner creado por bootstrap, las pruebas deben
  hacerse con ese usuario.
- Si se cargan seeders manuales, actualizar este archivo y distinguir
  claramente entre credenciales permanentes y demo.
- No documentar secretos reales de Meta en este archivo.
