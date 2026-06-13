# Checklist QA multitenant

## Base

- [ ] `composer validate` pasa.
- [ ] `composer install` pasa en la app SaaS host.
- [ ] `npm install` pasa.
- [ ] `php artisan test` pasa en la app SaaS host.
- [ ] `vendor/bin/pint --test` pasa.
- [ ] `npm run build` pasa.
- [ ] `php artisan route:list` no muestra rutas rotas.

## Tenant

- [ ] Tenant A no ve datos de Tenant B.
- [ ] Tenant B no ve datos de Tenant A.
- [ ] Usuario sin tenant no entra.
- [ ] Tenant suspendido no entra.
- [ ] Tenant activo entra.

## CRM

- [ ] Leads aislados.
- [ ] Deals aislados.
- [ ] Quotes aisladas.
- [ ] Invoices aisladas.
- [ ] Orders aisladas.
- [ ] People aisladas.
- [ ] Organizations aisladas.
- [ ] Products aislados.
- [ ] Tasks aisladas.
- [ ] Notes aisladas.
- [ ] Files aislados.
- [ ] Campaigns aisladas.
- [ ] Chat aislado.

## API

- [ ] Token A no accede a datos B.
- [ ] Token B no accede a datos A.
- [ ] API sin token falla.
- [ ] API con token invalido falla.
- [ ] API con tenant suspendido falla.

## Portal

- [ ] Quote publica no cruza tenant.
- [ ] Invoice publica no cruza tenant.
- [ ] Purchase order publica no cruza tenant.
- [ ] Feature publica no cruza tenant.
- [ ] Tracking email/sms no cruza tenant.

## WhatsApp

- [ ] Token cifrado.
- [ ] Webhook verificado.
- [ ] Payload guardado.
- [ ] Tenant resuelto por `phone_number_id`.
- [ ] Mensaje entrante aislado.
- [ ] Mensaje saliente aislado.
- [ ] Estados actualizados.
- [ ] Errores logueados.
