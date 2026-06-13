# Docker package workspace

This repository is the `venturedrake/laravel-crm` package, not a complete
Laravel application. The package must be installed into a Laravel app to expose
`/crm` in a browser.

## Folder layout

```txt
infra/docker/
  compose.package.yml
  php/Dockerfile
docs/deployment/
  docker-package-workspace.md
```

## Start

```bash
docker compose -f infra/docker/compose.package.yml up -d --build
```

## Status

```bash
docker compose -f infra/docker/compose.package.yml ps
```

## Useful checks

```bash
docker compose -f infra/docker/compose.package.yml exec -T app composer validate --no-check-publish
docker compose -f infra/docker/compose.package.yml run --rm node npm run build
```

## Services

- `app`: PHP 8.3 CLI workspace for Composer/package checks.
- `node`: one-shot asset builder for Vite.
- `mysql`: integration database on `127.0.0.1:33068`.
- `redis`: local cache/queue dependency on `127.0.0.1:63798`.

## Known constraints

- The app image uses `composer install --no-dev --no-security-blocking` because
  this upstream package has no lock file and the current dev constraints trigger
  Composer security blocking while resolving old Laravel/Pest ranges.
- `node` exits after the asset build. That is expected.
- A browser CRM deployment requires a separate Laravel SaaS host app.
