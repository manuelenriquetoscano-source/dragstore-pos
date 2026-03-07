<?php
require_once '../../config/bootstrap.php';
requireLogin(['admin']);
require_once '../../controllers/ProductoController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new ProductoController($db);

$estado = isset($_GET['estado']) ? (string)$_GET['estado'] : '';
$dias = isset($_GET['dias']) ? (int)$_GET['dias'] : 30;
$dias = max(1, min(180, $dias));

$rows = $controller->listarReporteVencimientos($estado, $dias);

if (isset($_GET['export']) && (string)$_GET['export'] === 'csv') {
    $filename = 'reporte_vencimientos_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['producto_id', 'codigo_barras', 'nombre', 'numero_lote', 'fecha_vencimiento', 'dias_para_vencer', 'cantidad_disponible', 'estado_calculado'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            (int)$r['producto_id'],
            (string)$r['codigo_barras'],
            (string)$r['nombre'],
            (string)$r['numero_lote'],
            (string)$r['fecha_vencimiento'],
            (int)$r['dias_para_vencer'],
            (int)$r['cantidad_disponible'],
            (string)$r['estado_calculado']
        ], ';');
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Vencimientos - Drugstore POS</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #eef5fb; padding: 14px; color: #1f2d3d; }
        .wrap { max-width: 1100px; margin: 0 auto; background: rgba(255,255,255,0.84); border: 1px solid rgba(255,255,255,0.62); border-radius: 14px; padding: 14px; box-shadow: 0 24px 44px -30px rgba(44, 62, 80, 0.5); }
        .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
        h1 { margin: 0; font-size: 24px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { border: 1px solid rgba(255,255,255,0.45); background: rgba(44, 62, 80, 0.92); color: #fff; text-decoration: none; border-radius: 10px; padding: 9px 13px; font-size: 13px; font-weight: 700; cursor: pointer; }
        .filters { display: grid; grid-template-columns: 1fr; gap: 8px; margin-bottom: 10px; }
        .filters label { font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 3px; display: block; }
        .filters select, .filters input { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 9px 10px; font-size: 14px; background: rgba(255,255,255,0.88); }
        .table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid rgba(148, 163, 184, 0.35); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid rgba(148, 163, 184, 0.25); text-align: left; font-size: 13px; white-space: nowrap; }
        th { background: rgba(51, 65, 85, 0.92); color: #fff; }
        .badge { display: inline-block; border-radius: 999px; padding: 3px 8px; font-size: 11px; font-weight: 800; }
        .b-ok { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .b-warn { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .b-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .b-gray { background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; }
        @media (min-width: 980px) {
            body { padding: 24px; }
            .wrap { padding: 18px; }
            .filters { grid-template-columns: 180px 140px auto auto auto; align-items: end; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1>Reporte de Vencimientos</h1>
        <div class="actions">
            <a class="btn" href="index_productos.php">Inventario</a>
            <a class="btn" href="../../index.php">Menu</a>
        </div>
    </div>

    <form class="filters" method="GET" action="reporte_vencimientos.php">
        <div>
            <label>Estado</label>
            <select name="estado">
                <option value="" <?php echo $estado === '' ? 'selected' : ''; ?>>Todos</option>
                <option value="vencido" <?php echo $estado === 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                <option value="por_vencer" <?php echo $estado === 'por_vencer' ? 'selected' : ''; ?>>Por vencer</option>
                <option value="activo" <?php echo $estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
            </select>
        </div>
        <div>
            <label>Ventana dias</label>
            <input type="number" name="dias" min="1" max="180" value="<?php echo (int)$dias; ?>">
        </div>
        <button class="btn" type="submit">Filtrar</button>
        <a class="btn" href="reporte_vencimientos.php">Limpiar</a>
        <button class="btn" type="submit" name="export" value="csv">Exportar CSV</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Lote</th>
                    <th>Vencimiento</th>
                    <th>Dias</th>
                    <th>Disponible</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6">Sin lotes para los filtros seleccionados.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $estadoCalc = (string)$r['estado_calculado'];
                    $class = 'b-ok';
                    if ($estadoCalc === 'vencido') {
                        $class = 'b-danger';
                    } elseif ($estadoCalc === 'por_vencer') {
                        $class = 'b-warn';
                    } elseif ($estadoCalc === 'agotado') {
                        $class = 'b-gray';
                    }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$r['codigo_barras'] . ' - ' . (string)$r['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['numero_lote'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['fecha_vencimiento'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)$r['dias_para_vencer']; ?></td>
                        <td><?php echo (int)$r['cantidad_disponible']; ?></td>
                        <td><span class="badge <?php echo $class; ?>"><?php echo htmlspecialchars($estadoCalc, ENT_QUOTES, 'UTF-8'); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
