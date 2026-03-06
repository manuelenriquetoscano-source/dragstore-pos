<?php

require_once __DIR__ . '/../services/UsuarioService.php';

class UsuarioController
{
    private $service;

    public function __construct(PDO $db)
    {
        $this->service = new UsuarioService($db);
    }

    public function listarUsuarios(): array
    {
        return $this->service->listarUsuarios();
    }

    public function crearUsuario(array $request): array
    {
        return $this->service->crearUsuario($request);
    }

    public function cambiarPassword(array $request): array
    {
        return $this->service->cambiarPassword($request);
    }

    public function cambiarEstado(array $request): array
    {
        return $this->service->cambiarEstado($request);
    }

    public function cambiarRol(array $request): array
    {
        return $this->service->cambiarRol($request);
    }
}
