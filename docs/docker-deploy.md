# Docker deploy note

Docker files were moved out of the repository root.

Use:

```bash
docker compose -f infra/docker/compose.package.yml up -d --build
```

See [docker-package-workspace.md](deployment/docker-package-workspace.md) for the
full package workspace guide.
