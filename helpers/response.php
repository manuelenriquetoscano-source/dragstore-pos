<?php

function jsonResponse(array $payload, int $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    if (!isset($payload['request_id']) && isset($_SERVER['APP_REQUEST_ID'])) {
        $payload['request_id'] = $_SERVER['APP_REQUEST_ID'];
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonSuccess($data = null, string $message = 'OK', int $statusCode = 200)
{
    $payload = [
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ];
    jsonResponse($payload, $statusCode);
}

function jsonError(string $message, int $statusCode = 400, array $extra = [])
{
    $payload = array_merge([
        'status' => 'error',
        'message' => $message
    ], $extra);
    jsonResponse($payload, $statusCode);
}
