# Release Checklist

## 1. Pre-release
- Confirmar rama limpia y PR aprobado.
- Ejecutar tests: `php tests/run.php`.
- Verificar sintaxis PHP: `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }`.
- Validar `.env` de destino (DB, APP_ENV, APP_DEBUG=false, APP_VERSION).

## 2. Base de datos
- Backup de DB antes de desplegar.
- Ejecutar migraciones: `php migrate.php`.
- Confirmar tabla `schema_migrations` actualizada.

## 3. Deploy
- Actualizar código en servidor.
- Validar permisos de escritura en `logs/`.
- Reiniciar stack web/PHP si aplica.

## 4. Post-deploy
- Revisar healthcheck: `GET /dragstore-pos/health.php` (200).
- Probar login admin/caja.
- Probar flujo completo POS (buscar, vender, ticket).
- Revisar logs de aplicación en `logs/app-YYYY-MM-DD.log`.

## 5. Rollback (si falla)
- Restaurar backup de DB.
- Volver al commit/tag anterior.
- Revalidar `health.php`.

