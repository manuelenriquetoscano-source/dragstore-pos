<?php

require_once __DIR__ . '/../models/AuditLog.php';

class AuditService
{
    private $auditModel;

    public function __construct(PDO $db)
    {
        $this->auditModel = new AuditLog($db);
    }

    public function registrar(
        ?int $actorUserId,
        ?string $actorUsername,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $details = null
    ): void {
        try {
            $this->auditModel->crear($actorUserId, $actorUsername, $action, $entityType, $entityId, $details);
        } catch (Throwable $e) {
            appLog('warning', 'audit_log_write_failed', [
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function normalizeFilters(array $filters = []): array
    {
        $dateFrom = isset($filters['date_from']) ? trim((string)$filters['date_from']) : '';
        $dateTo = isset($filters['date_to']) ? trim((string)$filters['date_to']) : '';
        $actorUsername = isset($filters['actor_username']) ? trim((string)$filters['actor_username']) : '';
        $action = isset($filters['action']) ? trim((string)$filters['action']) : '';
        $entityType = isset($filters['entity_type']) ? trim((string)$filters['entity_type']) : '';
        $entityIdRaw = isset($filters['entity_id']) ? trim((string)$filters['entity_id']) : '';
        $sortBy = isset($filters['sort_by']) ? trim((string)$filters['sort_by']) : 'id';
        $sortDir = isset($filters['sort_dir']) ? strtolower(trim((string)$filters['sort_dir'])) : 'desc';

        if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = '';
        }
        if ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = '';
        }
        if ($entityType !== '' && !preg_match('/^[a-zA-Z0-9_.-]{1,80}$/', $entityType)) {
            $entityType = '';
        }

        $allowedSort = ['id', 'created_at', 'actor_username', 'action', 'entity_type', 'entity_id'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'id';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }
        $entityId = '';
        if ($entityIdRaw !== '' && ctype_digit($entityIdRaw)) {
            $entityId = (string)((int)$entityIdRaw);
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'actor_username' => $actorUsername,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir
        ];
    }

    private function hydrateRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['actor_user_id'] = $row['actor_user_id'] !== null ? (int)$row['actor_user_id'] : null;
            $row['entity_id'] = $row['entity_id'] !== null ? (int)$row['entity_id'] : null;
            $row['details'] = null;
            if (!empty($row['details_json'])) {
                $decoded = json_decode((string)$row['details_json'], true);
                if (is_array($decoded)) {
                    $row['details'] = $decoded;
                }
            }
        }
        unset($row);
        return $rows;
    }

    public function listar(array $filters = [], int $limit = 200): array
    {
        $normalized = $this->normalizeFilters($filters);
        $stmt = $this->auditModel->listar($normalized, $limit, 0, $normalized['sort_by'], $normalized['sort_dir']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->hydrateRows($rows);
    }

    public function listarPaginado(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $normalized = $this->normalizeFilters($filters);
        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));

        $total = $this->auditModel->contar($normalized);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $stmt = $this->auditModel->listar($normalized, $perPage, $offset, $normalized['sort_by'], $normalized['sort_dir']);
        $rows = $this->hydrateRows($stmt->fetchAll(PDO::FETCH_ASSOC));

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'filters' => $normalized
        ];
    }

    public function resumenActividad(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $stmt = $this->auditModel->resumirPorAccion($normalized);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resumen = [];
        $total = 0;
        foreach ($rows as $row) {
            $count = (int)($row['total'] ?? 0);
            $action = (string)($row['action'] ?? 'unknown');
            $resumen[] = [
                'action' => $action,
                'total' => $count
            ];
            $total += $count;
        }

        return [
            'items' => $resumen,
            'total' => $total
        ];
    }
}
