<?php
require_once '../../config/bootstrap.php';
requireLogin(['admin']);
require_once '../../controllers/ProductoController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new ProductoController($db);

$productoId = isset($_GET['producto_id']) ? (int)$_GET['producto_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productoId = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : $productoId;
}

$producto = $controller->obtenerProducto($productoId);
if (!$producto) {
    http_response_code(404);
    echo 'Producto no encontrado.';
    exit;
}

$mensaje = '';
$tipo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->registrarLoteDesdeRequest($_POST);
    $mensaje = (string)($result['message'] ?? '');
    $tipo = !empty($result['ok']) ? 'success' : 'danger';
    if (!empty($result['ok'])) {
        $producto = $controller->obtenerProducto($productoId);
    }
}

$lotes = $controller->listarLotesPorProducto($productoId);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lotes por Producto - Drugstore POS</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background:
                radial-gradient(circle at 16% 16%, rgba(52, 152, 219, 0.16), transparent 42%),
                radial-gradient(circle at 86% 10%, rgba(39, 174, 96, 0.14), transparent 36%),
                linear-gradient(145deg, #e9f2fb 0%, #f7fbff 46%, #edf6f1 100%);
            color: #1f2d3d;
            min-height: 100vh;
            padding: 14px;
        }
        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            background: rgba(255,255,255,0.78);
            border: 1px solid rgba(255,255,255,0.52);
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 24px 44px -30px rgba(44, 62, 80, 0.5);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .title { margin: 0; font-size: 24px; }
        .muted { color: #475569; font-size: 13px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn {
            border: 1px solid rgba(255,255,255,0.45);
            background: rgba(44, 62, 80, 0.92);
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        .summary {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }
        .card {
            background: rgba(255,255,255,0.58);
            border: 1px solid rgba(148,163,184,0.25);
            border-radius: 10px;
            padding: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-top: 10px;
        }
        .form-grid label { font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 3px; display: block; }
        .form-grid input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 14px;
            background: rgba(255,255,255,0.88);
        }
        .alert {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 13px;
            font-weight: 700;
        }
        .alert.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert.danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .table-wrap {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            margin-top: 10px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 8px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            text-align: left;
            font-size: 13px;
            white-space: nowrap;
        }
        th { background: rgba(51, 65, 85, 0.92); color: #fff; }
        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 800;
        }
        .badge.ok { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge.warn { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .badge.danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .badge.gray { background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; }
        @media (min-width: 980px) {
            body { padding: 24px; }
            .wrap { padding: 18px; }
            .summary { grid-template-columns: 1fr 1fr 1fr; }
            .form-grid { grid-template-columns: 1fr 1fr 1fr 1fr auto; align-items: end; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div>
            <h1 class="title">Lotes del Producto</h1>
            <div class="muted">
                <?php echo htmlspecialchars((string)$producto['codigo_barras'], ENT_QUOTES, 'UTF-8'); ?>
                - <?php echo htmlspecialchars((string)$producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
        <div class="actions">
            <a class="btn" href="index_productos.php">Volver a Inventario</a>
            <a class="btn" href="../../index.php">Menu Principal</a>
        </div>
    </div>

    <div class="summary">
        <div class="card"><strong>Stock actual:</strong> <?php echo (int)$producto['stock']; ?></div>
        <div class="card"><strong>Stock minimo:</strong> <?php echo (int)$producto['stock_minimo']; ?></div>
        <div class="card"><strong>Precio venta:</strong> $<?php echo number_format((float)$producto['precio'], 2, '.', ''); ?></div>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert <?php echo $tipo === 'success' ? 'success' : 'danger'; ?>">
            <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <strong>Registrar nuevo lote (ingreso de stock)</strong>
        <form method="POST" action="lotes.php?producto_id=<?php echo (int)$productoId; ?>">
            <input type="hidden" name="producto_id" value="<?php echo (int)$productoId; ?>">
            <div class="form-grid">
                <div>
                    <label>Numero de lote</label>
                    <input type="text" name="numero_lote" required>
                </div>
                <div>
                    <label>Vencimiento</label>
                    <input type="date" name="fecha_vencimiento" required>
                </div>
                <div>
                    <label>Cantidad</label>
                    <input type="number" name="cantidad" min="1" required>
                </div>
                <div>
                    <label>Costo unitario (opcional)</label>
                    <input type="number" step="0.01" min="0" name="costo_unitario">
                </div>
                <button class="btn" type="submit">Agregar lote</button>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Lote</th>
                    <th>Vencimiento</th>
                    <th>Cant. Inicial</th>
                    <th>Cant. Disponible</th>
                    <th>Costo Unit.</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($lotes)): ?>
                <tr><td colspan="7">Sin lotes cargados para este producto.</td></tr>
            <?php else: ?>
                <?php foreach ($lotes as $l): ?>
                    <?php
                    $estado = (string)($l['estado_calculado'] ?? 'activo');
                    $class = 'ok';
                    $label = 'Activo';
                    if ($estado === 'vencido') {
                        $class = 'danger';
                        $label = 'Vencido';
                    } elseif ($estado === 'por_vencer') {
                        $class = 'warn';
                        $label = 'Por vencer';
                    } elseif ($estado === 'agotado') {
                        $class = 'gray';
                        $label = 'Agotado';
                    }
                    ?>
                    <tr>
                        <td><?php echo (int)$l['id']; ?></td>
                        <td><?php echo htmlspecialchars((string)$l['numero_lote'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$l['fecha_vencimiento'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)$l['cantidad_inicial']; ?></td>
                        <td><?php echo (int)$l['cantidad_disponible']; ?></td>
                        <td><?php echo $l['costo_unitario'] !== null ? ('$' . number_format((float)$l['costo_unitario'], 2, '.', '')) : '-'; ?></td>
                        <td><span class="badge <?php echo $class; ?>"><?php echo $label; ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
