<?php

class AuditLog
{
    private $conn;
    private $table_name = "audit_log";

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crear(?int $actorUserId, ?string $actorUsername, string $action, string $entityType, ?int $entityId, ?array $details = null): bool
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (actor_user_id, actor_username, action, entity_type, entity_id, details_json)
                  VALUES (:actor_user_id, :actor_username, :action, :entity_type, :entity_id, :details_json)";
        $stmt = $this->conn->prepare($query);

        $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

        return $stmt->execute([
            ':actor_user_id' => $actorUserId,
            ':actor_username' => $actorUsername,
            ':action' => $action,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':details_json' => $detailsJson
        ]);
    }

    private function buildFilterParts(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['actor_username'])) {
            $where[] = "actor_username LIKE :actor_username";
            $params[':actor_username'] = '%' . $filters['actor_username'] . '%';
        }
        if (!empty($filters['action'])) {
            $where[] = "action = :action";
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['entity_type'])) {
            $where[] = "entity_type = :entity_type";
            $params[':entity_type'] = $filters['entity_type'];
        }
        if (isset($filters['entity_id']) && $filters['entity_id'] !== '') {
            $where[] = "entity_id = :entity_id";
            $params[':entity_id'] = (int)$filters['entity_id'];
        }

        return [$where, $params];
    }

    public function listar(array $filters = [], int $limit = 200, int $offset = 0, string $sortBy = 'id', string $sortDir = 'desc')
    {
        [$where, $params] = $this->buildFilterParts($filters);

        $allowedSort = [
            'id' => 'id',
            'created_at' => 'created_at',
            'actor_username' => 'actor_username',
            'action' => 'action',
            'entity_type' => 'entity_type',
            'entity_id' => 'entity_id'
        ];
        $column = $allowedSort[$sortBy] ?? 'id';
        $direction = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';

        $query = "SELECT id, actor_user_id, actor_username, action, entity_type, entity_id, details_json, created_at
                  FROM " . $this->table_name;
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        $query .= " ORDER BY {$column} {$direction}, id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, (int)$offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function contar(array $filters = []): int
    {
        [$where, $params] = $this->buildFilterParts($filters);

        $query = "SELECT COUNT(*) AS total FROM " . $this->table_name;
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function resumirPorAccion(array $filters = [])
    {
        [$where, $params] = $this->buildFilterParts($filters);

        $query = "SELECT action, COUNT(*) AS total
                  FROM " . $this->table_name;
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        $query .= " GROUP BY action ORDER BY total DESC";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt;
    }
}
