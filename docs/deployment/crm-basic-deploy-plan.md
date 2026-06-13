# CRM basic deploy plan

This plan is for testing `crm_laravel` as a package and preparing the future
SaaS host application.

## Phase 1: package workspace

Use the local package workspace to validate dependencies, assets, inventory and
tenancy assumptions.

```bash
docker compose -f infra/docker/compose.package.yml up -d --build
docker compose -f infra/docker/compose.package.yml exec -T app composer validate --no-check-publish
docker compose -f infra/docker/compose.package.yml run --rm node npm run build
```

The package workspace does not expose `/crm` in a browser because this repository
is a Laravel package, not a full Laravel application.

## Phase 2: host Laravel app

Create a Laravel app that will host the package.

The host app owns:

- `.env`
- database connection
- auth/users
- queues
- scheduler
- domains/subdomains
- SaaS tenant tables
- superadmin panel

The package owns:

- CRM routes and views
- CRM models, policies and observers
- CRM migrations and seeders
- CRM team scoping

## Phase 3: required host env

Start with this environment shape in the host app:

```env
APP_NAME="CRM Multitenant"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=crm_multitenant
DB_USERNAME=crm_multitenant
DB_PASSWORD=crm_multitenant

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

LARAVEL_CRM_OWNER=owner@example.com
LARAVEL_CRM_TEAMS=true
LARAVEL_CRM_ROUTE_PREFIX=crm
LARAVEL_CRM_ROUTE_SUBDOMAIN=
LARAVEL_CRM_DB_TABLE_PREFIX=crm_
LARAVEL_CRM_USER_INTERFACE=true
```

## Phase 4: publish and install CRM

Inside the host app:

```bash
composer require venturedrake/laravel-crm
php artisan vendor:publish --tag=config
php artisan laravelcrm:install
php artisan migrate
php artisan db:seed --class="VentureDrake\\LaravelCrm\\Database\\Seeders\\LaravelCrmTablesSeeder"
```

If testing sample data:

```bash
php artisan laravelcrm:sample-data
```

## Phase 5: first checks

```bash
php artisan route:list | grep crm
php artisan test
npm run build
```

Manual checks:

- `/crm` loads.
- owner user can log in.
- leads CRUD works.
- teams mode is enabled.
- API V2 requires token.

## Phase 6: Meta WhatsApp configuration placeholder

Publish config and set only global Meta app values in `.env`:

```env
META_WHATSAPP_ENABLED=false
META_WHATSAPP_GRAPH_VERSION=v20.0
META_WHATSAPP_APP_ID=
META_WHATSAPP_APP_SECRET=
META_WHATSAPP_WEBHOOK_VERIFY_TOKEN=
META_WHATSAPP_WEBHOOK_PATH=/webhooks/meta/whatsapp
```

Do not put tenant WhatsApp access tokens in `.env`.

Future tenant credentials must be stored encrypted per tenant:

```txt
saas_tenant_integrations
  tenant_id
  provider = meta_whatsapp
  status
  credentials_json encrypted
  metadata_json
```

Expected encrypted `credentials_json` shape:

```json
{
  "waba_id": "tenant WABA ID",
  "phone_number_id": "tenant phone number ID",
  "business_id": "tenant business ID",
  "access_token": "tenant access token"
}
```

Webhook tenant resolution must use `phone_number_id` or `waba_id`, never the
session.

## Phase 7: blocking rule before WhatsApp

Do not implement WhatsApp message sending until:

- tenant core works
- `team_id` diagnosis is complete
- isolation tests pass
- portal routes are tenant-safe
- jobs carry tenant/team context
- superadmin can suspend tenants
