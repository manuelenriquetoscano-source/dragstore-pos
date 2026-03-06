<?php
require_once 'config/bootstrap.php';
requireApiLogin(['admin', 'caja']);
require_once 'controllers/VentaController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new VentaController($db);

$result = $controller->procesarDesdeJson(file_get_contents('php://input'));
if (!$result['ok']) {
    jsonError($result['message'], $result['statusCode']);
}

jsonSuccess(null, $result['message']);
