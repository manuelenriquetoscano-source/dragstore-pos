<?php

function appLog(string $level, string $message, array $context = []): void
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    if (!isset($context['request_id']) && isset($_SERVER['APP_REQUEST_ID'])) {
        $context['request_id'] = $_SERVER['APP_REQUEST_ID'];
    }

    $payload = [
        'ts' => date('c'),
        'level' => strtolower($level),
        'message' => $message,
        'context' => $context
    ];
    $line = json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n";
    $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
    @file_put_contents($logFile, $line, FILE_APPEND);
}
