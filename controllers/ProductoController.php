<?php

require_once __DIR__ . '/../services/ProductoService.php';

class ProductoController
{
    private $service;

    public function __construct(PDO $db)
    {
        $this->service = new ProductoService($db);
    }

    public function buscarParaCaja(string $termino): array
    {
        $termino = trim($termino);
        if ($termino === '') {
            return ['ok' => false, 'message' => 'Término vacío', 'statusCode' => 400, 'data' => []];
        }

        $productos = $this->service->buscar($termino);
        if (empty($productos)) {
            return ['ok' => false, 'message' => 'No se encontró nada', 'statusCode' => 404, 'data' => []];
        }

        return [
            'ok' => true,
            'message' => 'Resultados encontrados',
            'statusCode' => 200,
            'data' => $productos,
            'count' => count($productos)
        ];
    }

    public function listarInventario(bool $filtroBajoStock): array
    {
        return $this->service->listarInventario($filtroBajoStock);
    }

    public function crearProductoDesdeRequest(array $request): array
    {
        return $this->service->crear($request);
    }

    public function eliminarProducto(int $id): bool
    {
        return $this->service->eliminar($id);
    }

    public function contarStockCritico(int $minimo = 5): int
    {
        return $this->service->contarStockCritico($minimo);
    }
}
