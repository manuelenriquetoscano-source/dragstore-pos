<?php

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/database.php';

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function currentUser()
{
    if (PHP_SAPI === 'cli') {
        return null;
    }
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function isLoggedIn()
{
    return currentUser() !== null;
}

function getAuthUsers()
{
    return [
        [
            'username' => env('AUTH_ADMIN_USER', 'admin'),
            'password' => env('AUTH_ADMIN_PASS', 'admin123'),
            'role' => 'admin',
            'display_name' => env('AUTH_ADMIN_NAME', 'Administrador')
        ],
        [
            'username' => env('AUTH_CAJA_USER', 'caja'),
            'password' => env('AUTH_CAJA_PASS', 'caja123'),
            'role' => 'caja',
            'display_name' => env('AUTH_CAJA_NAME', 'Caja')
        ]
    ];
}

function getAuthDbConnection(): ?PDO
{
    static $cached = false;
    if ($cached instanceof PDO || $cached === null) {
        return $cached;
    }

    try {
        $database = new Database();
        $cached = $database->getConnection();
    } catch (Throwable $e) {
        $cached = null;
    }

    return $cached ?: null;
}

function isUsersTableAvailable(): bool
{
    $db = getAuthDbConnection();
    if (!$db) {
        return false;
    }

    try {
        $stmt = $db->query("SHOW TABLES LIKE 'usuarios'");
        return $stmt && (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function isAuditTableAvailable(): bool
{
    static $cached = false;
    if (is_bool($cached) && $cached === true) {
        return true;
    }

    $db = getAuthDbConnection();
    if (!$db) {
        return false;
    }

    try {
        $stmt = $db->query("SHOW TABLES LIKE 'audit_log'");
        $cached = $stmt && (bool)$stmt->fetchColumn();
        return (bool)$cached;
    } catch (Throwable $e) {
        return false;
    }
}

function recordAuditEvent(string $action, ?array $user = null, ?array $details = null): void
{
    if (PHP_SAPI === 'cli' || !isAuditTableAvailable()) {
        return;
    }

    $db = getAuthDbConnection();
    if (!$db) {
        return;
    }

    $userData = $user ?: currentUser();
    $actorUserId = isset($userData['id']) ? (int)$userData['id'] : null;
    $actorUsername = isset($userData['username']) ? (string)$userData['username'] : null;
    $entityId = $actorUserId ?: null;

    $payload = $details ?: [];
    if (!isset($payload['ip'])) {
        $payload['ip'] = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
    }
    if (!isset($payload['user_agent'])) {
        $payload['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';
    }

    $query = "INSERT INTO audit_log
              (actor_user_id, actor_username, action, entity_type, entity_id, details_json)
              VALUES (:actor_user_id, :actor_username, :action, :entity_type, :entity_id, :details_json)";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':actor_user_id' => $actorUserId,
            ':actor_username' => $actorUsername,
            ':action' => $action,
            ':entity_type' => 'session',
            ':entity_id' => $entityId,
            ':details_json' => json_encode($payload, JSON_UNESCAPED_UNICODE)
        ]);
    } catch (Throwable $e) {
        // No-op: auth flow should continue even if audit logging fails.
    }
}

function findUserByUsername(string $username): ?array
{
    $db = getAuthDbConnection();
    if (!$db) {
        return null;
    }

    $query = "SELECT id, username, password_hash, role, display_name, activo
              FROM usuarios
              WHERE username = :username
              LIMIT 1";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['activo'] !== 1) {
            return null;
        }
        return $row;
    } catch (Throwable $e) {
        return null;
    }
}

function loginWithDb(string $username, string $password): bool
{
    $user = findUserByUsername($username);
    if (!$user) {
        return false;
    }

    if (!password_verify($password, (string)$user['password_hash'])) {
        return false;
    }

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'display_name' => $user['display_name']
    ];
    recordAuditEvent('auth.login', $_SESSION['user'], [
        'method' => 'db',
        'role' => $user['role']
    ]);
    return true;
}

function loginWithEnvFallback(string $username, string $password): bool
{
    foreach (getAuthUsers() as $user) {
        if (hash_equals((string)$user['username'], $username) &&
            hash_equals((string)$user['password'], $password)) {
            $_SESSION['user'] = [
                'username' => $user['username'],
                'role' => $user['role'],
                'display_name' => $user['display_name']
            ];
            recordAuditEvent('auth.login', $_SESSION['user'], [
                'method' => 'env_fallback',
                'role' => $user['role']
            ]);
            return true;
        }
    }
    return false;
}

function attemptLogin($username, $password)
{
    $username = trim((string)$username);
    $password = (string)$password;

    if ($username === '' || $password === '') {
        return false;
    }

    if (isUsersTableAvailable()) {
        return loginWithDb($username, $password);
    }

    // Fallback temporal para entornos sin migración aplicada.
    return loginWithEnvFallback($username, $password);
}

function logoutUser()
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    $user = currentUser();
    if ($user) {
        recordAuditEvent('auth.logout', $user, [
            'role' => isset($user['role']) ? (string)$user['role'] : ''
        ]);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function requireLogin(array $roles = [])
{
    if (!isLoggedIn()) {
        header('Location: /dragstore-pos/login.php');
        exit;
    }

    if (!empty($roles)) {
        $user = currentUser();
        if (!$user || !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            echo 'Acceso denegado.';
            exit;
        }
    }
}

function requireApiLogin(array $roles = [])
{
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
        exit;
    }

    if (!empty($roles)) {
        $user = currentUser();
        if (!$user || !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Sin permisos']);
            exit;
        }
    }
}
