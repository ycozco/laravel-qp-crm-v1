# Registro de evidencias

## 2026-06-12

- Se clono `venturedrake/laravel-crm` en `D:\proyectos\crm_laravel`.
- Se genero `graph.json` con Graphify para el proyecto `crm-laravel`.
- Graphify reporto 1513 archivos, 373 directorios y 1887 nodos.
- Se construyo un workspace Docker de paquete con PHP, Node, MySQL y Redis.
- `composer validate --no-check-publish` inicialmente aviso que
  `dcblogdev/laravel-xero` estaba fijado a la version exacta `1.1.3`.
- Se cambio `dcblogdev/laravel-xero` a `^1.1.3` y Composer valida sin warnings.
- `npm run build` paso, con avisos de vulnerabilidades npm y chunk grande.

## Pendiente Sprint 0

- Guardar inventarios en `docs/baseline`.
- Documentar salida completa de checks.
- Confirmar si se ejecutaran tests en este paquete o en app SaaS host.

## 2026-06-13

- Se agrego `config/meta-whatsapp.php` como placeholder publicable para la app
  host.
- La configuracion deja comentado que `META_WHATSAPP_APP_ID`,
  `META_WHATSAPP_APP_SECRET` y `META_WHATSAPP_WEBHOOK_VERIFY_TOKEN` son globales
  de la app Meta.
- Se documento que los access tokens por tenant no van en `.env`, sino cifrados
  en `saas_tenant_integrations.credentials_json`.
