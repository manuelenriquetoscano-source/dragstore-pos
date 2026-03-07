<?php

class Promocion
{
    private $conn;
    private $table = 'promociones_pos';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function tableExists(): bool
    {
        try {
            $stmt = $this->conn->query("SHOW TABLES LIKE 'promociones_pos'");
            return $stmt && (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function listarTodas(): array
    {
        $query = "SELECT *
                  FROM {$this->table}
                  ORDER BY activo DESC, prioridad ASC, id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarActivas(): array
    {
        $query = "SELECT *
                  FROM {$this->table}
                  WHERE activo = 1
                  ORDER BY prioridad ASC, id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function crear(array $data): int
    {
        $query = "INSERT INTO {$this->table}
                    (nombre, tipo, activo, prioridad, percent_value, min_qty, combo_price, product_ids_json, codigos_barras_json, required_items_json, dias_semana_json, hora_desde, hora_hasta, vigencia_desde, vigencia_hasta)
                  VALUES
                    (:nombre, :tipo, :activo, :prioridad, :percent_value, :min_qty, :combo_price, :product_ids_json, :codigos_barras_json, :required_items_json, :dias_semana_json, :hora_desde, :hora_hasta, :vigencia_desde, :vigencia_hasta)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($data);
        return (int)$this->conn->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool
    {
        $query = "UPDATE {$this->table}
                  SET nombre = :nombre,
                      tipo = :tipo,
                      activo = :activo,
                      prioridad = :prioridad,
                      percent_value = :percent_value,
                      min_qty = :min_qty,
                      combo_price = :combo_price,
                      product_ids_json = :product_ids_json,
                      codigos_barras_json = :codigos_barras_json,
                      required_items_json = :required_items_json,
                      dias_semana_json = :dias_semana_json,
                      hora_desde = :hora_desde,
                      hora_hasta = :hora_hasta,
                      vigencia_desde = :vigencia_desde,
                      vigencia_hasta = :vigencia_hasta
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $data[':id'] = $id;
        return $stmt->execute($data);
    }

    public function setActivo(int $id, bool $activo): bool
    {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET activo = :activo WHERE id = :id");
        return $stmt->execute([
            ':activo' => $activo ? 1 : 0,
            ':id' => $id
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
