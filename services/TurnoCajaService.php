<?php

require_once __DIR__ . '/../models/TurnoCaja.php';

class TurnoCajaService
{
    private $turnoModel;

    public function __construct(PDO $db)
    {
        $this->turnoModel = new TurnoCaja($db);
    }

    public function estadoActual(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return ['ok' => false, 'message' => 'Usuario invalido'];
        }

        $turno = $this->turnoModel->obtenerAbiertoPorUsuario($usuarioId);
        $resumen = null;
        if ($turno !== null) {
            $resumen = $this->turnoModel->resumenVentasPorTurno((int)$turno['id']);
            $resumen['monto_inicial'] = (float)($turno['monto_inicial'] ?? 0);
            $resumen['esperado_caja'] = round($resumen['monto_inicial'] + (float)$resumen['total_efectivo'], 2);
        }
        return [
            'ok' => true,
            'abierto' => $turno !== null,
            'turno' => $turno,
            'resumen' => $resumen
        ];
    }

    public function abrir(int $usuarioId, float $montoInicial, ?string $observaciones = null): array
    {
        if ($usuarioId <= 0) {
            return ['ok' => false, 'message' => 'Usuario invalido'];
        }
        if ($montoInicial < 0) {
            return ['ok' => false, 'message' => 'El monto inicial no puede ser negativo'];
        }

        return $this->turnoModel->abrir($usuarioId, round($montoInicial, 2), $observaciones);
    }

    public function cerrar(int $usuarioId, float $montoFinalDeclarado): array
    {
        if ($usuarioId <= 0) {
            return ['ok' => false, 'message' => 'Usuario invalido'];
        }
        if ($montoFinalDeclarado < 0) {
            return ['ok' => false, 'message' => 'El monto final declarado no puede ser negativo'];
        }

        $turno = $this->turnoModel->obtenerAbiertoPorUsuario($usuarioId);
        if (!$turno) {
            return ['ok' => false, 'message' => 'No hay turno abierto para cerrar'];
        }

        return $this->turnoModel->cerrar((int)$turno['id'], round($montoFinalDeclarado, 2));
    }

    public function listarUltimos(int $usuarioId, int $limit = 10): array
    {
        if ($usuarioId <= 0) {
            return ['ok' => false, 'message' => 'Usuario invalido', 'items' => []];
        }

        return [
            'ok' => true,
            'items' => $this->turnoModel->listarUltimosPorUsuario($usuarioId, $limit)
        ];
    }

    public function listarPaginado(array $filters, bool $adminMode, int $usuarioActualId, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $normalized = [
            'date_from' => isset($filters['date_from']) ? trim((string)$filters['date_from']) : '',
            'date_to' => isset($filters['date_to']) ? trim((string)$filters['date_to']) : '',
            'estado' => isset($filters['estado']) ? trim((string)$filters['estado']) : '',
            'usuario_id' => isset($filters['usuario_id']) ? trim((string)$filters['usuario_id']) : ''
        ];

        if ($normalized['date_from'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized['date_from'])) {
            $normalized['date_from'] = '';
        }
        if ($normalized['date_to'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized['date_to'])) {
            $normalized['date_to'] = '';
        }
        if (!in_array($normalized['estado'], ['', 'abierto', 'cerrado'], true)) {
            $normalized['estado'] = '';
        }

        if ($adminMode) {
            if ($normalized['usuario_id'] !== '' && ctype_digit($normalized['usuario_id'])) {
                $normalized['usuario_id'] = (string)((int)$normalized['usuario_id']);
            } else {
                $normalized['usuario_id'] = '';
            }
        } else {
            $normalized['usuario_id'] = (string)$usuarioActualId;
        }

        $total = $this->turnoModel->contarConFiltros($normalized, $adminMode);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $items = $this->turnoModel->listarConFiltros($normalized, $adminMode, $perPage, $offset);

        return [
            'ok' => true,
            'items' => $items,
            'filters' => $normalized,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages
        ];
    }

    public function exportarCsv(array $filters, bool $adminMode, int $usuarioActualId, int $limit = 5000): array
    {
        $normalized = [
            'date_from' => isset($filters['date_from']) ? trim((string)$filters['date_from']) : '',
            'date_to' => isset($filters['date_to']) ? trim((string)$filters['date_to']) : '',
            'estado' => isset($filters['estado']) ? trim((string)$filters['estado']) : '',
            'usuario_id' => isset($filters['usuario_id']) ? trim((string)$filters['usuario_id']) : ''
        ];

        if ($normalized['date_from'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized['date_from'])) {
            $normalized['date_from'] = '';
        }
        if ($normalized['date_to'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized['date_to'])) {
            $normalized['date_to'] = '';
        }
        if (!in_array($normalized['estado'], ['', 'abierto', 'cerrado'], true)) {
            $normalized['estado'] = '';
        }

        if ($adminMode) {
            if ($normalized['usuario_id'] !== '' && ctype_digit($normalized['usuario_id'])) {
                $normalized['usuario_id'] = (string)((int)$normalized['usuario_id']);
            } else {
                $normalized['usuario_id'] = '';
            }
        } else {
            $normalized['usuario_id'] = (string)$usuarioActualId;
        }

        return [
            'ok' => true,
            'items' => $this->turnoModel->listarConFiltros($normalized, $adminMode, max(1, min(5000, $limit)), 0),
            'filters' => $normalized
        ];
    }
}
