<?php

class TurnoCaja
{
    private $conn;
    private $table = 'caja_turnos';
    private $ventasColumnsCache = [];

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerAbiertoPorUsuario(int $usuarioId): ?array
    {
        $query = "SELECT id, usuario_id, opened_at, closed_at, monto_inicial, monto_final_declarado,
                         total_ventas, cantidad_ventas, diferencia, estado, observaciones
                  FROM {$this->table}
                  WHERE usuario_id = :usuario_id AND estado = 'abierto'
                  ORDER BY id DESC
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':usuario_id' => $usuarioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function abrir(int $usuarioId, float $montoInicial, ?string $observaciones = null): array
    {
        $existente = $this->obtenerAbiertoPorUsuario($usuarioId);
        if ($existente) {
            return ['ok' => false, 'message' => 'Ya existe un turno abierto para este usuario'];
        }

        $query = "INSERT INTO {$this->table} (usuario_id, monto_inicial, observaciones)
                  VALUES (:usuario_id, :monto_inicial, :observaciones)";
        $stmt = $this->conn->prepare($query);
        $ok = $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':monto_inicial' => $montoInicial,
            ':observaciones' => $observaciones
        ]);
        if (!$ok) {
            return ['ok' => false, 'message' => 'No se pudo abrir el turno'];
        }

