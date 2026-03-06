<?php

require_once __DIR__ . '/../services/AuditService.php';

class AuditController
{
    private $service;

    public function __construct(PDO $db)
    {
        $this->service = new AuditService($db);
    }

    public function listar(array $filters = [], int $limit = 200): array
    {
        return $this->service->listar($filters, $limit);
    }

    public function listarPaginado(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        return $this->service->listarPaginado($filters, $page, $perPage);
    }

    public function exportarCsv(array $filters = [], int $limit = 2000): void
    {
        $rows = $this->service->listar($filters, $limit);

        $filename = 'audit_log_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        // BOM UTF-8 for Excel compatibility.
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['id', 'created_at', 'actor_username', 'action', 'entity_type', 'entity_id', 'details_json'], ';');
        foreach ($rows as $row) {
            $details = $row['details'] ? json_encode($row['details'], JSON_UNESCAPED_UNICODE) : '';
            fputcsv($out, [
                $row['id'],
                $row['created_at'],
                $row['actor_username'] ?? '',
                $row['action'],
                $row['entity_type'],
                $row['entity_id'] ?? '',
                $details
            ], ';');
        }

        fclose($out);
        exit;
    }

    public function resumenActividad(array $filters = []): array
    {
        return $this->service->resumenActividad($filters);
    }
}
