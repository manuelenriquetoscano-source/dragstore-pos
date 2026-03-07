<?php

class DashboardService
{
    private $db;
    private $columnCache = [];
    private $cacheTtlSeconds;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $ttl = 45;
        if (function_exists('env')) {
            $rawTtl = env('DASHBOARD_CACHE_TTL', '45');
            if (is_numeric($rawTtl)) {
                $ttl = (int)$rawTtl;
            }
        }
        $this->cacheTtlSeconds = max(0, $ttl);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$table} LIKE :column");
            $stmt->execute([':column' => $column]);
            $this->columnCache[$key] = $stmt && (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            $this->columnCache[$key] = false;
        }

        return $this->columnCache[$key];
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }
        return $value;
    }

    private static function getCacheBaseDir(): string
    {
        $baseDir = dirname(__DIR__) . '/cache/dashboard';
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0775, true);
        }
        return $baseDir;
    }

    private function getCacheFilePath(string $cacheKey): string
    {
        $baseDir = self::getCacheBaseDir();
        return $baseDir . '/summary_' . sha1($cacheKey) . '.json';
    }

    private function getCachedSummary(string $cacheKey): ?array
    {
        if ($this->cacheTtlSeconds <= 0) {
            return null;
        }

        $path = $this->getCacheFilePath($cacheKey);
        if (!is_file($path)) {
            return null;
        }

        $maxAge = time() - $this->cacheTtlSeconds;
        if (@filemtime($path) < $maxAge) {
            return null;
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function setCachedSummary(string $cacheKey, array $data): void
    {
        if ($this->cacheTtlSeconds <= 0) {
            return;
        }

        $path = $this->getCacheFilePath($cacheKey);
        @file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function clearCache(): int
    {
        $baseDir = self::getCacheBaseDir();
        if (!is_dir($baseDir)) {
            return 0;
        }

        $files = glob($baseDir . '/summary_*.json');
        if ($files === false) {
            return 0;
        }

        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file) && @unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public static function clearCacheGlobal(): int
    {
        $baseDir = self::getCacheBaseDir();
        if (!is_dir($baseDir)) {
            return 0;
        }

        $files = glob($baseDir . '/summary_*.json');
        if ($files === false) {
            return 0;
        }

        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file) && @unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public function generarResumen(array $filters = [], bool $useCache = true): array
    {
        $dateFrom = isset($filters['date_from']) ? $this->normalizeDate((string)$filters['date_from']) : '';
        $dateTo = isset($filters['date_to']) ? $this->normalizeDate((string)$filters['date_to']) : '';
        $scopeUserId = isset($filters['scope_user_id']) ? max(0, (int)$filters['scope_user_id']) : 0;
        $scopeRole = isset($filters['scope_role']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$filters['scope_role'])) : '';
        $today = date('Y-m-d');
        if ($dateFrom === '' && $dateTo === '') {
            $dateFrom = $today;
            $dateTo = $today;
        } elseif ($dateFrom === '') {
            $dateFrom = $dateTo;
        } elseif ($dateTo === '') {
            $dateTo = $dateFrom;
        }

        $normalizedFilters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'scope_user_id' => $scopeUserId,
            'scope_role' => $scopeRole
        ];
        $cacheKey = json_encode($normalizedFilters);
        if ($useCache) {
            $cached = $this->getCachedSummary((string)$cacheKey);
            if (is_array($cached)) {
                $cached['cache'] = [
                    'hit' => true,
                    'ttl' => $this->cacheTtlSeconds
                ];
                if (!isset($cached['meta']) || !is_array($cached['meta'])) {
                    $cached['meta'] = [];
                }
                $cached['meta']['served_at'] = date('Y-m-d H:i:s');
                return $cached;
            }
        }

        $hasEstadoVenta = $this->hasColumn('ventas', 'estado');
        $hasMetodoPago = $this->hasColumn('ventas', 'metodo_pago');
        $hasUsuarioVenta = $this->hasColumn('ventas', 'usuario_id');

        $ventasWhere = ["DATE(v.fecha) >= :date_from", "DATE(v.fecha) <= :date_to"];
        $ventasParams = [':date_from' => $dateFrom, ':date_to' => $dateTo];

        $queryKpi = "SELECT
                        COALESCE(SUM(CASE WHEN " . ($hasEstadoVenta ? "v.estado = 'anulada'" : "0=1") . " THEN v.total ELSE 0 END), 0) AS total_anulado,
                        COALESCE(SUM(CASE WHEN " . ($hasEstadoVenta ? "v.estado <> 'anulada'" : "1=1") . " THEN v.total ELSE 0 END), 0) AS total_neto,
                        COUNT(CASE WHEN " . ($hasEstadoVenta ? "v.estado <> 'anulada'" : "1=1") . " THEN 1 END) AS cantidad_neta
                     FROM ventas v
                     WHERE " . implode(' AND ', $ventasWhere);
        $stmtKpi = $this->db->prepare($queryKpi);
        foreach ($ventasParams as $k => $v) {
            $stmtKpi->bindValue($k, $v);
        }
        $stmtKpi->execute();
        $kpi = $stmtKpi->fetch(PDO::FETCH_ASSOC) ?: [];
        $totalNeto = (float)($kpi['total_neto'] ?? 0);
        $totalAnulado = (float)($kpi['total_anulado'] ?? 0);
        $cantidadNeta = (int)($kpi['cantidad_neta'] ?? 0);
        $ticketPromedio = $cantidadNeta > 0 ? ($totalNeto / $cantidadNeta) : 0;

        if ($hasMetodoPago) {
            $queryMetodo = "SELECT v.metodo_pago AS metodo, COALESCE(SUM(v.total), 0) AS total
                            FROM ventas v
                            WHERE " . implode(' AND ', $ventasWhere) . "
                              AND " . ($hasEstadoVenta ? "v.estado <> 'anulada'" : "1=1") . "
                            GROUP BY v.metodo_pago
                            ORDER BY total DESC";
            $stmtMetodo = $this->db->prepare($queryMetodo);
            foreach ($ventasParams as $k => $v) {
                $stmtMetodo->bindValue($k, $v);
            }
            $stmtMetodo->execute();
            $metodos = $stmtMetodo->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $metodos = [['metodo' => 'efectivo', 'total' => $totalNeto]];
        }

        $hasTurnoTable = false;
        try {
            $checkTurnos = $this->db->query("SHOW TABLES LIKE 'caja_turnos'");
            $hasTurnoTable = $checkTurnos && (bool)$checkTurnos->fetchColumn();
        } catch (Throwable $e) {
            $hasTurnoTable = false;
        }

        $turnos = [
            'abiertos' => 0,
            'cerrados' => 0,
            'diferencia_acumulada' => 0.0
        ];
        if ($hasTurnoTable) {
            $queryTurnos = "SELECT
                                SUM(CASE WHEN t.estado = 'abierto' THEN 1 ELSE 0 END) AS abiertos,
                                SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) AS cerrados,
                                COALESCE(SUM(CASE WHEN t.estado = 'cerrado' THEN t.diferencia ELSE 0 END), 0) AS diferencia_acumulada
                            FROM caja_turnos t
                            WHERE DATE(t.opened_at) >= :date_from
                              AND DATE(t.opened_at) <= :date_to";
            $stmtTurnos = $this->db->prepare($queryTurnos);
            $stmtTurnos->bindValue(':date_from', $dateFrom);
            $stmtTurnos->bindValue(':date_to', $dateTo);
            $stmtTurnos->execute();
            $t = $stmtTurnos->fetch(PDO::FETCH_ASSOC) ?: [];
            $turnos = [
                'abiertos' => (int)($t['abiertos'] ?? 0),
                'cerrados' => (int)($t['cerrados'] ?? 0),
                'diferencia_acumulada' => (float)($t['diferencia_acumulada'] ?? 0)
            ];
        }

        $queryTopProductos = "SELECT p.nombre, SUM(d.cantidad) AS unidades, SUM(d.cantidad * d.precio_unitario) AS total
                              FROM detalle_ventas d
                              INNER JOIN ventas v ON v.id = d.venta_id
                              INNER JOIN productos p ON p.id = d.producto_id
                              WHERE DATE(v.fecha) >= :date_from
                                AND DATE(v.fecha) <= :date_to
                                AND " . ($hasEstadoVenta ? "v.estado <> 'anulada'" : "1=1") . "
                              GROUP BY p.id, p.nombre
                              ORDER BY unidades DESC
                              LIMIT 5";
        $stmtTop = $this->db->prepare($queryTopProductos);
        $stmtTop->bindValue(':date_from', $dateFrom);
        $stmtTop->bindValue(':date_to', $dateTo);
        $stmtTop->execute();
        $topProductos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

        $ventasPorUsuario = [];
        if ($hasUsuarioVenta) {
            $queryUsuarios = "SELECT u.username, u.display_name, COUNT(*) AS cantidad, COALESCE(SUM(v.total), 0) AS total
                              FROM ventas v
                              INNER JOIN usuarios u ON u.id = v.usuario_id
                              WHERE DATE(v.fecha) >= :date_from
                                AND DATE(v.fecha) <= :date_to
                                AND " . ($hasEstadoVenta ? "v.estado <> 'anulada'" : "1=1") . "
                              GROUP BY u.id, u.username, u.display_name
                              ORDER BY total DESC
                              LIMIT 10";
            $stmtUsuarios = $this->db->prepare($queryUsuarios);
            $stmtUsuarios->bindValue(':date_from', $dateFrom);
            $stmtUsuarios->bindValue(':date_to', $dateTo);
            $stmtUsuarios->execute();
            $ventasPorUsuario = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
        }

        $querySerie = "SELECT
                         DATE(v.fecha) AS fecha,
                         COALESCE(SUM(CASE WHEN " . ($hasEstadoVenta ? "v.estado <> 'anulada'" : "1=1") . " THEN v.total ELSE 0 END), 0) AS neto,
                         COALESCE(SUM(CASE WHEN " . ($hasEstadoVenta ? "v.estado = 'anulada'" : "0=1") . " THEN v.total ELSE 0 END), 0) AS anulado
                       FROM ventas v
                       WHERE DATE(v.fecha) >= :date_from
                         AND DATE(v.fecha) <= :date_to
                       GROUP BY DATE(v.fecha)
                       ORDER BY DATE(v.fecha) ASC";
        $stmtSerie = $this->db->prepare($querySerie);
        $stmtSerie->bindValue(':date_from', $dateFrom);
        $stmtSerie->bindValue(':date_to', $dateTo);
        $stmtSerie->execute();
        $serieDiaria = $stmtSerie->fetchAll(PDO::FETCH_ASSOC);

        $result = [
            'ok' => true,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ],
            'kpi' => [
                'total_neto' => $totalNeto,
                'total_anulado' => $totalAnulado,
                'cantidad_neta' => $cantidadNeta,
                'ticket_promedio' => $ticketPromedio
            ],
            'metodos_pago' => $metodos,
            'turnos' => $turnos,
            'top_productos' => $topProductos,
            'ventas_por_usuario' => $ventasPorUsuario,
            'serie_diaria' => $serieDiaria,
            'cache' => [
                'hit' => false,
                'ttl' => $this->cacheTtlSeconds
            ],
            'meta' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'served_at' => date('Y-m-d H:i:s')
            ]
        ];

        if ($useCache) {
            $this->setCachedSummary((string)$cacheKey, $result);
        }

        return $result;
    }
}
