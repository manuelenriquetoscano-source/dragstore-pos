<?php
// Archivo: config/database.php

class Database {
    private $host = "localhost";
    private $db_name = "dragstore_db"; // Cambia esto al nombre de tu DB en Workbench
    private $username = "root";
    private $password = "root"; // Tu contraseña de Workbench
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            // Configuramos para que PHP nos avise si hay errores de SQL
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>