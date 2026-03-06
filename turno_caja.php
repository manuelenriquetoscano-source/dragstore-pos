<?php
require_once __DIR__ . '/config/bootstrap.php';
requireApiLogin(['admin', 'caja']);
require_once __DIR__ . '/controllers/TurnoCajaController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new TurnoCajaController($db);
$user = currentUser();
$usuarioId = (int)($user['id'] ?? 0);
$action = isset($_GET['action']) ? (string)$_GET['action'] : '';

if ($action === 'status') {
    $result = $controller->estadoActual($usuarioId);
    if (!$result['ok']) {
        jsonError($result['message'], 422);
    }
    jsonSuccess($result, 'Estado de turno');
}

if ($action === 'historial') {
    $result = $controller->listarUltimos($usuarioId, 10);
    if (!$result['ok']) {
        jsonError($result['message'], 422);
    }
    jsonSuccess($result, 'Historial de turnos');
}

$raw = file_get_contents('php://input');
if ($action === 'abrir') {
    $result = $controller->abrirDesdeJson($raw, $usuarioId);
    if (!$result['ok']) {
        jsonError($result['message'], $result['statusCode']);
    }
    jsonSuccess($result['data'], $result['message']);
}

if ($action === 'cerrar') {
    $result = $controller->cerrarDesdeJson($raw, $usuarioId);
    if (!$result['ok']) {
        jsonError($result['message'], $result['statusCode']);
    }
    jsonSuccess($result['data'], $result['message']);
}

jsonError('Accion no valida', 400);
