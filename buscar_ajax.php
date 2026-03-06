<?php
header('Content-Type: application/json');
require_once 'config/database.php';
require_once 'models/Producto.php';

$database = new Database();
$db = $database->getConnection();
$producto = new Producto($db);

$termino = isset($_GET['codigo']) ? $_GET['codigo'] : '';

if (!empty($termino)) {
    $stmt = $producto->buscar($termino);
    $num = $stmt->rowCount();

    if ($num > 0) {
        $productos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Aseguramos que los tipos de datos sean correctos para JS
            $row['stock'] = (int)$row['stock'];
            $row['precio'] = (float)$row['precio'];
            $productos[] = $row;
        }
        
        // SIEMPRE enviamos el array completo $productos.
        // Esto permite que el JavaScript recorra la lista sin errores.
        echo json_encode([
            "status" => "success",
            "count" => $num,
            "data" => $productos 
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "No se encontró nada"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Término vacío"]);
}