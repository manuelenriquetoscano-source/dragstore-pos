<?php
// Archivo: models/Venta.php

class Venta {
    private $conn;
    private $table_ventas = "ventas";
    private $table_detalle = "detalle_ventas";
    private $table_productos = "productos";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function registrarVenta($productos_vendidos, $total) {
        try {
            $this->conn->beginTransaction();

            // 1. Insertar la venta general (una sola vez)
            $query_v = "INSERT INTO " . $this->table_ventas . " (total) VALUES (:total)";
            $stmt_v = $this->conn->prepare($query_v);
            $stmt_v->execute([":total" => $total]);
            
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
}