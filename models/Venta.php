<?php
// Archivo: models/Venta.php

class Venta {
    private $conn;
    private $table_ventas = "ventas";
    private $table_detalle = "detalle_ventas";
    private $table_productos = "productos";
    private $supportsPaymentColumns = null;
    private $columnsCache = [];

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
        $query = "SELECT id, fecha, total FROM " . $this->table_ventas . " 
                  WHERE DATE(fecha) = :fecha ORDER BY fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        return $stmt;
    }
}
