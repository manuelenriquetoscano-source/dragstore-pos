<?php
require_once '../../config/bootstrap.php';
requireLogin(['admin']);
require_once '../../controllers/ProductoController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new ProductoController($db);
$rows = $controller->listarReporteMargen();

if (isset($_GET['export']) && (string)$_GET['export'] === 'csv') {
    $filename = 'reporte_margen_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id', 'codigo_barras', 'nombre', 'precio', 'costo_referencia', 'margen_unitario', 'margen_pct', 'stock', 'stock_en_lotes', 'estado_margen'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            (int)$r['id'],
            (string)$r['codigo_barras'],
            (string)$r['nombre'],
            number_format((float)$r['precio'], 2, '.', ''),
            number_format((float)$r['costo_referencia'], 2, '.', ''),
            number_format((float)$r['margen_unitario'], 2, '.', ''),
            number_format((float)$r['margen_pct'], 2, '.', ''),
            (int)$r['stock'],
            (int)$r['stock_en_lotes'],
            (string)$r['estado_margen']
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
    <title>Reporte de Margen - Drugstore POS</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #eef5fb; padding: 14px; color: #1f2d3d; }
        .wrap { max-width: 1180px; margin: 0 auto; background: rgba(255,255,255,0.84); border: 1px solid rgba(255,255,255,0.62); border-radius: 14px; padding: 14px; box-shadow: 0 24px 44px -30px rgba(44, 62, 80, 0.5); }
        .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
        h1 { margin: 0; font-size: 24px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { border: 1px solid rgba(255,255,255,0.45); background: rgba(44, 62, 80, 0.92); color: #fff; text-decoration: none; border-radius: 10px; padding: 9px 13px; font-size: 13px; font-weight: 700; cursor: pointer; }
        .table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid rgba(148,163,184,0.35); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid rgba(148,163,184,0.25); text-align: left; font-size: 13px; white-space: nowrap; }
        th { background: rgba(51, 65, 85, 0.92); color: #fff; }
        .badge { display: inline-block; border-radius: 999px; padding: 3px 8px; font-size: 11px; font-weight: 800; }
        .b-ok { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .b-warn { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .b-bad { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1>Reporte de Margen por Producto</h1>
        <div class="actions">
            <a class="btn" href="index_productos.php">Inventario</a>
            <a class="btn" href="../../index.php">Menu</a>
            <a class="btn" href="reporte_margen.php?export=csv">Exportar CSV</a>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Costo Ref.</th>
                    <th>Margen U.</th>
                    <th>Margen %</th>
                    <th>Stock</th>
                    <th>Stock Lotes</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8">Sin datos.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $estado = (string)$r['estado_margen'];
                    $class = 'b-ok';
                    if ($estado === 'bajo') $class = 'b-warn';
                    if ($estado === 'negativo') $class = 'b-bad';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$r['codigo_barras'] . ' - ' . (string)$r['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>$<?php echo number_format((float)$r['precio'], 2, '.', ''); ?></td>
                        <td>$<?php echo number_format((float)$r['costo_referencia'], 2, '.', ''); ?></td>
                        <td>$<?php echo number_format((float)$r['margen_unitario'], 2, '.', ''); ?></td>
                        <td><?php echo number_format((float)$r['margen_pct'], 2, '.', ''); ?>%</td>
                        <td><?php echo (int)$r['stock']; ?></td>
                        <td><?php echo (int)$r['stock_en_lotes']; ?></td>
                        <td><span class="badge <?php echo $class; ?>"><?php echo htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
