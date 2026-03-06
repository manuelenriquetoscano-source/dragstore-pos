<?php
require_once 'config/bootstrap.php';
requireApiLogin(['admin', 'caja']);
require_once 'controllers/VentaController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new VentaController($db);
$user = currentUser();

$result = $controller->procesarDesdeJson(file_get_contents('php://input'), $user);
if (!$result['ok']) {
    jsonError($result['message'], $result['statusCode']);
}

jsonSuccess(null, $result['message']);
