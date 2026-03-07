<?php
require_once __DIR__ . '/config/bootstrap.php';
requireApiLogin(['admin', 'caja']);
require_once __DIR__ . '/controllers/PromocionController.php';

$database = new Database();
$db = $database->getConnection();
$payload = ['updated_at' => null, 'rules' => []];

if ($db) {
    $controller = new PromocionController($db);
    if ($controller->tableExists()) {
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $payload['rules'] = $controller->listarActivasRuntime();
    } else {
        $configFile = __DIR__ . '/config/promociones_pos.php';
        if (is_file($configFile)) {
            $loaded = require $configFile;
            if (is_array($loaded)) {
                $payload['updated_at'] = isset($loaded['updated_at']) ? (string)$loaded['updated_at'] : null;
                $payload['rules'] = isset($loaded['rules']) && is_array($loaded['rules']) ? $loaded['rules'] : [];
            }
        }
    }
}

jsonResponse([
    'status' => 'success',
    'message' => 'Promociones cargadas',
    'updated_at' => $payload['updated_at'],
    'data' => $payload['rules']
]);
