<?php

require_once __DIR__ . '/../services/PromocionService.php';

class PromocionController
{
    private $service;

    public function __construct(PDO $db)
    {
        $this->service = new PromocionService($db);
    }

    public function tableExists(): bool
    {
        return $this->service->tableExists();
    }

    public function listarTodas(): array
    {
        return $this->service->listarTodas();
    }

    public function obtenerPorId(int $id): ?array
    {
        return $this->service->obtenerPorId($id);
    }

    public function guardar(array $input): array
    {
        return $this->service->guardar($input);
    }

    public function setActivo(int $id, bool $activo): bool
    {
        return $this->service->setActivo($id, $activo);
    }

    public function eliminar(int $id): bool
    {
        return $this->service->eliminar($id);
    }

    public function listarActivasRuntime(): array
    {
        return $this->service->listarActivasRuntime();
    }
}
