<?php

require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/TurnoCaja.php';
require_once __DIR__ . '/DashboardService.php';

class VentaService
{
    private $ventaModel;
    private $turnoModel;

    public function __construct(PDO $db)
    {
        $this->ventaModel = new Venta($db);
        $this->turnoModel = new TurnoCaja($db);
    }

    public function registrar(array $carrito, float $total): array
    {
        return $this->registrarConPago($carrito, $total, []);
    }

    public function registrarConPago(array $carrito, float $total, array $pago = [], array $contexto = []): array
    {
        if (empty($carrito)) {
            return ['ok' => false, 'message' => 'No hay productos para vender'];
        }

        if ($total <= 0) {
            return ['ok' => false, 'message' => 'Total invalido'];
        }

        foreach ($carrito as $item) {
            if (!isset($item['id'], $item['cantidad'], $item['precio'])) {
                return ['ok' => false, 'message' => 'Items de venta invalidos'];
            }
        }

        $validacionPago = $this->validarPago($total, $pago);
        if (!$validacionPago['ok']) {
            return ['ok' => false, 'message' => $validacionPago['message']];
        }

        $ventaContexto = [];
        if (isset($contexto['usuario_id']) && (int)$contexto['usuario_id'] > 0) {
            $usuarioId = (int)$contexto['usuario_id'];
            $turno = $this->turnoModel->obtenerAbiertoPorUsuario($usuarioId);
            if (!$turno) {
                return ['ok' => false, 'message' => 'Debe abrir un turno de caja antes de vender'];
            }
            $ventaContexto['usuario_id'] = $usuarioId;
            $ventaContexto['turno_id'] = (int)$turno['id'];
        }

        $ok = $this->ventaModel->registrarVenta($carrito, $total, $validacionPago['pago'], $ventaContexto);
        if (!$ok) {
            return ['ok' => false, 'message' => 'Error al procesar la transaccion'];
        }
        DashboardService::clearCacheGlobal();

        return ['ok' => true, 'message' => 'Venta completada'];
    }

    private function validarPago(float $total, array $pago = []): array
    {
        $metodo = isset($pago['metodo_pago']) ? strtolower(trim((string)$pago['metodo_pago'])) : 'efectivo';
        $metodosValidos = ['efectivo', 'tarjeta', 'transferencia', 'mixto'];
        if (!in_array($metodo, $metodosValidos, true)) {
            return ['ok' => false, 'message' => 'Metodo de pago invalido'];
        }

        $montoRecibido = isset($pago['monto_recibido']) ? (float)$pago['monto_recibido'] : null;
        $montoEfectivo = isset($pago['monto_efectivo']) ? (float)$pago['monto_efectivo'] : null;
        $montoDigital = isset($pago['monto_digital']) ? (float)$pago['monto_digital'] : null;

        if ($metodo === 'efectivo') {
            if ($montoRecibido === null) {
                $montoRecibido = $total;
            }
            if ($montoRecibido < $total) {
                return ['ok' => false, 'message' => 'Monto recibido insuficiente para pago en efectivo'];
            }
            return [
                'ok' => true,
                'pago' => [
                    'metodo_pago' => 'efectivo',
                    'monto_recibido' => round($montoRecibido, 2),
                    'vuelto' => round($montoRecibido - $total, 2),
                    'monto_efectivo' => round($total, 2),
                    'monto_digital' => 0.0
                ]
            ];
        }

        if ($metodo === 'tarjeta' || $metodo === 'transferencia') {
            return [
                'ok' => true,
                'pago' => [
                    'metodo_pago' => $metodo,
                    'monto_recibido' => round($total, 2),
                    'vuelto' => 0.0,
                    'monto_efectivo' => 0.0,
                    'monto_digital' => round($total, 2)
                ]
            ];
        }

        if ($montoEfectivo === null || $montoDigital === null) {
            return ['ok' => false, 'message' => 'Debe informar monto en efectivo y monto digital para pago mixto'];
        }
        if ($montoEfectivo < 0 || $montoDigital < 0) {
            return ['ok' => false, 'message' => 'Los montos de pago mixto no pueden ser negativos'];
        }

        $sumatoria = $montoEfectivo + $montoDigital;
        if ($sumatoria < $total) {
            return ['ok' => false, 'message' => 'Monto total insuficiente para pago mixto'];
        }

        return [
            'ok' => true,
            'pago' => [
                'metodo_pago' => 'mixto',
                'monto_recibido' => round($sumatoria, 2),
                'vuelto' => round($sumatoria - $total, 2),
                'monto_efectivo' => round($montoEfectivo, 2),
                'monto_digital' => round($montoDigital, 2)
            ]
        ];
    }

