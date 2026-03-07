<?php

require_once __DIR__ . '/../services/VentaService.php';

class VentaController
{
    private $service;

    public function __construct(PDO $db)
    {
        $this->service = new VentaService($db);
    }

    public function procesarDesdeJson(string $rawJson, ?array $authUser = null): array
    {
        $payload = json_decode($rawJson, true);
        if (!is_array($payload)) {
            return ['ok' => false, 'message' => 'JSON invalido', 'statusCode' => 400];
        }

        $carrito = isset($payload['carrito']) && is_array($payload['carrito']) ? $payload['carrito'] : [];
        $total = isset($payload['total']) ? (float)$payload['total'] : 0;
        $pago = isset($payload['pago']) && is_array($payload['pago']) ? $payload['pago'] : [];

        $contexto = [];
        if (is_array($authUser) && isset($authUser['id'])) {
            $contexto['usuario_id'] = (int)$authUser['id'];
        }

        $result = $this->service->registrarConPago($carrito, $total, $pago, $contexto);
        return [
            'ok' => $result['ok'],
            'message' => $result['message'],
            'statusCode' => $result['ok'] ? 200 : 422
        ];
    }

    public function obtenerReporteDiario(?string $fecha = null): array
    {
        return $this->service->obtenerReporteDiario($fecha);
    }

    public function anularVentaDesdeJson(string $rawJson, ?array $authUser = null): array
    {
        $payload = json_decode($rawJson, true);
        if (!is_array($payload)) {
            return ['ok' => false, 'message' => 'JSON invalido', 'statusCode' => 400];
        }

        $ventaId = isset($payload['venta_id']) ? (int)$payload['venta_id'] : 0;
        $motivo = isset($payload['motivo']) ? (string)$payload['motivo'] : '';
        $actorUserId = (is_array($authUser) && isset($authUser['id'])) ? (int)$authUser['id'] : null;

        $result = $this->service->anularVenta($ventaId, $motivo, $actorUserId);
        return [
            'ok' => $result['ok'],
            'message' => $result['message'],
            'statusCode' => $result['ok'] ? 200 : 422
        ];
    }

    public function obtenerDetalleTicket(int $ventaId): array
    {
        return $this->service->obtenerDetalleTicket($ventaId);
    }

    public function obtenerUltimaVentaId(?array $authUser = null): array
    {
        $usuarioId = null;
        if (is_array($authUser) && isset($authUser['id'])) {
            $usuarioId = (int)$authUser['id'];
        }
        return $this->service->obtenerUltimaVentaId($usuarioId);
    }
}
