<?php
require_once __DIR__ . '/config/bootstrap.php';
requireApiLogin(['admin', 'caja']);
require_once __DIR__ . '/controllers/TurnoCajaController.php';
require_once __DIR__ . '/services/AuditService.php';

$database = new Database();
$db = $database->getConnection();
$controller = new TurnoCajaController($db);
$audit = new AuditService($db);
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
    if (($user['role'] ?? '') !== 'admin') {
        jsonError('Solo un administrador puede abrir turnos', 403);
    }
    $result = $controller->abrirDesdeJson($raw, $usuarioId);
    if (!$result['ok']) {
        jsonError($result['message'], $result['statusCode']);
    }
    $turnoId = (int)($result['data']['id'] ?? 0);
    $audit->registrar(
        (int)($user['id'] ?? 0),
        (string)($user['username'] ?? ''),
        'turno.open',
        'turno',
        $turnoId > 0 ? $turnoId : null,
        [
            'monto_inicial' => $result['data']['monto_inicial'] ?? null,
            'role' => $user['role'] ?? null
        ]
    );
    jsonSuccess($result['data'], $result['message']);
}

if ($action === 'cerrar') {
    if (($user['role'] ?? '') !== 'admin') {
        jsonError('Solo un administrador puede cerrar turnos', 403);
    }
    $result = $controller->cerrarDesdeJson($raw, $usuarioId);
    if (!$result['ok']) {
        jsonError($result['message'], $result['statusCode']);
    }
    $resumen = $result['data']['resumen'] ?? [];
    $turnoId = isset($resumen['turno_id']) ? (int)$resumen['turno_id'] : 0;
    $audit->registrar(
        (int)($user['id'] ?? 0),
        (string)($user['username'] ?? ''),
        'turno.close',
        'turno',
        $turnoId > 0 ? $turnoId : null,
        [
            'monto_inicial' => $resumen['monto_inicial'] ?? null,
            'monto_final_declarado' => $resumen['monto_final_declarado'] ?? null,
            'total_ventas' => $resumen['total_ventas'] ?? null,
            'total_efectivo' => $resumen['total_efectivo'] ?? null,
            'esperado_caja' => $resumen['esperado_caja'] ?? null,
            'diferencia' => $resumen['diferencia'] ?? null,
            'cantidad_ventas' => $resumen['cantidad_ventas'] ?? null,
            'role' => $user['role'] ?? null
        ]
    );
    jsonSuccess($result['data'], $result['message']);
}

jsonError('Accion no valida', 400);
