<?php

require_once __DIR__ . '/../services/TurnoCajaService.php';

class TurnoCajaController
{
    private $service;

    public function __construct(PDO $db)
    {
        $this->service = new TurnoCajaService($db);
    }

    public function estadoActual(int $usuarioId): array
    {
        return $this->service->estadoActual($usuarioId);
    }

    public function abrirDesdeJson(string $rawJson, int $usuarioId): array
    {
        $payload = json_decode($rawJson, true);
        if (!is_array($payload)) {
            return ['ok' => false, 'message' => 'JSON invalido', 'statusCode' => 400];
        }

        $montoInicial = isset($payload['monto_inicial']) ? (float)$payload['monto_inicial'] : 0;
        $observaciones = isset($payload['observaciones']) ? (string)$payload['observaciones'] : null;
        $result = $this->service->abrir($usuarioId, $montoInicial, $observaciones);

        return [
            'ok' => $result['ok'],
            'message' => $result['message'],
            'statusCode' => $result['ok'] ? 200 : 422,
            'data' => $result
        ];
    }

    public function cerrarDesdeJson(string $rawJson, int $usuarioId): array
    {
        $payload = json_decode($rawJson, true);
        if (!is_array($payload)) {
            return ['ok' => false, 'message' => 'JSON invalido', 'statusCode' => 400];
        }

        if (!array_key_exists('monto_final_declarado', $payload) || trim((string)$payload['monto_final_declarado']) === '') {
            return ['ok' => false, 'message' => 'Debe ingresar el monto final declarado', 'statusCode' => 422];
        }

        $montoFinal = (float)$payload['monto_final_declarado'];
        $result = $this->service->cerrar($usuarioId, $montoFinal);
        return [
            'ok' => $result['ok'],
            'message' => $result['message'],
            'statusCode' => $result['ok'] ? 200 : 422,
            'data' => $result
        ];
    }

    public function listarUltimos(int $usuarioId, int $limit = 10): array
    {
        return $this->service->listarUltimos($usuarioId, $limit);
    }

    public function listarPaginado(array $filters, bool $adminMode, int $usuarioActualId, int $page = 1, int $perPage = 20): array
    {
        return $this->service->listarPaginado($filters, $adminMode, $usuarioActualId, $page, $perPage);
    }

    public function exportarCsv(array $filters, bool $adminMode, int $usuarioActualId, int $limit = 5000): array
    {
        return $this->service->exportarCsv($filters, $adminMode, $usuarioActualId, $limit);
    }
}
