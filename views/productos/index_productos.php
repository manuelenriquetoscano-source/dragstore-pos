<?php
require_once '../../config/bootstrap.php';
requireLogin(['admin']);
require_once '../../controllers/ProductoController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new ProductoController($db);
$filtroBajoStock = isset($_GET['filtro']) && $_GET['filtro'] === 'bajo_stock';
$productos = $controller->listarInventario($filtroBajoStock);
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
            --text-muted: #64748b;
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
            max-width: 980px;
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
            margin-bottom: 20px;
            gap: 10px;
        }

        h1 {
            margin: 0;
            color: #1f2d3d;
            font-size: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 12px;
            overflow: hidden;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 12px;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            text-align: left;
            white-space: nowrap;
            font-size: 14px;
        }

        th {
            background: rgba(44, 62, 80, 0.9);
            color: white;
        }

        td {
            background: rgba(255, 255, 255, 0.42);
        }

        .bajo-stock td,
        .bajo-stock {
            background-color: rgba(231, 76, 60, 0.13);
            color: #b42318;
            font-weight: 600;
        }

        .badge-danger {
            background: #e74c3c;
            color: white;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 11px;
            border: 1px solid rgba(255, 255, 255, 0.45);
        }

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
            width: 100%;
        }

        .btn-blue {
            background: linear-gradient(135deg, #3498db, #2572a6);
            color: white;
        }

        .btn-gray {
            background: linear-gradient(135deg, #95a5a6, #6f8082);
            color: white;
        }

        @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
            .container {
                background: rgba(255, 255, 255, 0.92);
            }
        }

        .no-print {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        @media (min-width: 769px) {
            body { padding: 30px; }
            .container { padding: 24px; }
            header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                gap: 14px;
                flex-wrap: wrap;
            }
            h1 {
                font-size: 32px;
            }
            .no-print {
                display: flex;
                flex-direction: row;
                gap: 8px;
            }
            .btn {
                width: auto;
            }
            th,
            td {
                font-size: 16px;
            }
        }

        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>📦 Inventario de Productos</h1>
        <div class="no-print">
            <?php if ($filtroBajoStock): ?>
                <a href="index_productos.php" class="btn btn-gray">Ver Todo</a>
            <?php endif; ?>
            <a href="../../index.php" class="btn btn-blue">Menú Principal</a>
        </div>
    </header>

    <?php if ($filtroBajoStock): ?>
        <div style="background: #e74c3c; color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>⚠️ LISTA DE REPOSICIÓN:</strong> Mostrando productos con menos de 5 unidades.
        </div>
    <?php endif; ?>

    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Stock Actual</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $row): ?>
                <tr class="<?php echo ($row['stock'] < 5) ? 'bajo-stock' : ''; ?>">
                    <td><?php echo htmlspecialchars($row['codigo_barras'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>$<?php echo number_format($row['precio'], 2); ?></td>
                    <td>
                        <?php echo (int)$row['stock']; ?>
                        <?php if ($row['stock'] < 5): ?>
                            <span class="badge-danger">¡Reponer!</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <div class="no-print" style="margin-top: 20px;">
        <button onclick="window.print()" class="btn btn-gray">🖨️ Imprimir Lista</button>
    </div>
</div>

</body>
</html>
