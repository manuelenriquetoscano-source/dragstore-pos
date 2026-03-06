<?php

class Usuario
{
    private $conn;
    private $table_name = "usuarios";

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function listarTodos()
    {
        $query = "SELECT id, username, role, display_name, activo, created_at, updated_at
                  FROM " . $this->table_name . "
                  ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function buscarPorUsername(string $username)
    {
        $query = "SELECT id, username, role, display_name, activo
                  FROM " . $this->table_name . "
                  WHERE username = :username
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function buscarPorId(int $id)
    {
        $query = "SELECT id, username, role, display_name, activo
                  FROM " . $this->table_name . "
                  WHERE id = :id
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function crear(string $username, string $passwordHash, string $role, string $displayName, int $activo = 1): bool
    {
        $query = "INSERT INTO " . $this->table_name . " (username, password_hash, role, display_name, activo)
                  VALUES (:username, :password_hash, :role, :display_name, :activo)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':username' => $username,
            ':password_hash' => $passwordHash,
            ':role' => $role,
            ':display_name' => $displayName,
            ':activo' => $activo
        ]);
    }

    public function actualizarPassword(int $id, string $passwordHash): bool
    {
        $query = "UPDATE " . $this->table_name . "
                  SET password_hash = :password_hash
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':password_hash' => $passwordHash,
            ':id' => $id
        ]);
    }

    public function actualizarEstado(int $id, int $activo): bool
    {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = :activo
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':activo' => $activo,
            ':id' => $id
        ]);
    }

    public function actualizarRol(int $id, string $role): bool
    {
        $query = "UPDATE " . $this->table_name . "
                  SET role = :role
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':role' => $role,
            ':id' => $id
        ]);
    }

    public function contarAdminsActivos(): int
    {
        $query = "SELECT COUNT(*) AS total
                  FROM " . $this->table_name . "
                  WHERE role = 'admin' AND activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }
}
