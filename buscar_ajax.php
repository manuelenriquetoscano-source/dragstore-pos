<?php
require_once 'config/bootstrap.php';
requireApiLogin(['admin', 'caja']);
require_once 'controllers/ProductoController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new ProductoController($db);
$termino = isset($_GET['codigo']) ? (string)$_GET['codigo'] : '';
$result = $controller->buscarParaCaja($termino);

if (!$result['ok']) {
    jsonResponse([
        'status' => 'error',
        'message' => $result['message']
    ], $result['statusCode']);
}

jsonResponse([
    'status' => 'success',
    'message' => $result['message'],
    'count' => $result['count'],
    'data' => $result['data']
]);
