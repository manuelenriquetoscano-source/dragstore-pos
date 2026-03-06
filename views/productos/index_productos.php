<?php
// Archivo: views/productos/index_productos.php
require_once '../../config/database.php';
require_once '../../models/Producto.php';

$database = new Database();
$db = $database->getConnection();
$producto = new Producto($db);

// --- LÓGICA DE FILTRADO ---
$filtroBajoStock = isset($_GET['filtro']) && $_GET['filtro'] == 'bajo_stock';

if ($filtroBajoStock) {
    // Solo productos con menos de 5 unidades
    $query = "SELECT * FROM productos WHERE stock < 5 ORDER BY stock ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
} else {
    // Carga normal de todos los productos
    $stmt = $producto->leerTodo();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
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
            padding: 30px;
            margin: 0;
            color: var(--text-primary);
            min-height: 100vh;
        }

        .container {
            max-width: 980px;
            margin: auto;
            background: var(--glass-bg);
            padding: 24px;
            border-radius: 16px;
            box-shadow: var(--glass-shadow);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 14px;
            flex-wrap: wrap;
        }

        h1 {
            margin: 0;
            color: #1f2d3d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 12px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            text-align: left;
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

        @media (max-width: 768px) {
            body { padding: 16px; }
            .container { padding: 16px; }
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
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr class="<?php echo ($row['stock'] < 5) ? 'bajo-stock' : ''; ?>">
                    <td><?php echo $row['codigo_barras']; ?></td>
                    <td><?php echo $row['nombre']; ?></td>
                    <td>$<?php echo number_format($row['precio'], 2); ?></td>
                    <td>
                        <?php echo $row['stock']; ?>
                        <?php if ($row['stock'] < 5): ?>
                            <span class="badge-danger">¡Reponer!</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="no-print" style="margin-top: 20px;">
        <button onclick="window.print()" class="btn btn-gray">🖨️ Imprimir Lista</button>
    </div>
</div>

</body>
</html>
