<?php
require_once __DIR__ . '/env.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    public $conn;

    public function __construct() {
        $this->host = env('DB_HOST', 'localhost');
        $this->db_name = env('DB_NAME', 'dragstore_db');
        $this->username = env('DB_USER', 'root');
        $this->password = env('DB_PASS', 'root');
        $this->charset = env('DB_CHARSET', 'utf8mb4');
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("SET time_zone = '-03:00'");
        } catch (PDOException $exception) {
            error_log('Error de conexion DB: ' . $exception->getMessage());
            if (env('APP_DEBUG', 'false') === 'true') {
                echo 'Error de conexion DB.';
            }
        }

        return $this->conn;
    }
}
?>
