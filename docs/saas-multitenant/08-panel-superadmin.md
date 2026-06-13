# Panel superadmin

## Objetivo

Administrar el SaaS completo sin entrar directamente a la base de datos.

## Menu minimo

```txt
Superadmin
  Dashboard
  Tenants
  Planes
  Suscripciones
  Usuarios
  Uso
  Integraciones
  Webhooks
  Jobs fallidos
  Auditoria
  Soporte
```

## Funciones minimas

- Crear tenant.
- Editar tenant.
- Suspender tenant.
- Reactivar tenant.
- Cambiar plan.
- Ver usuarios del tenant.
- Ver consumo del tenant.
- Ver integraciones del tenant.
- Ver logs del tenant.
- Entrar como soporte con auditoria.

## Metricas minimas

- tenants activos
- tenants suspendidos
- usuarios activos
- leads por tenant
- deals por tenant
- invoices por tenant
- mensajes WhatsApp por tenant
- jobs fallidos
- webhooks fallidos

## Criterio de salida

- Superadmin puede operar tenants.
- Acciones criticas quedan auditadas.
- Suspender tenant bloquea acceso.
- Cambiar plan actualiza limites.
