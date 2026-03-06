<?php
// Archivo: procesar_venta.php
require_once 'config/database.php';
require_once 'models/Venta.php';

// Recibimos los datos JSON enviados por el navegador
$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if ($datos && !empty($datos['carrito'])) {
    $database = new Database();
    $db = $database->getConnection();
    $venta = new Venta($db);

    $carrito = $datos['carrito'];
    $total = $datos['total'];

    // Intentamos registrar la venta (esto descuenta stock automáticamente)
    if ($venta->registrarVenta($carrito, $total)) {
        echo json_encode(['status' => 'success', 'message' => 'Venta completada']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al procesar la transacción']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No hay productos para vender']);
}