<?php

require_once __DIR__ . '/../models/Venta.php';

class VentaService
{
    private $ventaModel;

    public function __construct(PDO $db)
    {
        $this->ventaModel = new Venta($db);
    }

    public function registrar(array $carrito, float $total): array
    {
        if (empty($carrito)) {
            return ['ok' => false, 'message' => 'No hay productos para vender'];
        }

        if ($total <= 0) {
            return ['ok' => false, 'message' => 'Total inválido'];
        }

        foreach ($carrito as $item) {
            if (!isset($item['id'], $item['cantidad'], $item['precio'])) {
                return ['ok' => false, 'message' => 'Ítems de venta inválidos'];
            }
        }

        $ok = $this->ventaModel->registrarVenta($carrito, $total);
        if (!$ok) {
            return ['ok' => false, 'message' => 'Error al procesar la transacción'];
        }

        return ['ok' => true, 'message' => 'Venta completada'];
    }
}

