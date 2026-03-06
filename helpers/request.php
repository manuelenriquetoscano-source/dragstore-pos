<?php

function initRequestContext(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    if (isset($GLOBALS['__request_context_initialized']) && $GLOBALS['__request_context_initialized'] === true) {
        return;
    }

    $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(8));
    $_SERVER['APP_REQUEST_ID'] = $requestId;
    $GLOBALS['__request_context_initialized'] = true;
    $GLOBALS['__request_start_time'] = microtime(true);

    header('X-Request-Id: ' . $requestId);

    appLog('info', 'request.start', [
        'request_id' => $requestId,
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    register_shutdown_function(function () {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $start = $GLOBALS['__request_start_time'] ?? microtime(true);
        $durationMs = (int)round((microtime(true) - $start) * 1000);
        appLog('info', 'request.finish', [
            'request_id' => getRequestId(),
            'status_code' => http_response_code(),
            'duration_ms' => $durationMs
        ]);
    });
}

function getRequestId(): ?string
{
    return $_SERVER['APP_REQUEST_ID'] ?? null;
}

