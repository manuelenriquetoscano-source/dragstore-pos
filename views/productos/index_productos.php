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
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 30px; }
        .container { max-width: 900px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border: 1px solid #eee; text-align: left; }
        th { background: #2c3e50; color: white; }
        
        /* Resaltado para productos en alerta */
        .bajo-stock { background-color: #fff5f5; color: #e74c3c; }
        .badge-danger { background: #e74c3c; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
        
        .btn { padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 14px; }
        .btn-blue { background: #3498db; color: white; }
        .btn-gray { background: #95a5a6; color: white; }
        
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