# CRM SaaS Multitenant

Este directorio contiene el plan operativo para convertir Laravel CRM en una
plataforma SaaS multitenant verificable.

## Orden de lectura

1. [00 - Resumen Ejecutivo](00-resumen-ejecutivo.md)
2. [01 - Inventario Graphify](01-inventario-graphify.md)
3. [02 - Decisiones Arquitectonicas ADR](02-decisiones-arquitectonicas-adr.md)
4. [03 - Mapa de Riesgos](03-mapa-de-riesgos.md)
5. [04 - Plan Verificable por Fases](04-plan-verificable-por-fases.md)
6. [05 - Checklist QA Multitenant](05-checklist-qa-multitenant.md)
7. [06 - Prompts Codex](06-prompts-codex.md)
8. [07 - Matriz de Aislamiento](07-matriz-de-aislamiento.md)
9. [08 - Panel Superadmin](08-panel-superadmin.md)
10. [09 - WhatsApp Meta](09-whatsapp-meta.md)
11. [10 - Registro de Evidencias](10-registro-de-evidencias.md)
12. [11 - Registro de Cambios](11-registro-de-cambios.md)

## Regla principal

No implementar multitenancy masivo sin evidencia previa. Primero se verifica si
`team_id` sirve como tenant SaaS para el MVP.
