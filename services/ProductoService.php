<?php

require_once __DIR__ . '/../models/Producto.php';

class ProductoService
{
    private $productoModel;
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
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
            $row['stock_minimo'] = (int)($row['stock_minimo'] ?? 5);
            $row['precio'] = (float)$row['precio'];
            $row['lotes_vencidos'] = (int)($row['lotes_vencidos'] ?? 0);
            $row['lotes_por_vencer'] = (int)($row['lotes_por_vencer'] ?? 0);
            $result[] = $row;
        }
        return $result;
    }

    public function listarInventario(bool $soloBajoStock = false): array
    {
        if ($soloBajoStock) {
            $stmt = $this->productoModel->leerBajoStockConAlertas(5, 30);
        } else {
            $stmt = $this->productoModel->leerTodoConAlertas(30);
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['precio'] = (float)$row['precio'];
            $row['stock'] = (int)$row['stock'];
            $row['stock_minimo'] = (int)($row['stock_minimo'] ?? 5);
            $row['lotes_activos'] = (int)($row['lotes_activos'] ?? 0);
            $row['lotes_vencidos'] = (int)($row['lotes_vencidos'] ?? 0);
            $row['lotes_por_vencer'] = (int)($row['lotes_por_vencer'] ?? 0);
            $row['estado_stock'] = ($row['stock'] <= $row['stock_minimo']) ? 'critico' : 'ok';
            $row['estado_vencimiento'] = 'ok';
            if ($row['lotes_vencidos'] > 0) {
                $row['estado_vencimiento'] = 'vencido';
            } elseif ($row['lotes_por_vencer'] > 0) {
                $row['estado_vencimiento'] = 'proximo';
            }
        }
        unset($row);

        return $rows;
    }

    public function crear(array $input): array
    {
        $codigo = trim((string)($input['codigo_barras'] ?? ''));
        $nombre = trim((string)($input['nombre'] ?? ''));
        $precio = (float)($input['precio'] ?? 0);
        $stock = (int)($input['stock'] ?? 0);
        $stockMinimo = (int)($input['stock_minimo'] ?? 5);
        $numeroLote = trim((string)($input['numero_lote'] ?? ''));
        $fechaVencimiento = trim((string)($input['fecha_vencimiento'] ?? ''));
        $cantidadLoteRaw = $input['cantidad_lote'] ?? null;
        $cantidadLote = ($cantidadLoteRaw === null || $cantidadLoteRaw === '') ? $stock : (int)$cantidadLoteRaw;

        if ($codigo === '' || $nombre === '') {
            return ['ok' => false, 'message' => 'Codigo y nombre son obligatorios.'];
        }
        if ($precio <= 0) {
            return ['ok' => false, 'message' => 'El precio debe ser mayor que 0.'];
        }
        if ($stock < 0) {
            return ['ok' => false, 'message' => 'El stock no puede ser negativo.'];
        }
        if ($stockMinimo < 1) {
            return ['ok' => false, 'message' => 'El stock minimo debe ser al menos 1.'];
        }
        $usaLote = ($numeroLote !== '' || $fechaVencimiento !== '' || $cantidadLoteRaw !== null);
        if ($usaLote) {
            if ($numeroLote === '' || $fechaVencimiento === '') {
                return ['ok' => false, 'message' => 'Para lote inicial debe informar numero de lote y fecha de vencimiento.'];
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVencimiento)) {
                return ['ok' => false, 'message' => 'La fecha de vencimiento debe tener formato YYYY-MM-DD.'];
            }
            if ($cantidadLote < 0) {
                return ['ok' => false, 'message' => 'La cantidad de lote no puede ser negativa.'];
            }
        }

        $this->productoModel->codigo_barras = $codigo;
        $this->productoModel->nombre = $nombre;
        $this->productoModel->precio = $precio;
        $this->productoModel->stock = $stock;
        $this->productoModel->stock_minimo = $stockMinimo;

        $productoId = $this->productoModel->crear();
        if (!$productoId) {
            return ['ok' => false, 'message' => 'No se pudo guardar el producto. Verifique el codigo.'];
        }

        if ($usaLote) {
            $okLote = $this->productoModel->crearLote((int)$productoId, $numeroLote, $fechaVencimiento, (int)$cantidadLote, null);
            if (!$okLote) {
                return ['ok' => true, 'message' => 'Producto creado, pero no se pudo guardar el lote inicial (ejecute migraciones).'];
            }
        }

        return ['ok' => true, 'message' => 'Producto guardado con exito.'];
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

    public function obtenerProducto(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        return $this->productoModel->obtenerPorId($id);
    }

    public function listarLotesPorProducto(int $productoId): array
    {
        if ($productoId <= 0) {
            return [];
        }

        $lotes = $this->productoModel->listarLotesPorProducto($productoId);
        foreach ($lotes as &$lote) {
            $lote['id'] = (int)$lote['id'];
            $lote['producto_id'] = (int)$lote['producto_id'];
            $lote['cantidad_inicial'] = (int)$lote['cantidad_inicial'];
            $lote['cantidad_disponible'] = (int)$lote['cantidad_disponible'];
            $lote['costo_unitario'] = $lote['costo_unitario'] !== null ? (float)$lote['costo_unitario'] : null;

            $estado = (string)($lote['estado'] ?? 'activo');
            if ($lote['cantidad_disponible'] <= 0) {
                $estado = 'agotado';
            } elseif (!empty($lote['fecha_vencimiento']) && $lote['fecha_vencimiento'] < date('Y-m-d')) {
                $estado = 'vencido';
            } elseif (!empty($lote['fecha_vencimiento']) && $lote['fecha_vencimiento'] <= date('Y-m-d', strtotime('+30 days'))) {
                $estado = 'por_vencer';
            }
            $lote['estado_calculado'] = $estado;
        }
        unset($lote);

        return $lotes;
    }

    public function registrarLote(array $input): array
    {
        $productoId = (int)($input['producto_id'] ?? 0);
        $numeroLote = trim((string)($input['numero_lote'] ?? ''));
        $fechaVencimiento = trim((string)($input['fecha_vencimiento'] ?? ''));
        $cantidad = (int)($input['cantidad'] ?? 0);
        $costoRaw = $input['costo_unitario'] ?? null;
        $costoUnitario = ($costoRaw === null || $costoRaw === '') ? null : (float)$costoRaw;

        if ($productoId <= 0) {
            return ['ok' => false, 'message' => 'Producto invalido.'];
        }
        if ($numeroLote === '') {
            return ['ok' => false, 'message' => 'Debe informar el numero de lote.'];
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVencimiento)) {
            return ['ok' => false, 'message' => 'Fecha de vencimiento invalida.'];
        }
        if ($cantidad <= 0) {
            return ['ok' => false, 'message' => 'La cantidad debe ser mayor que 0.'];
        }
        if ($costoUnitario !== null && $costoUnitario < 0) {
            return ['ok' => false, 'message' => 'El costo unitario no puede ser negativo.'];
        }

        $producto = $this->productoModel->obtenerPorId($productoId);
        if (!$producto) {
            return ['ok' => false, 'message' => 'Producto no encontrado.'];
        }

        try {
            $this->db->beginTransaction();
            $okLote = $this->productoModel->crearLote($productoId, $numeroLote, $fechaVencimiento, $cantidad, $costoUnitario);
            if (!$okLote) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'No se pudo registrar el lote (ejecute migraciones).'];
            }
            $okStock = $this->productoModel->agregarStock($productoId, $cantidad);
            if (!$okStock) {
                $this->db->rollBack();
                return ['ok' => false, 'message' => 'No se pudo actualizar el stock del producto.'];
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'message' => 'Error al registrar lote: ' . $e->getMessage()];
        }

        return ['ok' => true, 'message' => 'Lote registrado y stock actualizado correctamente.'];
    }

    public function listarReporteVencimientos(string $estado = '', int $dias = 30): array
    {
        $estado = strtolower(trim($estado));
        if (!in_array($estado, ['', 'vencido', 'por_vencer', 'activo'], true)) {
            $estado = '';
        }
        $dias = max(1, min(180, $dias));

        $rows = $this->productoModel->listarLotesConVencimiento($estado, $dias);
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['producto_id'] = (int)$row['producto_id'];
            $row['cantidad_inicial'] = (int)$row['cantidad_inicial'];
            $row['cantidad_disponible'] = (int)$row['cantidad_disponible'];
            $row['dias_para_vencer'] = (int)$row['dias_para_vencer'];

            if ($row['cantidad_disponible'] <= 0) {
                $row['estado_calculado'] = 'agotado';
            } elseif ($row['dias_para_vencer'] < 0) {
                $row['estado_calculado'] = 'vencido';
            } elseif ($row['dias_para_vencer'] <= $dias) {
                $row['estado_calculado'] = 'por_vencer';
            } else {
                $row['estado_calculado'] = 'activo';
            }
        }
        unset($row);

        return $rows;
    }

    public function listarReporteMargen(): array
    {
        $rows = $this->productoModel->listarMargenPorProducto();
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['precio'] = (float)$row['precio'];
            $row['stock'] = (int)$row['stock'];
            $row['stock_en_lotes'] = (int)($row['stock_en_lotes'] ?? 0);
            $row['costo_referencia'] = (float)($row['costo_referencia'] ?? 0);
            $row['margen_unitario'] = $row['precio'] - $row['costo_referencia'];
            $row['margen_pct'] = $row['precio'] > 0 ? (($row['margen_unitario'] / $row['precio']) * 100) : 0.0;
            if ($row['margen_pct'] < 0) {
                $row['estado_margen'] = 'negativo';
            } elseif ($row['margen_pct'] < 20) {
                $row['estado_margen'] = 'bajo';
            } else {
                $row['estado_margen'] = 'saludable';
            }
        }
        unset($row);
        return $rows;
    }
}
