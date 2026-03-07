<?php
require_once __DIR__ . '/config/bootstrap.php';
requireApiLogin(['admin']);
require_once __DIR__ . '/controllers/VentaController.php';
require_once __DIR__ . '/services/AuditService.php';

$database = new Database();
$db = $database->getConnection();
$controller = new VentaController($db);
$audit = new AuditService($db);
$user = currentUser();
$rawJson = file_get_contents('php://input');
$payload = json_decode($rawJson, true);

$result = $controller->anularVentaDesdeJson($rawJson, $user);
if (!$result['ok']) {
    jsonError($result['message'], $result['statusCode']);
}

$ventaId = isset($payload['venta_id']) ? (int)$payload['venta_id'] : null;
$motivo = isset($payload['motivo']) ? (string)$payload['motivo'] : '';
$audit->registrar(
    (int)($user['id'] ?? 0),
    (string)($user['username'] ?? ''),
    'venta.cancel',
    'venta',
    $ventaId,
    ['motivo' => $motivo]
);

jsonSuccess(null, $result['message']);
