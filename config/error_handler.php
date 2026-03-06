<?php

require_once __DIR__ . '/../helpers/logger.php';

if (!function_exists('wantsJsonResponse')) {
    function wantsJsonResponse(): bool
    {
        if (PHP_SAPI === 'cli') {
            return false;
        }

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (stripos($accept, 'application/json') !== false) {
            return true;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return preg_match('/(buscar_ajax|procesar_venta)\.php/i', $uri) === 1;
    }
}

if (!function_exists('renderUnhandledException')) {
    function renderUnhandledException(Throwable $exception): void
    {
        $debug = env('APP_DEBUG', 'false') === 'true';
        $errorId = bin2hex(random_bytes(6));

        appLog('error', 'Unhandled exception', [
            'error_id' => $errorId,
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "[$errorId] " . $exception->getMessage() . PHP_EOL);
            return;
        }

        if (wantsJsonResponse()) {
            $message = $debug ? $exception->getMessage() : 'Error interno del servidor';
            jsonResponse([
                'status' => 'error',
                'message' => $message,
                'error_id' => $errorId
            ], 500);
        }

        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
        $message = $debug
            ? htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8')
            : 'Ocurrió un error inesperado.';
        echo "<h1>Error interno</h1><p>{$message}</p><small>ID: {$errorId}</small>";
    }
}

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $exception) {
    renderUnhandledException($exception);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if (!$error) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    $exception = new ErrorException(
        $error['message'],
        0,
        $error['type'],
        $error['file'],
        $error['line']
    );
    renderUnhandledException($exception);
});

