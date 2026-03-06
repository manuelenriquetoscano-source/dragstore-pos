<?php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/AuditService.php';

class UsuarioService
{
    private $usuarioModel;
    private $auditService;

    public function __construct(PDO $db)
    {
        $this->usuarioModel = new Usuario($db);
        $this->auditService = new AuditService($db);
    }

    public function listarUsuarios(): array
    {
        $stmt = $this->usuarioModel->listarTodos();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearUsuario(array $input): array
    {
        $username = trim((string)($input['username'] ?? ''));
        $displayName = trim((string)($input['display_name'] ?? ''));
        $role = trim((string)($input['role'] ?? ''));
        $password = (string)($input['password'] ?? '');
        $passwordConfirm = (string)($input['password_confirm'] ?? '');

        $actorUserId = isset($input['actor_user_id']) ? (int)$input['actor_user_id'] : null;
        $actorUsername = isset($input['actor_username']) ? trim((string)$input['actor_username']) : null;

        if ($username === '' || $displayName === '' || $role === '' || $password === '') {
            return ['ok' => false, 'message' => 'Completa todos los campos obligatorios.'];
        }
        if (!in_array($role, ['admin', 'caja'], true)) {
            return ['ok' => false, 'message' => 'Rol inválido.'];
        }
        if ($password !== $passwordConfirm) {
            return ['ok' => false, 'message' => 'Las contraseñas no coinciden.'];
        }
        $policyError = $this->validatePasswordPolicy($password);
        if ($policyError !== null) {
            return ['ok' => false, 'message' => $policyError];
        }

        $exists = $this->usuarioModel->buscarPorUsername($username);
        if ($exists) {
            return ['ok' => false, 'message' => 'El nombre de usuario ya existe.'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $ok = $this->usuarioModel->crear($username, $passwordHash, $role, $displayName, 1);
        if (!$ok) {
            return ['ok' => false, 'message' => 'No se pudo crear el usuario.'];
        }

        $newUser = $this->usuarioModel->buscarPorUsername($username);
        $this->auditService->registrar(
            $actorUserId,
            $actorUsername,
            'user.create',
            'usuario',
            isset($newUser['id']) ? (int)$newUser['id'] : null,
            [
                'username' => $username,
                'role' => $role,
                'display_name' => $displayName
            ]
        );

        return ['ok' => true, 'message' => 'Usuario creado correctamente.'];
    }

    public function cambiarPassword(array $input): array
    {
        $userId = (int)($input['user_id'] ?? 0);
        $password = (string)($input['new_password'] ?? '');
        $passwordConfirm = (string)($input['new_password_confirm'] ?? '');
        $actorUserId = isset($input['actor_user_id']) ? (int)$input['actor_user_id'] : null;
        $actorUsername = isset($input['actor_username']) ? trim((string)$input['actor_username']) : null;

        if ($userId <= 0 || $password === '') {
            return ['ok' => false, 'message' => 'Datos inválidos para cambio de contraseña.'];
        }
        if ($password !== $passwordConfirm) {
            return ['ok' => false, 'message' => 'Las contraseñas no coinciden.'];
        }
        $policyError = $this->validatePasswordPolicy($password);
        if ($policyError !== null) {
            return ['ok' => false, 'message' => $policyError];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $ok = $this->usuarioModel->actualizarPassword($userId, $passwordHash);
        if (!$ok) {
            return ['ok' => false, 'message' => 'No se pudo actualizar la contraseña.'];
        }

        $targetUser = $this->usuarioModel->buscarPorId($userId);
        $this->auditService->registrar(
            $actorUserId,
            $actorUsername,
            'user.password_change',
            'usuario',
            $userId,
            [
                'target_username' => $targetUser['username'] ?? null
            ]
        );

        return ['ok' => true, 'message' => 'Contraseña actualizada correctamente.'];
    }

    public function cambiarEstado(array $input): array
    {
        $userId = (int)($input['user_id'] ?? 0);
        $activo = (int)($input['activo'] ?? 0);
        $actorUserId = (int)($input['actor_user_id'] ?? 0);
        $actorUsername = isset($input['actor_username']) ? trim((string)$input['actor_username']) : null;

        if ($userId <= 0 || !in_array($activo, [0, 1], true)) {
            return ['ok' => false, 'message' => 'Solicitud inválida para cambio de estado.'];
        }
        if ($actorUserId > 0 && $actorUserId === $userId && $activo === 0) {
            return ['ok' => false, 'message' => 'No puedes desactivar tu propio usuario.'];
        }

        $targetUser = $this->usuarioModel->buscarPorId($userId);
        if (!$targetUser) {
            return ['ok' => false, 'message' => 'Usuario no encontrado.'];
        }

        if ($targetUser['role'] === 'admin' && $activo === 0) {
            $adminsActivos = $this->usuarioModel->contarAdminsActivos();
            if ($adminsActivos <= 1 && (int)$targetUser['activo'] === 1) {
                return ['ok' => false, 'message' => 'Debe existir al menos un admin activo.'];
            }
        }

        $ok = $this->usuarioModel->actualizarEstado($userId, $activo);
        if (!$ok) {
            return ['ok' => false, 'message' => 'No se pudo actualizar el estado del usuario.'];
        }

        $this->auditService->registrar(
            $actorUserId > 0 ? $actorUserId : null,
            $actorUsername,
            'user.status_change',
            'usuario',
            $userId,
            [
                'new_status' => $activo === 1 ? 'activo' : 'inactivo',
                'target_username' => $targetUser['username'] ?? null
            ]
        );

        return ['ok' => true, 'message' => 'Estado de usuario actualizado.'];
    }

    public function cambiarRol(array $input): array
    {
        $userId = (int)($input['user_id'] ?? 0);
        $role = trim((string)($input['role'] ?? ''));
        $actorUserId = (int)($input['actor_user_id'] ?? 0);
        $actorUsername = isset($input['actor_username']) ? trim((string)$input['actor_username']) : null;

        if ($userId <= 0 || !in_array($role, ['admin', 'caja'], true)) {
            return ['ok' => false, 'message' => 'Solicitud inválida para cambio de rol.'];
        }

        $targetUser = $this->usuarioModel->buscarPorId($userId);
        if (!$targetUser) {
            return ['ok' => false, 'message' => 'Usuario no encontrado.'];
        }

        if ($actorUserId > 0 && $actorUserId === $userId && $role !== 'admin') {
            return ['ok' => false, 'message' => 'No puedes quitarte el rol admin a ti mismo.'];
        }

        if ($targetUser['role'] === 'admin' && $role !== 'admin' && (int)$targetUser['activo'] === 1) {
            $adminsActivos = $this->usuarioModel->contarAdminsActivos();
            if ($adminsActivos <= 1) {
                return ['ok' => false, 'message' => 'Debe existir al menos un admin activo.'];
            }
        }

        $ok = $this->usuarioModel->actualizarRol($userId, $role);
        if (!$ok) {
            return ['ok' => false, 'message' => 'No se pudo actualizar el rol del usuario.'];
        }

        $this->auditService->registrar(
            $actorUserId > 0 ? $actorUserId : null,
            $actorUsername,
            'user.role_change',
            'usuario',
            $userId,
            [
                'new_role' => $role,
                'target_username' => $targetUser['username'] ?? null
            ]
        );

        return ['ok' => true, 'message' => 'Rol actualizado correctamente.'];
    }

    private function validatePasswordPolicy(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'La contraseña debe incluir al menos una letra mayúscula.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'La contraseña debe incluir al menos un número.';
        }
        return null;
    }
}
