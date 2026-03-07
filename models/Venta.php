<?php
// Archivo: models/Venta.php

class Venta {
    private $conn;
    private $table_ventas = "ventas";
    private $table_detalle = "detalle_ventas";
    private $table_productos = "productos";
    private $supportsPaymentColumns = null;
    private $columnsCache = [];
    private $tablesCache = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    private function hasPaymentColumns(): bool {
        if ($this->supportsPaymentColumns !== null) {
            return $this->supportsPaymentColumns;
        }

        try {
            $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_ventas . " LIKE 'metodo_pago'");
            $this->supportsPaymentColumns = $stmt && (bool)$stmt->fetchColumn();
        } catch (Exception $e) {
            $this->supportsPaymentColumns = false;
        }
        return $this->supportsPaymentColumns;
    }

    private function hasColumn(string $column): bool
    {
        if (array_key_exists($column, $this->columnsCache)) {
            return $this->columnsCache[$column];
        }

        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM " . $this->table_ventas . " LIKE :column");
            $stmt->execute([':column' => $column]);
            $this->columnsCache[$column] = $stmt && (bool)$stmt->fetchColumn();
        } catch (Exception $e) {
            $this->columnsCache[$column] = false;
        }

        return $this->columnsCache[$column];
    }

    private function hasTable(string $table): bool
    {
        if (array_key_exists($table, $this->tablesCache)) {
            return $this->tablesCache[$table];
        }

        try {
            $stmt = $this->conn->prepare("SHOW TABLES LIKE :table");
            $stmt->execute([':table' => $table]);
            $this->tablesCache[$table] = $stmt && (bool)$stmt->fetchColumn();
        } catch (Exception $e) {
            $this->tablesCache[$table] = false;
        }

        return $this->tablesCache[$table];
    }

    private function consumeStockByFefo(int $detalleVentaId, int $productoId, int $cantidad): void
    {
        if ($cantidad <= 0) {
            return;
        }

        if (!$this->hasTable('producto_lotes')) {
            return;
        }

        $trackConsumption = $this->hasTable('detalle_ventas_lotes');

        // Normaliza estado de lotes vencidos antes de consumir.
        $stmtExpire = $this->conn->prepare("UPDATE producto_lotes
                                            SET estado = 'vencido'
                                            WHERE producto_id = :producto_id
                                              AND estado = 'activo'
                                              AND cantidad_disponible > 0
                                              AND fecha_vencimiento < CURDATE()");
        $stmtExpire->execute([':producto_id' => $productoId]);

        $stmtLotes = $this->conn->prepare("SELECT id, cantidad_disponible
                                           FROM producto_lotes
                                           WHERE producto_id = :producto_id
                                             AND estado = 'activo'
                                             AND cantidad_disponible > 0
                                             AND fecha_vencimiento >= CURDATE()
                                           ORDER BY fecha_vencimiento ASC, id ASC
                                           FOR UPDATE");
        $stmtLotes->execute([':producto_id' => $productoId]);
        $lotes = $stmtLotes->fetchAll(PDO::FETCH_ASSOC);

        if (empty($lotes)) {
            return;
        }

        $cantidadPendiente = $cantidad;
        $stmtDescontar = $this->conn->prepare("UPDATE producto_lotes
                                               SET cantidad_disponible = cantidad_disponible - :cantidad_descuento,
                                                   estado = CASE WHEN (cantidad_disponible - :cantidad_estado) <= 0 THEN 'agotado' ELSE estado END
                                               WHERE id = :id
                                                 AND cantidad_disponible >= :cantidad_minima");
        $stmtTrack = null;
        if ($trackConsumption) {
            $stmtTrack = $this->conn->prepare("INSERT INTO detalle_ventas_lotes
                                               (detalle_venta_id, producto_id, lote_id, cantidad)
                                               VALUES (:detalle_venta_id, :producto_id, :lote_id, :cantidad)");
        }

        foreach ($lotes as $lote) {
            if ($cantidadPendiente <= 0) {
                break;
            }
            $loteId = (int)$lote['id'];
            $disponible = (int)$lote['cantidad_disponible'];
            if ($disponible <= 0) {
                continue;
            }

            $consumir = min($disponible, $cantidadPendiente);
            $stmtDescontar->execute([
                ':cantidad_descuento' => $consumir,
                ':cantidad_estado' => $consumir,
                ':id' => $loteId,
                ':cantidad_minima' => $consumir
            ]);

            if ($stmtDescontar->rowCount() === 0) {
                throw new Exception('No se pudo descontar lote FEFO para producto ID: ' . $productoId);
            }

            if ($stmtTrack) {
                $stmtTrack->execute([
                    ':detalle_venta_id' => $detalleVentaId,
                    ':producto_id' => $productoId,
                    ':lote_id' => $loteId,
                    ':cantidad' => $consumir
                ]);
            }

            $cantidadPendiente -= $consumir;
        }

        if ($cantidadPendiente > 0) {
            throw new Exception('Stock de lotes insuficiente (FEFO) para producto ID: ' . $productoId);
        }
    }

    public function registrarVenta($productos_vendidos, $total, array $pago = [], array $contexto = []) {
        try {
            $this->conn->beginTransaction();

            // 1. Insertar la venta general (una sola vez)
            $columns = ['total'];
            $params = [':total' => $total];

            if ($this->hasColumn('usuario_id') && array_key_exists('usuario_id', $contexto)) {
                $columns[] = 'usuario_id';
                $params[':usuario_id'] = $contexto['usuario_id'];
            }
            if ($this->hasColumn('turno_id') && array_key_exists('turno_id', $contexto)) {
                $columns[] = 'turno_id';
                $params[':turno_id'] = $contexto['turno_id'];
            }

            if ($this->hasPaymentColumns()) {
                $columns[] = 'metodo_pago';
                $columns[] = 'monto_recibido';
                $columns[] = 'vuelto';
                $columns[] = 'monto_efectivo';
                $columns[] = 'monto_digital';
                $params[':metodo_pago'] = $pago['metodo_pago'] ?? 'efectivo';
                $params[':monto_recibido'] = array_key_exists('monto_recibido', $pago) ? $pago['monto_recibido'] : null;
                $params[':vuelto'] = $pago['vuelto'] ?? 0;
                $params[':monto_efectivo'] = array_key_exists('monto_efectivo', $pago) ? $pago['monto_efectivo'] : null;
                $params[':monto_digital'] = array_key_exists('monto_digital', $pago) ? $pago['monto_digital'] : null;
            }

            $placeholders = array_map(function ($col) {
                return ':' . $col;
            }, $columns);

            $query_v = "INSERT INTO " . $this->table_ventas .
                       " (" . implode(', ', $columns) . ")
                        VALUES (" . implode(', ', $placeholders) . ")";
            $stmt_v = $this->conn->prepare($query_v);
            $stmt_v->execute($params);
            
            $venta_id = $this->conn->lastInsertId();

            // 2. OPTIMIZACIÓN: Preparamos las sentencias FUERA del bucle
            $query_d = "INSERT INTO " . $this->table_detalle . " 
                       (venta_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
            $stmt_d = $this->conn->prepare($query_d);

            $query_s = "UPDATE " . $this->table_productos . " 
                       SET stock = stock - ? WHERE id = ? AND stock >= ?";
            $stmt_s = $this->conn->prepare($query_s);

            // 3. Ejecutar dentro del bucle (ahora es mucho más rápido)
            foreach ($productos_vendidos as $item) {
                // Insertar detalle
                $stmt_d->execute([$venta_id, $item['id'], $item['cantidad'], $item['precio']]);
                $detalleVentaId = (int)$this->conn->lastInsertId();

                // Consumir lotes por FEFO (si aplica)
                $this->consumeStockByFefo($detalleVentaId, (int)$item['id'], (int)$item['cantidad']);

                // Descontar stock (y verificar que no sea insuficiente)
                $stmt_s->execute([$item['cantidad'], $item['id'], $item['cantidad']]);
                
                if ($stmt_s->rowCount() == 0) {
                    throw new Exception("Stock insuficiente para el producto ID: " . $item['id']);
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            // Opcional: podrías loguear $e->getMessage() para debug
            return false;
        }
    }

    // El método que te sugerí antes para los reportes
    public function listarVentasDia() {
        $query = "SELECT id, fecha, total FROM " . $this->table_ventas . " 
                  WHERE DATE(fecha) = CURDATE() ORDER BY fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function listarVentasPorFecha($fecha) {
        if ($this->hasColumn('estado') && $this->hasColumn('motivo_anulacion') && $this->hasColumn('anulada_at')) {
            $query = "SELECT id, fecha, total, estado, motivo_anulacion, anulada_at FROM " . $this->table_ventas . " 
                      WHERE DATE(fecha) = :fecha ORDER BY fecha DESC";
        } else {
            $query = "SELECT id, fecha, total, 'completada' AS estado, NULL AS motivo_anulacion, NULL AS anulada_at
                      FROM " . $this->table_ventas . " 
                      WHERE DATE(fecha) = :fecha ORDER BY fecha DESC";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        return $stmt;
    }

    private function obtenerVentaPorId(int $ventaId): ?array
    {
        if ($this->hasColumn('estado')) {
            $query = "SELECT id, total, estado FROM " . $this->table_ventas . " WHERE id = :id LIMIT 1";
        } else {
            $query = "SELECT id, total, 'completada' AS estado FROM " . $this->table_ventas . " WHERE id = :id LIMIT 1";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $ventaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function listarDetallesVenta(int $ventaId): array
    {
        $query = "SELECT producto_id, cantidad FROM " . $this->table_detalle . " WHERE venta_id = :venta_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':venta_id' => $ventaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function listarDetalleLotesPorVenta(int $ventaId): array
    {
        if (!$this->hasTable('detalle_ventas_lotes')) {
            return [];
        }

        $query = "SELECT dvl.lote_id, dvl.cantidad
                  FROM detalle_ventas_lotes dvl
                  INNER JOIN detalle_ventas dv ON dv.id = dvl.detalle_venta_id
                  WHERE dv.venta_id = :venta_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':venta_id' => $ventaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function anularVenta(int $ventaId, string $motivo, ?int $anuladaPorUserId = null): bool
    {
        if (!$this->hasColumn('estado')) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $venta = $this->obtenerVentaPorId($ventaId);
            if (!$venta) {
                throw new Exception('Venta no encontrada');
            }
            if (($venta['estado'] ?? 'completada') === 'anulada') {
                throw new Exception('La venta ya fue anulada');
            }

            $detalles = $this->listarDetallesVenta($ventaId);
            if (empty($detalles)) {
                throw new Exception('No se encontraron detalles para la venta');
            }

            $queryStock = "UPDATE " . $this->table_productos . " SET stock = stock + :cantidad WHERE id = :producto_id";
            $stmtStock = $this->conn->prepare($queryStock);
            foreach ($detalles as $d) {
                $stmtStock->execute([
                    ':cantidad' => (int)$d['cantidad'],
                    ':producto_id' => (int)$d['producto_id']
                ]);
            }

            if ($this->hasTable('producto_lotes') && $this->hasTable('detalle_ventas_lotes')) {
                $detalleLotes = $this->listarDetalleLotesPorVenta($ventaId);
                if (!empty($detalleLotes)) {
                    $stmtRestoreLote = $this->conn->prepare("UPDATE producto_lotes
                                                             SET cantidad_disponible = cantidad_disponible + :cantidad_suma,
                                                                 estado = CASE
                                                                    WHEN fecha_vencimiento < CURDATE() THEN 'vencido'
                                                                    WHEN (cantidad_disponible + :cantidad_estado) > 0 THEN 'activo'
                                                                    ELSE estado
                                                                 END
                                                             WHERE id = :lote_id");
                    foreach ($detalleLotes as $dl) {
                        $stmtRestoreLote->execute([
                            ':cantidad_suma' => (int)$dl['cantidad'],
                            ':cantidad_estado' => (int)$dl['cantidad'],
                            ':lote_id' => (int)$dl['lote_id']
                        ]);
                    }
                }
            }

            if ($this->hasColumn('anulada_por_user_id') && $anuladaPorUserId !== null) {
                $queryVenta = "UPDATE " . $this->table_ventas . "
                              SET estado = 'anulada',
                                  motivo_anulacion = :motivo_anulacion,
                                  anulada_at = CURRENT_TIMESTAMP,
                                  anulada_por_user_id = :anulada_por_user_id
                              WHERE id = :id AND estado <> 'anulada'";
                $stmtVenta = $this->conn->prepare($queryVenta);
                $stmtVenta->execute([
                    ':motivo_anulacion' => $motivo,
                    ':anulada_por_user_id' => $anuladaPorUserId,
                    ':id' => $ventaId
                ]);
            } else {
                $queryVenta = "UPDATE " . $this->table_ventas . "
                              SET estado = 'anulada',
                                  motivo_anulacion = :motivo_anulacion,
                                  anulada_at = CURRENT_TIMESTAMP
                              WHERE id = :id AND estado <> 'anulada'";
                $stmtVenta = $this->conn->prepare($queryVenta);
                $stmtVenta->execute([
                    ':motivo_anulacion' => $motivo,
                    ':id' => $ventaId
                ]);
            }

            if ($stmtVenta->rowCount() === 0) {
                throw new Exception('No se pudo anular la venta');
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    public function obtenerVentaParaTicket(int $ventaId): ?array
    {
        $baseColumns = "id, fecha, total";
        if ($this->hasColumn('estado') && $this->hasColumn('motivo_anulacion')) {
            $baseColumns .= ", estado, motivo_anulacion";
        } else {
            $baseColumns .= ", 'completada' AS estado, NULL AS motivo_anulacion";
        }
        if ($this->hasColumn('metodo_pago')) {
            $baseColumns .= ", metodo_pago, monto_recibido, vuelto, monto_efectivo, monto_digital";
        } else {
            $baseColumns .= ", 'efectivo' AS metodo_pago, NULL AS monto_recibido, 0 AS vuelto, NULL AS monto_efectivo, NULL AS monto_digital";
        }

        $queryVenta = "SELECT {$baseColumns}
                       FROM " . $this->table_ventas . "
                       WHERE id = :id
                       LIMIT 1";
        $stmtVenta = $this->conn->prepare($queryVenta);
        $stmtVenta->execute([':id' => $ventaId]);
        $venta = $stmtVenta->fetch(PDO::FETCH_ASSOC);
        if (!$venta) {
            return null;
        }

        $queryItems = "SELECT d.producto_id, d.cantidad, d.precio_unitario,
                              p.nombre AS producto_nombre
                       FROM " . $this->table_detalle . " d
                       LEFT JOIN " . $this->table_productos . " p ON p.id = d.producto_id
                       WHERE d.venta_id = :venta_id
                       ORDER BY d.id ASC";
        $stmtItems = $this->conn->prepare($queryItems);
        $stmtItems->execute([':venta_id' => $ventaId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        return [
            'venta' => $venta,
            'items' => $items
        ];
    }

    public function obtenerUltimaVentaId(?int $usuarioId = null): ?int
    {
        $query = "SELECT id FROM " . $this->table_ventas . " WHERE 1=1";
        $params = [];

        if ($this->hasColumn('estado')) {
            $query .= " AND estado <> 'anulada'";
        }

        if ($usuarioId !== null && $usuarioId > 0 && $this->hasColumn('usuario_id')) {
            $query .= " AND usuario_id = :usuario_id";
            $params[':usuario_id'] = $usuarioId;
        }

        $query .= " ORDER BY id DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !isset($row['id'])) {
            return null;
        }
        return (int)$row['id'];
    }
}
