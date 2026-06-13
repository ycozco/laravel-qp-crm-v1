# Prompts Codex

## Prompt 1: diagnostico `team_id`

```txt
Analiza unicamente el soporte actual de scoping por equipo en Laravel CRM.

Archivos:
- src/Scopes/BelongsToTeamsScope.php
- src/Traits/BelongsToTeams.php
- src/Traits/HasCrmTeams.php
- src/Models/Team.php
- src/Models/Model.php
- src/Http/Middleware/SetApiTeamContext.php
- src/Http/Middleware/RouteSubdomain.php
- src/Http/Middleware/TeamsPermission.php
- database/migrations/add_team_id_to_laravel_crm_tables.php.stub
- tests/Feature/BelongsToTeamsScopeTest.php
- tests/Feature/Api/V2/TeamScopingTest.php
- tests/Unit/Api/SetApiTeamContextTest.php

Objetivo:
Determinar si team_id puede funcionar como tenant SaaS para MVP.

Entrega:
1. Como se resuelve el team actual.
2. Que modelos/tablas estan cubiertos.
3. Que modelos/tablas no estan cubiertos.
4. Riesgos de fuga.
5. Recomendacion: Tenant = Team, Tenant contiene Teams o crear tenant_id.
6. Tests faltantes.

No modifiques codigo.
```

## Prompt 2: matriz de aislamiento

```txt
Genera una matriz de aislamiento multitenant para estos modelos:

Lead, Deal, Quote, Invoice, Order, Person, Organization, Customer, Product,
Task, Note, File, EmailCampaign, SmsCampaign, ChatConversation, Monitor,
Feature.

Columnas:
- Modelo
- Tabla probable
- Tiene team_id
- Usa BelongsToTeams
- Tiene policy
- Tiene API
- Tiene Livewire
- Tiene portal publico
- Tiene jobs relacionados
- Riesgo
- Test requerido

No modifiques codigo.
```

## Prompt 3: tests de fuga

```txt
Crea tests de aislamiento para verificar que un usuario de Team A no puede ver,
editar ni eliminar datos de Team B.

Crear:
- tests/Feature/Tenancy/TenantDataIsolationTest.php
- tests/Feature/Tenancy/TenantApiIsolationTest.php
- tests/Feature/Tenancy/TenantPortalIsolationTest.php

Modelos iniciales:
Lead, Deal, Quote, Invoice, Order, Person, Organization, Product.

No cambies arquitectura todavia. Solo tests.
```

## Prompt 4: SaasTenant minimo

```txt
Implementa el tenant core minimo usando la decision provisional Tenant = Team.

Crear:
- app/Models/SaasTenant.php
- app/Models/SaasTenantDomain.php
- app/Models/SaasPlan.php
- app/Services/TenantContext.php
- app/Services/TenantProvisioningService.php
- app/Http/Middleware/ResolveSaasTenant.php
- app/Http/Middleware/EnsureTenantIsActive.php
- migraciones correspondientes

Regla:
SaasTenant debe vincularse con el Team CRM existente mediante crm_team_id.

Crear tests:
- tests/Feature/Saas/TenantResolutionTest.php
- tests/Feature/Saas/TenantProvisioningTest.php
```

## Prompt 5: WhatsApp manual

```txt
Implementa integracion WhatsApp Meta manual por tenant.

Crear:
- app/Models/WhatsappMessage.php
- app/Models/WhatsappWebhookLog.php
- app/Services/Whatsapp/WhatsappClient.php
- app/Services/Whatsapp/WhatsappWebhookProcessor.php
- app/Jobs/ProcessWhatsappWebhook.php
- app/Jobs/SendWhatsappMessage.php
- rutas GET/POST /webhooks/meta/whatsapp

Reglas:
- No resolver tenant por sesion.
- Resolver tenant por phone_number_id o waba_id.
- Guardar access_token cifrado.
- Guardar payload bruto.
- Responder 200 rapido.
- Procesar en cola.
- Idempotencia por wa_message_id.
```
