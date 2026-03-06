<?php
require_once __DIR__ . '/config/bootstrap.php';

$appVersion = env('APP_VERSION', '1.0.0');
$dbOk = false;
$dbMessage = 'not_checked';

try {
    $database = new Database();
    $db = $database->getConnection();
    if ($db instanceof PDO) {
        $db->query('SELECT 1');
        $dbOk = true;
        $dbMessage = 'ok';
    } else {
        $dbMessage = 'connection_null';
    }
} catch (Throwable $e) {
    $dbOk = false;
    $dbMessage = 'error';
    appLog('error', 'health.db_check_failed', ['error' => $e->getMessage()]);
}

$statusCode = $dbOk ? 200 : 503;
jsonResponse([
    'status' => $dbOk ? 'ok' : 'degraded',
    'service' => 'dragstore-pos',
    'version' => $appVersion,
    'time' => date('c'),
    'checks' => [
        'database' => [
            'ok' => $dbOk,
            'message' => $dbMessage
        ]
    ]
], $statusCode);

