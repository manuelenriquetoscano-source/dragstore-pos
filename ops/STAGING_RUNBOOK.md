# Staging Runbook

## Objetivo
Validar funcionalidad y estabilidad antes de pasar a producción.

## Setup mínimo
- `.env` con `APP_ENV=staging`
- `APP_DEBUG=false`
- Credenciales de DB de staging
- `APP_VERSION` seteado al build actual

## Smoke tests manuales
1. `GET /dragstore-pos/health.php` retorna `200` y `database.ok=true`.
2. Login con usuario `admin`.
3. Alta de producto desde `views/productos/crear.php`.
4. Listado inventario y filtro bajo stock.
5. Login con usuario `caja`.
6. Flujo de venta en `views/ventas/caja.php`:
   - Buscar producto
   - Confirmar venta
   - Abrir ticket e imprimir
7. Confirmar redirección final al POS.

## Observabilidad
- Revisar logs de request y errores en `logs/app-YYYY-MM-DD.log`.
- Verificar que `request_id` aparezca en respuestas JSON y logs.

## Criterio de salida
- Sin errores 5xx en flujos críticos.
- Sin fallas en smoke tests.
- Sin errores críticos en logs.

