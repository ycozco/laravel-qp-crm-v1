# Plan verificable por fases

## Sprint 0: baseline e inventario

Objetivo: dejar evidencia antes de tocar arquitectura.

Tareas:

- Confirmar instalacion del proyecto.
- Ejecutar build frontend.
- Validar Composer.
- Generar lista de migraciones.
- Generar lista de modelos.
- Generar lista de middlewares.
- Generar lista de policies.
- Generar lista de jobs.
- Generar lista de tests existentes.

Comandos para este paquete:

```bash
docker compose -f infra/docker/compose.package.yml exec -T app composer validate --no-check-publish
docker compose -f infra/docker/compose.package.yml run --rm node npm run build
rg --files src/Models > docs/baseline/models.txt
rg --files src/Http/Middleware > docs/baseline/middlewares.txt
rg --files src/Policies > docs/baseline/policies.txt
rg --files src/Jobs > docs/baseline/jobs.txt
rg --files tests > docs/baseline/tests.txt
rg --files database/migrations > docs/baseline/migrations.txt
```

Criterio de salida:

- Existe evidencia en `docs/baseline`.
- Fallos existentes quedan documentados.

## Sprint 1: diagnostico `team_id`

Objetivo: determinar si `team_id` puede representar al tenant SaaS.

Comandos:

```bash
rg "team_id" src database tests > docs/audit/team-id-usage.txt
rg "BelongsToTeams" src tests > docs/audit/belongs-to-teams.txt
rg "BelongsToTeamsScope" src tests > docs/audit/team-scope.txt
rg "SetApiTeamContext" src tests > docs/audit/api-team-context.txt
rg "RouteSubdomain" src tests > docs/audit/route-subdomain.txt
rg "withoutGlobalScopes" src tests > docs/audit/without-global-scopes.txt
rg "DB::table" src > docs/audit/db-table-usage.txt
rg "::all\\(" src > docs/audit/model-all-usage.txt
```

Entregable:

```txt
docs/audit/team-scope-diagnosis.md
```

Criterio de salida: decision A, B o C documentada.

## Sprint 2: tests de aislamiento

Objetivo: crear pruebas que detecten fugas entre Team A y Team B.

Tests:

```txt
tests/Feature/Tenancy/TenantDataIsolationTest.php
tests/Feature/Tenancy/TenantApiIsolationTest.php
tests/Feature/Tenancy/TenantPortalIsolationTest.php
tests/Feature/Tenancy/TenantJobContextTest.php
```

Criterio de salida: cada fuga detectada tiene archivo, modelo y ruta afectada.

## Sprint 3: app Laravel SaaS base

Objetivo: crear una app contenedora separada del paquete.

Responsabilidades SaaS:

- tenants
- planes
- suscripciones
- superadmin
- dominios/subdominios
- integraciones externas
- logs y auditoria

Criterio de salida: existe app SaaS base con login, superadmin, CRUD minimo de
tenants y tenant demo.

## Sprint 4: CRM instalado y estable

Objetivo: instalar el paquete CRM dentro de la app SaaS y validar `/crm`.

Criterio de salida: CRM funcional antes de reforzar tenancy.

## Sprint 5: tenant core usando Team

Objetivo: vincular `SaasTenant.crm_team_id` con el Team CRM.

Criterio de salida: tenant activo accede, tenant suspendido no accede y usuario
sin relacion no entra.

## Sprint 6: aislamiento de datos

Orden:

1. Modelos principales.
2. API V2.
3. Livewire.
4. Portal publico.
5. Jobs.
6. Observers.
7. Storage.
8. Integraciones.

## Sprint 7: panel superadmin

Objetivo: operar tenants, planes, usuarios, logs e integraciones.

## Sprint 8: limites por plan

Objetivo: limitar usuarios, leads, contactos, mensajes, storage y features.

## Sprint 9: WhatsApp manual

Objetivo: conectar WhatsApp Cloud API por tenant con credenciales cargadas por
superadmin.

## Sprint 10: WhatsApp Embedded Signup

Objetivo: permitir que cada tenant conecte WhatsApp Business desde su panel.

## Sprint 11: observabilidad

Objetivo: auditar acciones criticas, webhooks, jobs y accesos denegados.
