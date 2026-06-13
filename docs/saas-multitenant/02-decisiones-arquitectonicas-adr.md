# Decisiones arquitectonicas ADR

## ADR-001: no hacer conversion multitenant masiva

Estado: aceptada.

Decision: no pedir ni ejecutar una conversion global del CRM a multitenant sin
inventario, pruebas y diagnostico previo.

Motivo: el paquete ya contiene `team_id`, scopes, middlewares y tests asociados.
Agregar `tenant_id` sin confirmar el estado actual puede duplicar conceptos y
crear fugas nuevas.

## ADR-002: evaluar `Tenant = Team` para MVP

Estado: provisional.

Decision provisional:

```txt
SaaS Tenant = CRM Team
```

Condicion de aprobacion: Sprint 1 debe demostrar que `team_id` aisla modelos,
API, portal y jobs, o documentar exactamente que falta.

## ADR-003: separar app SaaS y paquete CRM

Estado: aceptada.

Decision: la app SaaS contenedora administrara tenants, planes, dominios,
suscripciones, superadmin y auditoria. El paquete CRM debe conservar
responsabilidades CRM: leads, deals, quotes, invoices, orders, products,
activities, campaigns, chat y portal.

## ADR-004: WhatsApp despues del aislamiento

Estado: aceptada.

Decision: WhatsApp/Meta no se implementa hasta tener tenant core y pruebas de
aislamiento.

Motivo: WhatsApp introduce mensajes, contactos, webhooks y tokens por empresa.
Una fuga cross-tenant seria critica.
