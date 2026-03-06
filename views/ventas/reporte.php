<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin(['admin', 'caja']);
require_once __DIR__ . '/../../controllers/VentaController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new VentaController($db);

$fechaSolicitada = isset($_GET['fecha']) ? trim((string)$_GET['fecha']) : date('Y-m-d');
$reporte = $controller->obtenerReporteDiario($fechaSolicitada);
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Diario - Drugstore POS</title>
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.70);
            --glass-border: rgba(255, 255, 255, 0.46);
            --glass-shadow: 0 24px 45px -28px rgba(44, 62, 80, 0.45);
            --text-primary: #1f2d3d;
            --text-muted: #64748b;
            --ok: #16a34a;
            --warn: #dc2626;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 14px;
            font-family: 'Segoe UI', sans-serif;
            color: var(--text-primary);
            background:
                radial-gradient(circle at 16% 18%, rgba(52, 152, 219, 0.18), transparent 40%),
                radial-gradient(circle at 85% 16%, rgba(241, 196, 15, 0.14), transparent 35%),
                linear-gradient(145deg, #e9f2fb 0%, #f7fbff 45%, #edf7f1 100%);
        }

        .wrap {
            max-width: 1050px;
            margin: 0 auto;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            box-shadow: var(--glass-shadow);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 16px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 10px;
            background: rgba(44, 62, 80, 0.90);
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        h1 {
            margin: 0 0 14px;
            font-size: 24px;
        }

        .filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .filters input[type="date"] {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 12px;
            min-width: 200px;
            background: rgba(255, 255, 255, 0.82);
        }

        .kpis {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin-bottom: 14px;
        }

        .kpi {
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.48);
            background: rgba(255, 255, 255, 0.58);
            padding: 12px;
        }

        .kpi .label {
            font-size: 12px;
            color: var(--text-muted);
        }

        .kpi .value {
            margin-top: 4px;
            font-size: 24px;
            font-weight: 800;
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
        }

        th, td {
            border: 1px solid rgba(148, 163, 184, 0.24);
            padding: 10px;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: rgba(44, 62, 80, 0.92);
            color: #fff;
            font-size: 14px;
        }

        td {
            background: rgba(255, 255, 255, 0.45);
            font-size: 14px;
        }

        .empty {
            margin-top: 10px;
            padding: 12px;
            border-radius: 10px;
            background: rgba(220, 38, 38, 0.10);
            color: #991b1b;
            border: 1px solid rgba(220, 38, 38, 0.25);
        }

        .ok-note {
            color: var(--ok);
            font-weight: 700;
            font-size: 13px;
        }

        @media (min-width: 769px) {
            body { padding: 24px; }
            .wrap { padding: 22px; }
            h1 { font-size: 30px; }
            .kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            th, td { font-size: 15px; }
            .btn { font-size: 14px; }
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: #fff; }
            .wrap { box-shadow: none; border: none; background: #fff; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="topbar no-print">
            <div>
                Usuario: <strong><?php echo htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                (<?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>)
            </div>
            <div class="actions">
                <a class="btn" href="/dragstore-pos/views/ventas/caja.php">POS</a>
                <a class="btn" href="/dragstore-pos/index.php">Menu</a>
                <a class="btn" href="/dragstore-pos/logout.php">Salir</a>
            </div>
        </div>

        <h1>Reporte Diario de Ventas</h1>

        <form class="filters no-print" method="GET" action="reporte.php">
            <input type="date" name="fecha" value="<?php echo htmlspecialchars($reporte['fecha'], ENT_QUOTES, 'UTF-8'); ?>">
            <button class="btn" type="submit">Filtrar</button>
            <button class="btn" type="button" onclick="window.print()">Imprimir</button>
        </form>

        <div class="kpis">
            <div class="kpi">
                <div class="label">Fecha del reporte</div>
                <div class="value"><?php echo htmlspecialchars($reporte['fecha'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="kpi">
                <div class="label">Cantidad de ventas</div>
                <div class="value"><?php echo (int)$reporte['cantidad']; ?></div>
            </div>
            <div class="kpi">
                <div class="label">Total vendido</div>
                <div class="value">$<?php echo number_format((float)$reporte['total'], 2); ?></div>
            </div>
        </div>

        <?php if (!empty($reporte['ventas'])): ?>
            <div class="ok-note">Reporte generado correctamente.</div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID Venta</th>
                            <th>Fecha y Hora</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reporte['ventas'] as $venta): ?>
                            <tr>
                                <td>#<?php echo (int)$venta['id']; ?></td>
                                <td><?php echo htmlspecialchars((string)$venta['fecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>$<?php echo number_format((float)$venta['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">
                No hay ventas registradas para la fecha seleccionada.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

