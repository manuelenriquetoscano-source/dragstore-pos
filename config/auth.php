<?php

require_once __DIR__ . '/env.php';

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

function attemptLogin($username, $password)
{
    $username = trim((string)$username);
    $password = (string)$password;

    foreach (getAuthUsers() as $user) {
        if (hash_equals((string)$user['username'], $username) &&
            hash_equals((string)$user['password'], $password)) {
            $_SESSION['user'] = [
                'username' => $user['username'],
                'role' => $user['role'],
                'display_name' => $user['display_name']
            ];
            return true;
        }
    }

    return false;
}

function logoutUser()
{
    if (PHP_SAPI === 'cli') {
        return;
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