    public function obtenerReporteDiario(?string $fecha = null): array
    {
        $fecha = $fecha ?: date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return [
                'ok' => false,
                'message' => 'Formato de fecha invalido',
                'fecha' => date('Y-m-d'),
                'ventas' => [],
                'cantidad' => 0,
                'total' => 0.0
            ];
        }

        $stmt = $this->ventaModel->listarVentasPorFecha($fecha);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = 0.0;
        $totalAnulado = 0.0;
        foreach ($ventas as &$venta) {
            $venta['id'] = (int)$venta['id'];
            $venta['total'] = (float)$venta['total'];
            $venta['estado'] = isset($venta['estado']) ? (string)$venta['estado'] : 'completada';
            if ($venta['estado'] === 'anulada') {
                $totalAnulado += $venta['total'];
            } else {
                $total += $venta['total'];
            }
        }
        unset($venta);

        return [
            'ok' => true,
            'message' => 'Reporte generado',
            'fecha' => $fecha,
            'ventas' => $ventas,
            'cantidad' => count($ventas),
            'total' => $total,
            'total_anulado' => $totalAnulado
        ];
    }

    public function anularVenta(int $ventaId, string $motivo, ?int $actorUserId = null): array
    {
        if ($ventaId <= 0) {
            return ['ok' => false, 'message' => 'ID de venta invalido'];
        }

        $motivo = trim($motivo);
        if ($motivo === '') {
            return ['ok' => false, 'message' => 'Debe informar un motivo de anulacion'];
        }

        $ok = $this->ventaModel->anularVenta($ventaId, $motivo, $actorUserId);
        if (!$ok) {
            return ['ok' => false, 'message' => 'No se pudo anular la venta'];
        }
        DashboardService::clearCacheGlobal();

        return ['ok' => true, 'message' => 'Venta anulada correctamente'];
    }

    public function obtenerDetalleTicket(int $ventaId): array
    {
        if ($ventaId <= 0) {
            return ['ok' => false, 'message' => 'ID de venta invalido'];
        }

        $data = $this->ventaModel->obtenerVentaParaTicket($ventaId);
        if (!$data) {
            return ['ok' => false, 'message' => 'Venta no encontrada'];
        }

        $venta = $data['venta'];
        $items = $data['items'];

        $venta['id'] = (int)$venta['id'];
        $venta['total'] = (float)$venta['total'];
        $venta['monto_recibido'] = isset($venta['monto_recibido']) ? (float)$venta['monto_recibido'] : null;
        $venta['vuelto'] = isset($venta['vuelto']) ? (float)$venta['vuelto'] : 0.0;
        $venta['monto_efectivo'] = isset($venta['monto_efectivo']) ? (float)$venta['monto_efectivo'] : null;
        $venta['monto_digital'] = isset($venta['monto_digital']) ? (float)$venta['monto_digital'] : null;

        foreach ($items as &$item) {
            $item['producto_id'] = (int)$item['producto_id'];
            $item['cantidad'] = (int)$item['cantidad'];
            $item['precio_unitario'] = (float)$item['precio_unitario'];
            $item['producto_nombre'] = (string)($item['producto_nombre'] ?? ('Producto #' . $item['producto_id']));
        }
        unset($item);

        return [
            'ok' => true,
            'venta' => $venta,
            'items' => $items
        ];
    }

    public function obtenerUltimaVentaId(?int $usuarioId = null): array
    {
        $ventaId = $this->ventaModel->obtenerUltimaVentaId($usuarioId);
        if (!$ventaId) {
            return ['ok' => false, 'message' => 'No hay ventas disponibles para reimpresion'];
        }
        return ['ok' => true, 'venta_id' => $ventaId];
    }
}
