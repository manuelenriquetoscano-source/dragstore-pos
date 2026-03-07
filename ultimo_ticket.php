<?php
require_once __DIR__ . '/config/bootstrap.php';
requireApiLogin(['admin', 'caja']);
require_once __DIR__ . '/controllers/VentaController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new VentaController($db);
$user = currentUser();

$result = $controller->obtenerUltimaVentaId($user);
if (!$result['ok']) {
    jsonError($result['message'], 404);
}

jsonSuccess(['venta_id' => (int)$result['venta_id']], 'Ultimo ticket obtenido');
