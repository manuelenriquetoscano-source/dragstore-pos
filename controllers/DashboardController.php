<?php

require_once __DIR__ . '/../services/DashboardService.php';

class DashboardController
{
    private $service;

    public function __construct(PDO $db)
    {
        $this->service = new DashboardService($db);
    }

    public function generarResumen(array $filters = [], bool $useCache = true): array
    {
        return $this->service->generarResumen($filters, $useCache);
    }

    public function clearCache(): int
    {
        return $this->service->clearCache();
    }
}
