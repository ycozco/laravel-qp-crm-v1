# Local Test

`local-test` representa el entorno local reproducible para pruebas funcionales.

## URL local actual

```text
CRM:
http://127.0.0.1:8088/crm

Login:
http://127.0.0.1:8088/crm/login
```

## Credenciales demo

```text
Owner:
admin@crm-laravel.local
Secret123!

Agente:
sales@crm-laravel.local
Secret123!
```

## Servicios Docker locales

```text
crm_laravel_host_web  127.0.0.1:8088 -> 8088
laravel_crm_mysql     localhost:33068 -> 3306
laravel_crm_redis     localhost:63798 -> 6379
```

## Archivos relacionados

```text
D:\proyectos\crm_laravel_host\docker-compose.yml
D:\proyectos\crm_laravel_host\.env
D:\proyectos\crm_laravel\infra\docker\compose.package.yml
```

## Uso previsto

```text
Probar CRM base
Probar pantallas nuevas
Probar datos demo
Probar webhook con tunnel local si se requiere
```

Para pruebas con Meta desde local se necesitara un tunnel HTTPS:

```text
ngrok
cloudflared tunnel
localtunnel
```

La URL local directa no sirve para Meta porque Meta exige HTTPS publico.

