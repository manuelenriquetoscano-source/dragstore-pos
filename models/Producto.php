<?php

class Producto
{
    private $conn;
    private $table_name = 'productos';
    private $schemaCache = [];

    public $id;
    public $codigo_barras;
    public $nombre;
    public $precio;
    public $stock;
    public $stock_minimo = 5;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    private function hasTable(string $table): bool
    {
        $key = 'table:' . $table;
        if (array_key_exists($key, $this->schemaCache)) {
            return $this->schemaCache[$key];
        }

        try {
            $stmt = $this->conn->prepare('SHOW TABLES LIKE :table');
            $stmt->execute([':table' => $table]);
            $this->schemaCache[$key] = $stmt && (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            $this->schemaCache[$key] = false;
        }

        return $this->schemaCache[$key];
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = 'col:' . $table . '.' . $column;
        if (array_key_exists($key, $this->schemaCache)) {
            return $this->schemaCache[$key];
        }

        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM {$table} LIKE :column");
            $stmt->execute([':column' => $column]);
            $this->schemaCache[$key] = $stmt && (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            $this->schemaCache[$key] = false;
        }

        return $this->schemaCache[$key];
    }

    public function leerTodo()
    {
        $hasStockMinimo = $this->hasColumn($this->table_name, 'stock_minimo');
        $stockMinExpr = $hasStockMinimo ? 'stock_minimo' : '5';
        $query = "SELECT id, codigo_barras, nombre, precio, stock, {$stockMinExpr} AS stock_minimo
                  FROM {$this->table_name}
                  ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerBajoStock($minimo = 5)
    {
        if ($this->hasColumn($this->table_name, 'stock_minimo')) {
            $query = "SELECT id, codigo_barras, nombre, precio, stock, stock_minimo
                      FROM {$this->table_name}
                      WHERE stock <= stock_minimo
                      ORDER BY stock ASC, nombre ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        }

        $query = "SELECT id, codigo_barras, nombre, precio, stock, :minimo AS stock_minimo
                  FROM {$this->table_name}
                  WHERE stock <= :minimo
                  ORDER BY stock ASC, nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':minimo', (int)$minimo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function leerTodoConAlertas(int $diasProximos = 30)
    {
        $hasStockMinimo = $this->hasColumn($this->table_name, 'stock_minimo');
        $hasLotes = $this->hasTable('producto_lotes');

        $stockMinExpr = $hasStockMinimo ? 'p.stock_minimo' : '5';
        if ($hasLotes) {
            $query = "SELECT
                        p.id,
                        p.codigo_barras,
                        p.nombre,
                        p.precio,
                        p.stock,
                        {$stockMinExpr} AS stock_minimo,
                        COALESCE(SUM(CASE WHEN l.estado = 'activo' AND l.cantidad_disponible > 0 THEN 1 ELSE 0 END), 0) AS lotes_activos,
                        MIN(CASE WHEN l.estado = 'activo' AND l.cantidad_disponible > 0 THEN l.fecha_vencimiento ELSE NULL END) AS proximo_vencimiento,
                        COALESCE(SUM(CASE WHEN l.estado = 'activo' AND l.cantidad_disponible > 0 AND l.fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END), 0) AS lotes_vencidos,
                        COALESCE(SUM(CASE WHEN l.estado = 'activo' AND l.cantidad_disponible > 0 AND l.fecha_vencimiento >= CURDATE() AND l.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL :dias DAY) THEN 1 ELSE 0 END), 0) AS lotes_por_vencer
                      FROM {$this->table_name} p
                      LEFT JOIN producto_lotes l ON l.producto_id = p.id
                      GROUP BY p.id, p.codigo_barras, p.nombre, p.precio, p.stock, {$stockMinExpr}
                      ORDER BY p.nombre ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':dias', max(1, $diasProximos), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        }

        $query = "SELECT
                    p.id,
                    p.codigo_barras,
                    p.nombre,
                    p.precio,
                    p.stock,
                    {$stockMinExpr} AS stock_minimo,
                    0 AS lotes_activos,
                    NULL AS proximo_vencimiento,
                    0 AS lotes_vencidos,
                    0 AS lotes_por_vencer
                  FROM {$this->table_name} p
                  ORDER BY p.nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerBajoStockConAlertas(int $minimoDefault = 5, int $diasProximos = 30)
    {
        $hasStockMinimo = $this->hasColumn($this->table_name, 'stock_minimo');
        $hasLotes = $this->hasTable('producto_lotes');
        $stockMinExpr = $hasStockMinimo ? 'p.stock_minimo' : ':minimo_default';

        if ($hasLotes) {
            $query = "SELECT
                        p.id,
                        p.codigo_barras,
                        p.nombre,
                        p.precio,
                        p.stock,
                        {$stockMinExpr} AS stock_minimo,
                        COALESCE(SUM(CASE WHEN l.estado = 'activo' AND l.cantidad_disponible > 0 THEN 1 ELSE 0 END), 0) AS lotes_activos,
                        MIN(CASE WHEN l.estado = 'activo' AND l.cantidad_disponible > 0 THEN l.fecha_vencimiento ELSE NULL END) AS proximo_vencimiento,
                        COALESCE(SUM(CASE WHEN l.estado = 'activo' AND l.cantidad_disponible > 0 AND l.fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END), 0) AS lotes_vencidos,
                        COALESCE(SUM(CASE WHEN l.estado = 'activo' AND l.cantidad_disponible > 0 AND l.fecha_vencimiento >= CURDATE() AND l.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL :dias DAY) THEN 1 ELSE 0 END), 0) AS lotes_por_vencer
                      FROM {$this->table_name} p
                      LEFT JOIN producto_lotes l ON l.producto_id = p.id
                      GROUP BY p.id, p.codigo_barras, p.nombre, p.precio, p.stock, {$stockMinExpr}
                      HAVING p.stock <= {$stockMinExpr}
                      ORDER BY p.stock ASC, p.nombre ASC";
            $stmt = $this->conn->prepare($query);
            if (!$hasStockMinimo) {
                $stmt->bindValue(':minimo_default', max(1, $minimoDefault), PDO::PARAM_INT);
            }
            $stmt->bindValue(':dias', max(1, $diasProximos), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        }

        $query = "SELECT
                    p.id,
                    p.codigo_barras,
                    p.nombre,
                    p.precio,
                    p.stock,
                    {$stockMinExpr} AS stock_minimo,
                    0 AS lotes_activos,
                    NULL AS proximo_vencimiento,
                    0 AS lotes_vencidos,
                    0 AS lotes_por_vencer
                  FROM {$this->table_name} p
                  WHERE p.stock <= {$stockMinExpr}
                  ORDER BY p.stock ASC, p.nombre ASC";
        $stmt = $this->conn->prepare($query);
        if (!$hasStockMinimo) {
            $stmt->bindValue(':minimo_default', max(1, $minimoDefault), PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt;
    }

    public function crear()
    {
        $hasStockMinimo = $this->hasColumn($this->table_name, 'stock_minimo');
        if ($hasStockMinimo) {
            $query = "INSERT INTO {$this->table_name}
                      SET codigo_barras=:codigo, nombre=:nombre, precio=:precio, stock=:stock, stock_minimo=:stock_minimo";
        } else {
            $query = "INSERT INTO {$this->table_name}
                      SET codigo_barras=:codigo, nombre=:nombre, precio=:precio, stock=:stock";
        }

        $stmt = $this->conn->prepare($query);

        $this->codigo_barras = htmlspecialchars(strip_tags((string)$this->codigo_barras));
        $this->nombre = htmlspecialchars(strip_tags((string)$this->nombre));

        $stmt->bindValue(':codigo', $this->codigo_barras);
        $stmt->bindValue(':nombre', $this->nombre);
        $stmt->bindValue(':precio', (float)$this->precio);
        $stmt->bindValue(':stock', (int)$this->stock, PDO::PARAM_INT);
        if ($hasStockMinimo) {
            $stmt->bindValue(':stock_minimo', max(1, (int)$this->stock_minimo), PDO::PARAM_INT);
        }

        if (!$stmt->execute()) {
            return false;
        }

        return (int)$this->conn->lastInsertId();
    }

    public function crearLote(int $productoId, string $numeroLote, string $fechaVencimiento, int $cantidad, ?float $costoUnitario = null): bool
    {
        if (!$this->hasTable('producto_lotes')) {
            return false;
        }

        $query = "INSERT INTO producto_lotes
                  (producto_id, numero_lote, fecha_vencimiento, cantidad_inicial, cantidad_disponible, costo_unitario, estado)
                  VALUES
                  (:producto_id, :numero_lote, :fecha_vencimiento, :cantidad_inicial, :cantidad_disponible, :costo_unitario, 'activo')";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':producto_id' => $productoId,
            ':numero_lote' => trim($numeroLote),
            ':fecha_vencimiento' => $fechaVencimiento,
            ':cantidad_inicial' => max(0, $cantidad),
            ':cantidad_disponible' => max(0, $cantidad),
            ':costo_unitario' => $costoUnitario
        ]);
    }

    public function agregarStock(int $productoId, int $cantidad): bool
    {
        if ($cantidad < 0) {
            return false;
        }
        $query = "UPDATE {$this->table_name} SET stock = stock + :cantidad WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':cantidad' => $cantidad,
            ':id' => $productoId
        ]);
    }

    public function obtenerPorId(int $id): ?array
    {
        $hasStockMinimo = $this->hasColumn($this->table_name, 'stock_minimo');
        $stockMinExpr = $hasStockMinimo ? 'stock_minimo' : '5';
        $query = "SELECT id, codigo_barras, nombre, precio, stock, {$stockMinExpr} AS stock_minimo
                  FROM {$this->table_name}
                  WHERE id = :id
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listarLotesPorProducto(int $productoId): array
    {
        if (!$this->hasTable('producto_lotes')) {
            return [];
        }

        $query = "SELECT
                    id,
                    producto_id,
                    numero_lote,
                    fecha_vencimiento,
                    cantidad_inicial,
                    cantidad_disponible,
                    costo_unitario,
                    estado,
                    created_at,
                    updated_at
                  FROM producto_lotes
                  WHERE producto_id = :producto_id
                  ORDER BY fecha_vencimiento ASC, id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':producto_id' => $productoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminar()
    {
        $query = "DELETE FROM {$this->table_name} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', (int)$this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function buscar($termino)
    {
        $hasStockMinimo = $this->hasColumn($this->table_name, 'stock_minimo');
        $hasLotes = $this->hasTable('producto_lotes');
        $stockMinExpr = $hasStockMinimo ? 'stock_minimo' : '5';
        if ($hasLotes) {
            $query = "SELECT
                        p.id,
                        p.codigo_barras,
                        p.nombre,
                        p.precio,
                        p.stock,
                        {$stockMinExpr} AS stock_minimo,
                        MIN(CASE WHEN l.estado = 'activo' AND l.cantidad_disponible > 0 AND l.fecha_vencimiento >= CURDATE() THEN l.fecha_vencimiento ELSE NULL END) AS fefo_proximo_vencimiento,
                        COALESCE(SUM(CASE WHEN l.estado = 'activo' AND l.cantidad_disponible > 0 AND l.fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END), 0) AS lotes_vencidos,
                        COALESCE(SUM(CASE WHEN l.estado = 'activo' AND l.cantidad_disponible > 0 AND l.fecha_vencimiento >= CURDATE() AND l.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) AS lotes_por_vencer
                      FROM {$this->table_name} p
                      LEFT JOIN producto_lotes l ON l.producto_id = p.id
                      WHERE p.codigo_barras LIKE ? OR p.nombre LIKE ?
                      GROUP BY p.id, p.codigo_barras, p.nombre, p.precio, p.stock, {$stockMinExpr}
                      ORDER BY p.nombre ASC";
            $stmt = $this->conn->prepare($query);
        } else {
            $query = "SELECT
                        id,
                        codigo_barras,
                        nombre,
                        precio,
                        stock,
                        {$stockMinExpr} AS stock_minimo,
                        NULL AS fefo_proximo_vencimiento,
                        0 AS lotes_vencidos,
                        0 AS lotes_por_vencer
                      FROM {$this->table_name}
                      WHERE codigo_barras LIKE ? OR nombre LIKE ?
                      ORDER BY nombre ASC";
            $stmt = $this->conn->prepare($query);
        }

        $busqueda = '%' . htmlspecialchars(strip_tags((string)$termino)) . '%';
        $stmt->bindParam(1, $busqueda);
        $stmt->bindParam(2, $busqueda);
        $stmt->execute();
        return $stmt;
    }

    public function listarLotesConVencimiento(string $estado = '', int $dias = 30): array
    {
        if (!$this->hasTable('producto_lotes')) {
            return [];
        }

        $dias = max(1, $dias);
        $where = [];
        $usaDias = false;

        if ($estado === 'vencido') {
            $where[] = "l.fecha_vencimiento < CURDATE()";
        } elseif ($estado === 'por_vencer') {
            $where[] = "l.fecha_vencimiento >= CURDATE() AND l.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)";
            $usaDias = true;
        } elseif ($estado === 'activo') {
            $where[] = "l.fecha_vencimiento > DATE_ADD(CURDATE(), INTERVAL :dias DAY)";
            $usaDias = true;
        }

        $query = "SELECT
                    l.id,
                    l.producto_id,
                    p.codigo_barras,
                    p.nombre,
                    l.numero_lote,
                    l.fecha_vencimiento,
                    l.cantidad_inicial,
                    l.cantidad_disponible,
                    l.estado,
                    DATEDIFF(l.fecha_vencimiento, CURDATE()) AS dias_para_vencer
                  FROM producto_lotes l
                  INNER JOIN productos p ON p.id = l.producto_id";
        if (!empty($where)) {
            $query .= " WHERE " . implode(' AND ', $where);
        }
        $query .= " ORDER BY l.fecha_vencimiento ASC, p.nombre ASC";

        $stmt = $this->conn->prepare($query);
        if ($usaDias) {
            $stmt->bindValue(':dias', $dias, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarMargenPorProducto(): array
    {
        if ($this->hasTable('producto_lotes')) {
            $query = "SELECT
                        p.id,
                        p.codigo_barras,
                        p.nombre,
                        p.precio,
                        p.stock,
                        COALESCE(
                            SUM(CASE WHEN l.cantidad_disponible > 0 AND l.costo_unitario IS NOT NULL THEN l.costo_unitario * l.cantidad_disponible ELSE 0 END)
                            / NULLIF(SUM(CASE WHEN l.cantidad_disponible > 0 AND l.costo_unitario IS NOT NULL THEN l.cantidad_disponible ELSE 0 END), 0),
                            AVG(CASE WHEN l.costo_unitario IS NOT NULL THEN l.costo_unitario ELSE NULL END),
                            0
                        ) AS costo_referencia,
                        COALESCE(SUM(CASE WHEN l.cantidad_disponible > 0 THEN l.cantidad_disponible ELSE 0 END), 0) AS stock_en_lotes
                      FROM productos p
                      LEFT JOIN producto_lotes l ON l.producto_id = p.id
                      GROUP BY p.id, p.codigo_barras, p.nombre, p.precio, p.stock
                      ORDER BY p.nombre ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $query = "SELECT
                    p.id,
                    p.codigo_barras,
                    p.nombre,
                    p.precio,
                    p.stock,
                    0 AS costo_referencia,
                    0 AS stock_en_lotes
                  FROM productos p
                  ORDER BY p.nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarStockCritico($minimo = 5)
    {
        if ($this->hasColumn($this->table_name, 'stock_minimo')) {
            $query = "SELECT COUNT(*) as total FROM {$this->table_name} WHERE stock <= stock_minimo";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        } else {
            $query = "SELECT COUNT(*) as total FROM {$this->table_name} WHERE stock <= :minimo";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':minimo', (int)$minimo, PDO::PARAM_INT);
            $stmt->execute();
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }
}
