<?php

require_once __DIR__ . '/../models/Promocion.php';

class PromocionService
{
    private $model;

    public function __construct(PDO $db)
    {
        $this->model = new Promocion($db);
    }

    public function tableExists(): bool
    {
        return $this->model->tableExists();
    }

    private function parseJsonArrayInput(string $raw, bool $asNumbers = false): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        // Permite pegar JSON o lista separada por coma/espacio/nueva linea.
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $values = $decoded;
        } else {
            $parts = preg_split('/[\s,;]+/', $raw);
            $values = $parts ?: [];
        }

        $clean = [];
        foreach ($values as $v) {
            if ($asNumbers) {
                $n = (int)$v;
                if ($n > 0) $clean[] = $n;
            } else {
                $s = trim((string)$v);
                if ($s !== '') $clean[] = $s;
            }
        }

        return array_values(array_unique($clean));
    }

    private function normalizeDiasSemana(string $raw): array
    {
        $dias = $this->parseJsonArrayInput($raw, true);
        $valid = [];
        foreach ($dias as $d) {
            if ($d >= 0 && $d <= 6) {
                $valid[] = $d;
            }
        }
        return array_values(array_unique($valid));
    }

    private function normalizeRequiredItems(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return [];

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) continue;
            $qty = isset($row['qty']) ? max(1, (int)$row['qty']) : 1;
            $pid = isset($row['product_id']) ? (int)$row['product_id'] : 0;
            $cb = isset($row['codigo_barras']) ? trim((string)$row['codigo_barras']) : '';
            if ($pid <= 0 && $cb === '') continue;
            $item = ['qty' => $qty];
            if ($pid > 0) $item['product_id'] = $pid;
            if ($cb !== '') $item['codigo_barras'] = $cb;
            $items[] = $item;
        }
        return $items;
    }

    private function normalizeRow(array $r): array
    {
        return [
            'id' => (int)$r['id'],
            'nombre' => (string)$r['nombre'],
            'tipo' => (string)$r['tipo'],
            'activo' => (int)$r['activo'] === 1,
            'prioridad' => (int)$r['prioridad'],
            'percent_value' => $r['percent_value'] !== null ? (float)$r['percent_value'] : null,
            'min_qty' => $r['min_qty'] !== null ? (int)$r['min_qty'] : null,
            'combo_price' => $r['combo_price'] !== null ? (float)$r['combo_price'] : null,
            'product_ids' => $r['product_ids_json'] ? (json_decode((string)$r['product_ids_json'], true) ?: []) : [],
            'codigos_barras' => $r['codigos_barras_json'] ? (json_decode((string)$r['codigos_barras_json'], true) ?: []) : [],
            'required_items' => $r['required_items_json'] ? (json_decode((string)$r['required_items_json'], true) ?: []) : [],
            'dias_semana' => $r['dias_semana_json'] ? (json_decode((string)$r['dias_semana_json'], true) ?: []) : [],
            'hora_desde' => $r['hora_desde'] ?? null,
            'hora_hasta' => $r['hora_hasta'] ?? null,
            'vigencia_desde' => $r['vigencia_desde'] ?? null,
            'vigencia_hasta' => $r['vigencia_hasta'] ?? null
        ];
    }

    private function validHora(?string $v): ?string
    {
        $v = $v !== null ? trim($v) : '';
        if ($v === '') return null;
        return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $v) ? substr($v, 0, 8) : null;
    }

    private function validDate(?string $v): ?string
    {
        $v = $v !== null ? trim($v) : '';
        if ($v === '') return null;
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }

    public function listarTodas(): array
    {
        if (!$this->model->tableExists()) return [];
        $rows = $this->model->listarTodas();
        return array_map([$this, 'normalizeRow'], $rows);
    }

    public function obtenerPorId(int $id): ?array
    {
        $r = $this->model->obtenerPorId($id);
        return $r ? $this->normalizeRow($r) : null;
    }

    public function guardar(array $input): array
    {
        if (!$this->model->tableExists()) {
            return ['ok' => false, 'message' => 'Tabla de promociones no existe. Ejecute migraciones.'];
        }

        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $nombre = trim((string)($input['nombre'] ?? ''));
        $tipo = strtolower(trim((string)($input['tipo'] ?? '')));
        $activo = isset($input['activo']) ? 1 : 0;
        $prioridad = (int)($input['prioridad'] ?? 100);
        $percent = isset($input['percent_value']) && $input['percent_value'] !== '' ? (float)$input['percent_value'] : null;
        $minQty = isset($input['min_qty']) && $input['min_qty'] !== '' ? max(1, (int)$input['min_qty']) : null;
        $comboPrice = isset($input['combo_price']) && $input['combo_price'] !== '' ? (float)$input['combo_price'] : null;

        if ($nombre === '') return ['ok' => false, 'message' => 'Nombre obligatorio.'];
        if (!in_array($tipo, ['2x1', 'percent', 'combo'], true)) return ['ok' => false, 'message' => 'Tipo invalido.'];

        $productIds = $this->parseJsonArrayInput((string)($input['product_ids'] ?? ''), true);
        $codigos = $this->parseJsonArrayInput((string)($input['codigos_barras'] ?? ''), false);
        $requiredItems = $this->normalizeRequiredItems((string)($input['required_items'] ?? ''));
        $diasSemana = $this->normalizeDiasSemana((string)($input['dias_semana'] ?? ''));
        $horaDesde = $this->validHora(isset($input['hora_desde']) ? (string)$input['hora_desde'] : null);
        $horaHasta = $this->validHora(isset($input['hora_hasta']) ? (string)$input['hora_hasta'] : null);
        $vigenciaDesde = $this->validDate(isset($input['vigencia_desde']) ? (string)$input['vigencia_desde'] : null);
        $vigenciaHasta = $this->validDate(isset($input['vigencia_hasta']) ? (string)$input['vigencia_hasta'] : null);

        if ($tipo === '2x1' || $tipo === 'percent') {
            if (empty($productIds) && empty($codigos)) {
                return ['ok' => false, 'message' => 'Debe informar productos o codigos para la promo.'];
            }
        }
        if ($tipo === 'percent') {
            if ($percent === null || $percent <= 0 || $percent > 100) {
                return ['ok' => false, 'message' => 'Porcentaje invalido (1-100).'];
            }
        }
        if ($tipo === 'combo') {
            if ($comboPrice === null || $comboPrice <= 0) {
                return ['ok' => false, 'message' => 'Combo price debe ser mayor a 0.'];
            }
            if (count($requiredItems) < 2) {
                return ['ok' => false, 'message' => 'Combo requiere al menos 2 items en required_items (JSON).'];
            }
        }

        $payload = [
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':activo' => $activo,
            ':prioridad' => $prioridad,
            ':percent_value' => $percent,
            ':min_qty' => $minQty,
            ':combo_price' => $comboPrice,
            ':product_ids_json' => !empty($productIds) ? json_encode($productIds, JSON_UNESCAPED_UNICODE) : null,
            ':codigos_barras_json' => !empty($codigos) ? json_encode($codigos, JSON_UNESCAPED_UNICODE) : null,
            ':required_items_json' => !empty($requiredItems) ? json_encode($requiredItems, JSON_UNESCAPED_UNICODE) : null,
            ':dias_semana_json' => !empty($diasSemana) ? json_encode($diasSemana, JSON_UNESCAPED_UNICODE) : null,
            ':hora_desde' => $horaDesde,
            ':hora_hasta' => $horaHasta,
            ':vigencia_desde' => $vigenciaDesde,
            ':vigencia_hasta' => $vigenciaHasta
        ];

        if ($id > 0) {
            $ok = $this->model->actualizar($id, $payload);
            return ['ok' => (bool)$ok, 'message' => $ok ? 'Promocion actualizada.' : 'No se pudo actualizar.'];
        }

        $newId = $this->model->crear($payload);
        return ['ok' => $newId > 0, 'message' => $newId > 0 ? 'Promocion creada.' : 'No se pudo crear.', 'id' => $newId];
    }

    public function setActivo(int $id, bool $activo): bool
    {
        return $this->model->setActivo($id, $activo);
    }

    public function eliminar(int $id): bool
    {
        return $this->model->eliminar($id);
    }

    private function nowDayIndex(): int
    {
        // date('w'): 0 domingo .. 6 sabado
        return (int)date('w');
    }

    private function nowTime(): string
    {
        return date('H:i:s');
    }

    private function nowDate(): string
    {
        return date('Y-m-d');
    }

    private function isWithinSchedule(array $promo): bool
    {
        $nowDate = $this->nowDate();
        $nowTime = $this->nowTime();
        $todayDay = $this->nowDayIndex();

        if (!empty($promo['vigencia_desde']) && $nowDate < $promo['vigencia_desde']) return false;
        if (!empty($promo['vigencia_hasta']) && $nowDate > $promo['vigencia_hasta']) return false;

        if (!empty($promo['dias_semana']) && is_array($promo['dias_semana'])) {
            $days = array_map('intval', $promo['dias_semana']);
            if (!in_array($todayDay, $days, true)) return false;
        }

        $from = $promo['hora_desde'] ?? null;
        $to = $promo['hora_hasta'] ?? null;
        if ($from && $to) {
            // si cruza medianoche, soporta ventana nocturna
            if ($from <= $to) {
                if (!($nowTime >= $from && $nowTime <= $to)) return false;
            } else {
                if (!($nowTime >= $from || $nowTime <= $to)) return false;
            }
        } elseif ($from) {
            if (!($nowTime >= $from)) return false;
        } elseif ($to) {
            if (!($nowTime <= $to)) return false;
        }

        return true;
    }

    public function listarActivasRuntime(): array
    {
        if (!$this->model->tableExists()) return [];
        $rows = $this->model->listarActivas();
        $promos = array_map([$this, 'normalizeRow'], $rows);
        $active = [];
        foreach ($promos as $p) {
            if ($this->isWithinSchedule($p)) {
                $active[] = $p;
            }
        }
        return $active;
    }
}
