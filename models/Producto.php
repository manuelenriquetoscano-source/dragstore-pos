<?php
// Archivo: models/Producto.php

class Producto {
    private $conn;
    private $table_name = "productos";

    // Propiedades del objeto
    public $id;
    public $codigo_barras;
    public $nombre;
    public $precio;
    public $stock;

    public function __construct($db) {
        $this->conn = $db;
    }

    // LEER TODO: Para el listado general
    public function leerTodo() {
        $query = "SELECT id, codigo_barras, nombre, precio, stock 
                  FROM " . $this->table_name . " 
                  ORDER BY nombre ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerBajoStock($minimo = 5) {
        $query = "SELECT id, codigo_barras, nombre, precio, stock 
                  FROM " . $this->table_name . " 
                  WHERE stock < :minimo
                  ORDER BY stock ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':minimo', (int)$minimo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // CREAR: Para registrar nuevos ingresos
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET codigo_barras=:codigo, nombre=:nombre, precio=:precio, stock=:stock";

        $stmt = $this->conn->prepare($query);

        // Seguridad: Limpiar datos
        $this->codigo_barras = htmlspecialchars(strip_tags($this->codigo_barras));
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));

        $stmt->bindParam(":codigo", $this->codigo_barras);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":stock", $this->stock);

        return $stmt->execute();
    }

    // ELIMINAR: Para dar de baja productos
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // BUSCAR: Clave para la rapidez de la caja y el inventario
    public function buscar($termino) {
        $query = "SELECT id, codigo_barras, nombre, precio, stock 
                  FROM " . $this->table_name . " 
                  WHERE codigo_barras LIKE ? OR nombre LIKE ? 
                  ORDER BY nombre ASC";

        $stmt = $this->conn->prepare($query);
        
        // Limpiamos y preparamos el término para coincidencias parciales
        $busqueda = "%" . htmlspecialchars(strip_tags($termino)) . "%";
        $stmt->bindParam(1, $busqueda);
        $stmt->bindParam(2, $busqueda);

        $stmt->execute();
        return $stmt;
    }

    // STOCK CRÍTICO: La nueva alerta del Menú Principal
    public function contarStockCritico($minimo = 5) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE stock < ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$minimo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
