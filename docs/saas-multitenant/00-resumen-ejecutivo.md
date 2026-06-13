# Resumen ejecutivo

## Objetivo

Convertir `venturedrake/laravel-crm` en una plataforma SaaS multitenant
verificable, evitando cambios masivos sin pruebas y usando Graphify como fuente
de inventario tecnico.

## Camino aprobado

1. Baseline e inventario.
2. Diagnostico de `team_id`.
3. Tests de aislamiento.
4. App Laravel SaaS base.
5. CRM instalado y estable dentro de la app SaaS.
6. Tenant core usando Team.
7. Correccion de fugas reales.
8. Panel superadmin.
9. Limites por plan.
10. WhatsApp Meta manual.
11. Embedded Signup.
12. Observabilidad y auditoria.

## Decision provisional

Para el MVP se evaluara:

```txt
SaaS Tenant = CRM Team
```

Esta decision no queda aprobada hasta completar el diagnostico de `team_id` y
las pruebas de aislamiento.

## Regla de bloqueo

No avanzar a WhatsApp/Meta hasta cumplir:

- Tenant core funcionando.
- Tests de aislamiento pasando.
- API V2 aislada.
- Portal publico aislado.
- Jobs con contexto tenant/team.
- Panel superadmin capaz de suspender tenants.
