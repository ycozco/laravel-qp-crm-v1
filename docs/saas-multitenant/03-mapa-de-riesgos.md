# Mapa de riesgos

## Riesgos criticos

| Riesgo | Impacto | Mitigacion |
| --- | --- | --- |
| Fuga de datos entre tenants | Critico | Tests de aislamiento antes de cambios |
| `team_id` incompleto | Alto | Diagnostico por modelos, API, portal y jobs |
| Jobs sin contexto tenant | Alto | Tests de jobs y payload explicito |
| Portal publico cruzando tenants | Alto | Validar pertenencia de documento y signed URLs |
| API V2 resolviendo mal el team | Alto | Tests con tokens de Team A y Team B |
| WhatsApp sin resolucion por tenant | Critico | Resolver por `phone_number_id` o `waba_id`, nunca sesion |
| Storage compartido | Alto | Rutas `storage/app/tenants/{tenant_id}/...` |
| Superadmin sin auditoria | Medio | `saas_audit_logs` para acciones criticas |

## Riesgos de despliegue

| Riesgo | Estado | Mitigacion |
| --- | --- | --- |
| Confundir paquete con app Laravel completa | Activo | Documentado en `docs/deployment` |
| Docker en raiz desordenado | Corregido | Movido a `infra/docker` |
| Dependencias dev bloqueadas por Composer | Activo | Imagen usa `--no-dev --no-security-blocking` |
| Vulnerabilidades npm existentes | Pendiente | Revisar `npm audit` despues del baseline |
