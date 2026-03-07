<?php
require_once '../../config/bootstrap.php';
requireLogin(['admin']);
require_once '../../controllers/ProductoController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new ProductoController($db);
$filtroBajoStock = isset($_GET['filtro']) && $_GET['filtro'] === 'bajo_stock';
$productos = $controller->listarInventario($filtroBajoStock);

$totalStockCritico = 0;
$totalConVencidos = 0;
$totalPorVencer = 0;
foreach ($productos as $p) {
    if ((int)($p['stock'] ?? 0) <= (int)($p['stock_minimo'] ?? 5)) {
        $totalStockCritico++;
    }
    if ((int)($p['lotes_vencidos'] ?? 0) > 0) {
        $totalConVencidos++;
    } elseif ((int)($p['lotes_por_vencer'] ?? 0) > 0) {
        $totalPorVencer++;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Dragstore</title>
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.68);
            --glass-border: rgba(255, 255, 255, 0.45);
            --glass-shadow: 0 24px 45px -28px rgba(44, 62, 80, 0.5);
            --glass-blur: 12px;
            --text-primary: #1f2d3d;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background:
                radial-gradient(circle at 15% 20%, rgba(52, 152, 219, 0.2), transparent 38%),
                radial-gradient(circle at 82% 12%, rgba(231, 76, 60, 0.1), transparent 35%),
                linear-gradient(145deg, #eaf3fc 0%, #f8fbff 45%, #eef7f4 100%);
            padding: 16px;
            margin: 0;
            color: var(--text-primary);
            min-height: 100vh;
        }
        .container {
            max-width: 1180px;
            margin: auto;
            background: var(--glass-bg);
            padding: 16px;
            border-radius: 16px;
            box-shadow: var(--glass-shadow);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
        }
        header {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            margin-bottom: 16px;
            gap: 10px;
        }
        h1 { margin: 0; font-size: 24px; }
        .summary {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 14px;
        }
        .sum-card {
            background: rgba(255,255,255,0.55);
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 10px;
            padding: 10px;
            font-size: 13px;
            font-weight: 700;
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            width: 100%;
        }
        .table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 12px;
            overflow: hidden;
        }
        th, td {
            padding: 10px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            text-align: left;
            white-space: nowrap;
            font-size: 13px;
        }
        th { background: rgba(44, 62, 80, 0.9); color: white; }
        td { background: rgba(255, 255, 255, 0.42); }
        .bajo-stock td { background-color: rgba(231, 76, 60, 0.12); }
        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid rgba(255,255,255,0.45);
            margin-left: 4px;
        }
        .badge-danger { background: #e74c3c; color: white; }
        .badge-warning { background: #f59e0b; color: white; }
        .badge-ok { background: #27ae60; color: white; }
        .btn {
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 10px 18px -16px rgba(44, 62, 80, 0.8);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-blue { background: linear-gradient(135deg, #3498db, #2572a6); color: white; }
        .btn-gray { background: linear-gradient(135deg, #95a5a6, #6f8082); color: white; }
        .btn-red { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; }
        .note {
            background: rgba(231, 76, 60, 0.14);
            color: #7f1d1d;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid rgba(231, 76, 60, 0.3);
            font-size: 13px;
            font-weight: 700;
        }
        @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
            .container { background: rgba(255, 255, 255, 0.92); }
        }
        @media (min-width: 769px) {
            body { padding: 30px; }
            .container { padding: 24px; }
            header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
            }
            h1 { font-size: 32px; }
            .summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            th, td { font-size: 14px; }
        }
        @media print {
            .actions { display: none; }
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>Inventario de Productos</h1>
        <div class="actions">
            <?php if ($filtroBajoStock): ?>
                <a href="index_productos.php" class="btn btn-gray">Ver Todo</a>
            <?php else: ?>
                <a href="index_productos.php?filtro=bajo_stock" class="btn btn-red">Solo Stock Critico</a>
            <?php endif; ?>
            <a href="crear.php" class="btn btn-blue">Nuevo Producto</a>
            <a href="reporte_vencimientos.php" class="btn btn-gray">Reporte Vencimientos</a>
            <a href="../../index.php" class="btn btn-blue">Menu Principal</a>
        </div>
    </header>

    <div class="summary">
        <div class="sum-card">Stock critico: <?php echo (int)$totalStockCritico; ?></div>
        <div class="sum-card">Con lotes vencidos: <?php echo (int)$totalConVencidos; ?></div>
        <div class="sum-card">Por vencer (30 dias): <?php echo (int)$totalPorVencer; ?></div>
    </div>

    <?php if ($filtroBajoStock): ?>
        <div class="note">Mostrando productos con stock actual menor o igual al stock minimo.</div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Stock Minimo</th>
                    <th>Lotes Activos</th>
                    <th>Prox. Vencimiento</th>
                    <th>Alerta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($productos)): ?>
                <tr><td colspan="9">No hay productos para mostrar.</td></tr>
            <?php else: ?>
                <?php foreach ($productos as $row): ?>
                    <?php
                    $stock = (int)$row['stock'];
                    $stockMinimo = (int)($row['stock_minimo'] ?? 5);
                    $stockCritico = $stock <= $stockMinimo;
                    $lotesVencidos = (int)($row['lotes_vencidos'] ?? 0);
                    $lotesPorVencer = (int)($row['lotes_por_vencer'] ?? 0);
                    $alerta = 'OK';
                    $alertaClass = 'badge-ok';
                    if ($lotesVencidos > 0) {
                        $alerta = 'VENCIDO';
                        $alertaClass = 'badge-danger';
                    } elseif ($lotesPorVencer > 0) {
                        $alerta = 'POR VENCER';
                        $alertaClass = 'badge-warning';
                    }
                    ?>
                    <tr class="<?php echo $stockCritico ? 'bajo-stock' : ''; ?>">
                        <td><?php echo htmlspecialchars((string)$row['codigo_barras'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>$<?php echo number_format((float)$row['precio'], 2, '.', ''); ?></td>
                        <td>
                            <?php echo $stock; ?>
                            <?php if ($stockCritico): ?>
                                <span class="badge badge-danger">Reponer</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $stockMinimo; ?></td>
                        <td><?php echo (int)($row['lotes_activos'] ?? 0); ?></td>
                        <td><?php echo !empty($row['proximo_vencimiento']) ? htmlspecialchars((string)$row['proximo_vencimiento'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                        <td>
                            <span class="badge <?php echo $alertaClass; ?>"><?php echo $alerta; ?></span>
                            <?php if ($lotesVencidos > 0): ?>
                                <span class="badge badge-danger"><?php echo $lotesVencidos; ?> vencido(s)</span>
                            <?php elseif ($lotesPorVencer > 0): ?>
                                <span class="badge badge-warning"><?php echo $lotesPorVencer; ?> por vencer</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn btn-blue" href="lotes.php?producto_id=<?php echo (int)$row['id']; ?>">Lotes</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="actions" style="margin-top: 16px;">
        <button onclick="window.print()" class="btn btn-gray">Imprimir Lista</button>
    </div>
</div>
</body>
</html>
