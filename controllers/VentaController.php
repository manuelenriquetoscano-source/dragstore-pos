<?php

require_once __DIR__ . '/../services/VentaService.php';

class VentaController
{
    private $service;

    public function __construct(PDO $db)
    {
        $this->service = new VentaService($db);
    }

    public function procesarDesdeJson(string $rawJson): array
    {
        $payload = json_decode($rawJson, true);
        if (!is_array($payload)) {
            return ['ok' => false, 'message' => 'JSON inválido', 'statusCode' => 400];
        }

        $carrito = isset($payload['carrito']) && is_array($payload['carrito']) ? $payload['carrito'] : [];
        $total = isset($payload['total']) ? (float)$payload['total'] : 0;

        $result = $this->service->registrar($carrito, $total);
        return [
            'ok' => $result['ok'],
            'message' => $result['message'],
            'statusCode' => $result['ok'] ? 200 : 422
        ];
    }
}
