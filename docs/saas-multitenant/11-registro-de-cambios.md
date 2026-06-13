# Registro de cambios

## 2026-06-12

- Se agrego documentacion SaaS multitenant en `docs/saas-multitenant`.
- Se movio el despliegue Docker a `infra/docker`.
- Se dejo `docs/deployment/docker-package-workspace.md` como guia del workspace.
- Se mantuvo `docs/docker-deploy.md` como nota corta de redireccion.
- Se elimino Dockerfile y compose de la raiz para ordenar infraestructura.
- Se corrigio la restriccion exacta de `dcblogdev/laravel-xero` de `1.1.3` a
  `^1.1.3` para eliminar el warning de Composer.

## 2026-06-13

- Se agrego `config/meta-whatsapp.php`.
- Se registro `meta-whatsapp.php` en el service provider para merge y publish.
- Se agrego `docs/deployment/crm-basic-deploy-plan.md`.
- Se agrego `docs/deployment/host-app-env.example`.
