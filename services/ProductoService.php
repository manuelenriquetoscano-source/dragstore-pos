<?php

require_once __DIR__ . '/../models/Producto.php';

class ProductoService
{
    private $productoModel;

    public function __construct(PDO $db)
    {
        $this->productoModel = new Producto($db);
    }

    public function buscar(string $termino): array
    {
        $termino = trim($termino);
        if ($termino === '') {
            return [];
        }

        $stmt = $this->productoModel->buscar($termino);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['stock'] = (int)$row['stock'];
            $row['precio'] = (float)$row['precio'];
            $result[] = $row;
        }
        return $result;
    }

    public function listarInventario(bool $soloBajoStock = false): array
    {
        if ($soloBajoStock) {
            $stmt = $this->productoModel->leerBajoStock(5);
        } else {
            $stmt = $this->productoModel->leerTodo();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(array $input): array
    {
        $codigo = trim((string)($input['codigo_barras'] ?? ''));
        $nombre = trim((string)($input['nombre'] ?? ''));
        $precio = (float)($input['precio'] ?? 0);
        $stock = (int)($input['stock'] ?? 0);

        if ($codigo === '' || $nombre === '') {
            return ['ok' => false, 'message' => 'Código y nombre son obligatorios.'];
        }
        if ($precio <= 0) {
            return ['ok' => false, 'message' => 'El precio debe ser mayor que 0.'];
        }
        if ($stock < 0) {
            return ['ok' => false, 'message' => 'El stock no puede ser negativo.'];
        }

        $this->productoModel->codigo_barras = $codigo;
        $this->productoModel->nombre = $nombre;
        $this->productoModel->precio = $precio;
        $this->productoModel->stock = $stock;

        if ($this->productoModel->crear()) {
            return ['ok' => true, 'message' => '¡Producto guardado con éxito!'];
        }

        return ['ok' => false, 'message' => 'No se pudo guardar el producto. Verifica el código.'];
    }

    public function eliminar(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $this->productoModel->id = $id;
        return $this->productoModel->eliminar();
    }

    public function contarStockCritico(int $minimo = 5): int
    {
        return (int)$this->productoModel->contarStockCritico($minimo);
    }
}
