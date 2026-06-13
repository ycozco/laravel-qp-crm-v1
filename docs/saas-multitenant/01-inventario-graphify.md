# Inventario Graphify

## Fuente

Archivo generado:

```txt
graph.json
```

Salida original de Graphify:

```txt
obsidian-graphify-mcp/out/graphify/crm-laravel-2026-06-12_233550-graph.json
```

## Conteos

- Directorios: 373
- Archivos: 1513
- Nodos: 1887
- Edges detectadas: 43

## Carpetas principales

- `src`: codigo del paquete Laravel CRM.
- `resources/views`: vistas Blade y Livewire.
- `resources/js`: frontend Vite actual.
- `resources/v1`: vistas y assets heredados.
- `database/migrations`: migraciones del paquete.
- `database/seeders`: seeders iniciales.
- `tests`: cobertura funcional y unitaria existente.
- `config`: configuracion publicable del paquete.
- `public/vendor/laravel-crm`: assets compilados.

## Archivos prioritarios para tenancy

```txt
src/Scopes/BelongsToTeamsScope.php
src/Traits/BelongsToTeams.php
src/Traits/HasCrmTeams.php
src/Models/Team.php
src/Models/Model.php
src/Http/Middleware/SetApiTeamContext.php
src/Http/Middleware/RouteSubdomain.php
src/Http/Middleware/TeamsPermission.php
database/migrations/add_team_id_to_laravel_crm_tables.php.stub
tests/Feature/BelongsToTeamsScopeTest.php
tests/Feature/Api/V2/TeamScopingTest.php
tests/Unit/Api/SetApiTeamContextTest.php
```

## Nota

Graphify detecto poco simbolo PHP porque el escaner actual es mas util para
estructura y dependencias JS que para introspeccion profunda de clases PHP. Para
diagnostico tenancy se deben usar `rg`, tests y lectura directa de modelos,
traits, scopes, middlewares y rutas.