        $id = (int)$this->conn->lastInsertId();
        return ['ok' => true, 'message' => 'Turno abierto', 'id' => $id];
    }

    public function cerrar(int $turnoId, float $montoFinalDeclarado): array
    {
        $turno = $this->obtenerPorId($turnoId);
        if (!$turno) {
            return ['ok' => false, 'message' => 'Turno no encontrado'];
        }
        if (($turno['estado'] ?? '') !== 'abierto') {
            return ['ok' => false, 'message' => 'El turno ya esta cerrado'];
        }

        $resumen = $this->resumenVentasPorTurno($turnoId);
        $totalVentas = (float)$resumen['total_ventas'];
        $totalEfectivo = (float)$resumen['total_efectivo'];
        $cantidadVentas = (int)$resumen['cantidad_ventas'];
        $montoInicial = (float)$turno['monto_inicial'];
        $esperadoCaja = $montoInicial + $totalEfectivo;
        $diferencia = $montoFinalDeclarado - $esperadoCaja;

        $query = "UPDATE {$this->table}
                  SET closed_at = CURRENT_TIMESTAMP,
                      monto_final_declarado = :monto_final_declarado,
                      total_ventas = :total_ventas,
                      cantidad_ventas = :cantidad_ventas,
                      diferencia = :diferencia,
                      estado = 'cerrado'
                  WHERE id = :id AND estado = 'abierto'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':monto_final_declarado' => $montoFinalDeclarado,
            ':total_ventas' => $totalVentas,
            ':cantidad_ventas' => $cantidadVentas,
            ':diferencia' => $diferencia,
            ':id' => $turnoId
        ]);

        if ($stmt->rowCount() === 0) {
            return ['ok' => false, 'message' => 'No se pudo cerrar el turno'];
        }

        return [
            'ok' => true,
            'message' => 'Turno cerrado',
            'resumen' => [
                'turno_id' => $turnoId,
                'monto_inicial' => round($montoInicial, 2),
                'monto_final_declarado' => round($montoFinalDeclarado, 2),
                'total_ventas' => round($totalVentas, 2),
                'total_efectivo' => round($totalEfectivo, 2),
                'esperado_caja' => round($esperadoCaja, 2),
                'cantidad_ventas' => $cantidadVentas,
                'diferencia' => round($diferencia, 2)
            ]
        ];
    }

    private function ventasHasColumn(string $column): bool
    {
        if (array_key_exists($column, $this->ventasColumnsCache)) {
            return $this->ventasColumnsCache[$column];
        }

        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM ventas LIKE :column");
            $stmt->execute([':column' => $column]);
            $this->ventasColumnsCache[$column] = $stmt && (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            $this->ventasColumnsCache[$column] = false;
        }

        return $this->ventasColumnsCache[$column];
    }

    public function resumenVentasPorTurno(int $turnoId): array
    {
        if ($this->ventasHasColumn('monto_efectivo') && $this->ventasHasColumn('metodo_pago')) {
            $query = "SELECT
                        COALESCE(SUM(total), 0) AS total_ventas,
                        COALESCE(SUM(
                            CASE
                                WHEN monto_efectivo IS NOT NULL THEN monto_efectivo
                                WHEN metodo_pago IN ('tarjeta', 'transferencia') THEN 0
                                ELSE total
                            END
                        ), 0) AS total_efectivo,
                        COUNT(*) AS cantidad_ventas
                      FROM ventas
                      WHERE turno_id = :turno_id";
        } else {
            $query = "SELECT
                        COALESCE(SUM(total), 0) AS total_ventas,
                        COALESCE(SUM(total), 0) AS total_efectivo,
                        COUNT(*) AS cantidad_ventas
                      FROM ventas
                      WHERE turno_id = :turno_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':turno_id' => $turnoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_ventas' => 0, 'total_efectivo' => 0, 'cantidad_ventas' => 0];
        return [
            'total_ventas' => (float)$row['total_ventas'],
            'total_efectivo' => (float)$row['total_efectivo'],
            'cantidad_ventas' => (int)$row['cantidad_ventas']
        ];
    }

    public function obtenerPorId(int $id): ?array
    {
        $query = "SELECT id, usuario_id, opened_at, closed_at, monto_inicial, monto_final_declarado,
                         total_ventas, cantidad_ventas, diferencia, estado, observaciones
                  FROM {$this->table}
                  WHERE id = :id
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listarUltimosPorUsuario(int $usuarioId, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $query = "SELECT id, usuario_id, opened_at, closed_at, monto_inicial, monto_final_declarado,
                         total_ventas, cantidad_ventas, diferencia, estado, observaciones
                  FROM {$this->table}
                  WHERE usuario_id = :usuario_id
                  ORDER BY id DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildFilter(array $filters, bool $adminMode): array
    {
        $where = [];
        $params = [];

        if (!$adminMode && isset($filters['usuario_id'])) {
            $where[] = "t.usuario_id = :usuario_scope";
            $params[':usuario_scope'] = (int)$filters['usuario_id'];
            return [$where, $params];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(t.opened_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(t.opened_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['estado'])) {
            $where[] = "t.estado = :estado";
            $params[':estado'] = $filters['estado'];
        }
        if (!empty($filters['usuario_id'])) {
            $where[] = "t.usuario_id = :usuario_id";
            $params[':usuario_id'] = (int)$filters['usuario_id'];
        }

        return [$where, $params];
    }

    public function listarConFiltros(array $filters, bool $adminMode, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildFilter($filters, $adminMode);
        $query = "SELECT t.id, t.usuario_id, t.opened_at, t.closed_at, t.monto_inicial, t.monto_final_declarado,
                         t.total_ventas, t.cantidad_ventas, t.diferencia, t.estado, t.observaciones,
                         u.username, u.display_name
                  FROM {$this->table} t
                  INNER JOIN usuarios u ON u.id = t.usuario_id";
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        $query .= " ORDER BY t.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', max(1, min(5000, $limit)), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarConFiltros(array $filters, bool $adminMode): int
    {
        [$where, $params] = $this->buildFilter($filters, $adminMode);
        $query = "SELECT COUNT(*) AS total FROM {$this->table} t";
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        $stmt = $this->conn->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function obtenerDetalleActa(int $turnoId): ?array
    {
        $query = "SELECT t.id, t.usuario_id, t.opened_at, t.closed_at, t.monto_inicial, t.monto_final_declarado,
                         t.total_ventas, t.cantidad_ventas, t.diferencia, t.estado, t.observaciones,
                         u.username, u.display_name
                  FROM {$this->table} t
                  INNER JOIN usuarios u ON u.id = t.usuario_id
                  WHERE t.id = :id
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $turnoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function resumenPagosPorTurno(int $turnoId): array
    {
        $hasMetodo = $this->ventasHasColumn('metodo_pago');
        $hasEfectivo = $this->ventasHasColumn('monto_efectivo');
        $hasDigital = $this->ventasHasColumn('monto_digital');

        if ($hasMetodo && $hasEfectivo && $hasDigital) {
            $query = "SELECT
                        COALESCE(SUM(total), 0) AS total_ventas,
                        COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END), 0) AS total_efectivo_puro,
                        COALESCE(SUM(CASE WHEN metodo_pago = 'tarjeta' THEN total ELSE 0 END), 0) AS total_tarjeta,
                        COALESCE(SUM(CASE WHEN metodo_pago = 'transferencia' THEN total ELSE 0 END), 0) AS total_transferencia,
                        COALESCE(SUM(CASE WHEN metodo_pago = 'mixto' THEN total ELSE 0 END), 0) AS total_mixto,
                        COALESCE(SUM(
                            CASE
                                WHEN monto_efectivo IS NOT NULL THEN monto_efectivo
                                WHEN metodo_pago IN ('tarjeta', 'transferencia') THEN 0
                                ELSE total
                            END
                        ), 0) AS total_efectivo_real,
                        COALESCE(SUM(
                            CASE
                                WHEN monto_digital IS NOT NULL THEN monto_digital
                                WHEN metodo_pago IN ('tarjeta', 'transferencia') THEN total
                                ELSE 0
                            END
                        ), 0) AS total_digital_real
                      FROM ventas
                      WHERE turno_id = :turno_id";
        } else {
            $query = "SELECT
                        COALESCE(SUM(total), 0) AS total_ventas,
                        COALESCE(SUM(total), 0) AS total_efectivo_puro,
                        0 AS total_tarjeta,
                        0 AS total_transferencia,
                        0 AS total_mixto,
                        COALESCE(SUM(total), 0) AS total_efectivo_real,
                        0 AS total_digital_real
                      FROM ventas
                      WHERE turno_id = :turno_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':turno_id' => $turnoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_ventas' => (float)($row['total_ventas'] ?? 0),
            'total_efectivo_puro' => (float)($row['total_efectivo_puro'] ?? 0),
            'total_tarjeta' => (float)($row['total_tarjeta'] ?? 0),
            'total_transferencia' => (float)($row['total_transferencia'] ?? 0),
            'total_mixto' => (float)($row['total_mixto'] ?? 0),
            'total_efectivo_real' => (float)($row['total_efectivo_real'] ?? 0),
            'total_digital_real' => (float)($row['total_digital_real'] ?? 0)
        ];
    }
}
