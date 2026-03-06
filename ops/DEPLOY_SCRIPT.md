# Deploy Script

Script único de despliegue:

```bash
php deploy.php
```

## Qué ejecuta
1. Lint de todos los `.php`.
2. Tests (`php tests/run.php`).
3. Migraciones pendientes.
4. Health interno (conexión DB + `SELECT 1`).

## Flags opcionales
- `--skip-lint`
- `--skip-tests`
- `--skip-migrate`
- `--skip-health`

Ejemplo:

```bash
php deploy.php --skip-tests
```

## Logs
Todos los eventos de deploy se registran en:

`logs/app-YYYY-MM-DD.log`

