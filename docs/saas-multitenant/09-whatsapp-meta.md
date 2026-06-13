# WhatsApp Meta

## Regla de entrada

No empezar WhatsApp hasta cerrar tenant core, aislamiento API, portal y jobs.

## MVP manual

Primero se implementa integracion manual por tenant. Embedded Signup queda para
despues.

## Datos requeridos por tenant

- WABA ID
- Phone Number ID
- Business ID
- Access Token cifrado
- Webhook Verify Token

## Configuracion base agregada

El paquete publica un archivo de configuracion placeholder:

```txt
config/meta-whatsapp.php
```

Valores globales para la app host:

```env
META_WHATSAPP_ENABLED=false
META_WHATSAPP_GRAPH_VERSION=v20.0
META_WHATSAPP_APP_ID=
META_WHATSAPP_APP_SECRET=
META_WHATSAPP_WEBHOOK_VERIFY_TOKEN=
META_WHATSAPP_WEBHOOK_PATH=/webhooks/meta/whatsapp
```

No guardar tokens por tenant en `.env`. Los access tokens de cada empresa deben
ir cifrados en `saas_tenant_integrations.credentials_json`.

## Tablas propuestas

```txt
saas_tenant_integrations
whatsapp_messages
whatsapp_webhook_logs
```

## Endpoints

```txt
GET /webhooks/meta/whatsapp
POST /webhooks/meta/whatsapp
```

## Flujo POST

1. Recibir payload JSON.
2. Guardar payload bruto.
3. Extraer `phone_number_id`.
4. Buscar integracion por `phone_number_id`.
5. Resolver tenant.
6. Encolar job.
7. Responder 200 rapido.
8. Procesar mensaje/status en segundo plano.

## Reglas criticas

- No resolver tenant por sesion.
- Resolver tenant por `phone_number_id` o `waba_id`.
- Cifrar `access_token`.
- Guardar payload original.
- Usar cola.
- Implementar idempotencia por `wa_message_id`.
- Registrar errores.
