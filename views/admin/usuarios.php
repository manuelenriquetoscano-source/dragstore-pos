<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin(['admin']);
require_once __DIR__ . '/../../controllers/UsuarioController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new UsuarioController($db);
$user = currentUser();
$currentUserId = (int)($user['id'] ?? 0);

$flash = ['type' => '', 'message' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    if ($action === 'create_user') {
        $_POST['actor_user_id'] = $currentUserId;
        $_POST['actor_username'] = $user['username'] ?? null;
        $result = $controller->crearUsuario($_POST);
    } elseif ($action === 'change_password') {
        $_POST['actor_user_id'] = $currentUserId;
        $_POST['actor_username'] = $user['username'] ?? null;
        $result = $controller->cambiarPassword($_POST);
    } elseif ($action === 'toggle_active') {
        $_POST['actor_user_id'] = $currentUserId;
        $_POST['actor_username'] = $user['username'] ?? null;
        $result = $controller->cambiarEstado($_POST);
    } elseif ($action === 'change_role') {
        $_POST['actor_user_id'] = $currentUserId;
        $_POST['actor_username'] = $user['username'] ?? null;
        $result = $controller->cambiarRol($_POST);
    } else {
        $result = ['ok' => false, 'message' => 'Accion no valida.'];
    }

    $flash['type'] = $result['ok'] ? 'success' : 'error';
    $flash['message'] = $result['message'];
}

$usuarios = $controller->listarUsuarios();
$totalUsuarios = count($usuarios);
$totalActivos = 0;
foreach ($usuarios as $u) {
    if ((int)($u['activo'] ?? 0) === 1) {
        $totalActivos++;
    }
}
$totalInactivos = $totalUsuarios - $totalActivos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Usuarios - Drugstore POS</title>
    <style>
        :root {
            --glass: rgba(255, 255, 255, 0.78);
            --card: rgba(255, 255, 255, 0.68);
            --border: rgba(255, 255, 255, 0.52);
            --line: rgba(148, 163, 184, 0.35);
            --text: #1f2d3d;
            --muted: #475569;
            --primary: #1d4ed8;
            --primary-strong: #1e40af;
            --dark: #334155;
            --ok-bg: #dcfce7;
            --ok-text: #166534;
            --off-bg: #fee2e2;
            --off-text: #991b1b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            color: var(--text);
            min-height: 100vh;
            background:
                radial-gradient(circle at 16% 16%, rgba(52, 152, 219, 0.16), transparent 42%),
                radial-gradient(circle at 86% 10%, rgba(39, 174, 96, 0.14), transparent 36%),
                linear-gradient(145deg, #e9f2fb 0%, #f7fbff 46%, #edf6f1 100%);
            padding: 14px;
        }
        .wrap {
            max-width: 1240px;
            margin: 0 auto;
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 14px;
            box-shadow: 0 24px 44px -30px rgba(44, 62, 80, 0.5);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--line);
            padding-bottom: 12px;
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .subtitle {
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
        }
        .btn {
            border: 1px solid rgba(255, 255, 255, 0.42);
            background: var(--dark);
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -12px rgba(15, 23, 42, 0.75);
        }
        .btn.primary {
            background: var(--primary);
        }
        .btn.primary:hover {
            background: var(--primary-strong);
        }
        .btn.subtle {
            background: rgba(51, 65, 85, 0.88);
        }
        .stats {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }
        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 12px;
        }
        .stat-card .label {
            font-size: 12px;
            color: var(--muted);
        }
        .stat-card .value {
            margin-top: 3px;
            font-size: 24px;
            font-weight: 800;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
            min-width: 0;
        }
        h1 {
            margin: 0;
            font-size: 24px;
        }
        h2 {
            margin: 0 0 8px;
            font-size: 18px;
        }
        .card-head {
            margin-bottom: 10px;
        }
        .card-help {
            font-size: 12px;
            color: var(--muted);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }
        .field {
            min-width: 0;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 11px;
            font-size: 14px;
            margin-bottom: 0;
            background: rgba(255, 255, 255, 0.88);
        }
        input:focus, select:focus {
            outline: 2px solid rgba(29, 78, 216, 0.2);
            border-color: rgba(29, 78, 216, 0.55);
        }
        .flash {
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 700;
        }
        .flash.success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }
        .flash.error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        .policy {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
            margin-bottom: 10px;
        }
        .create-actions {
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid var(--line);
            padding: 6px;
            text-align: left;
            font-size: 12px;
            vertical-align: top;
            word-break: break-word;
        }
        th {
            background: rgba(51, 65, 85, 0.92);
            color: #fff;
            white-space: normal;
        }
        .table-wrap {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--line);
            max-width: 100%;
        }
        .small-form {
            display: flex;
            gap: 6px;
            align-items: stretch;
            flex-wrap: wrap;
        }
        .small-form input,
        .small-form select {
            margin-bottom: 0;
            min-width: 84px;
            flex: 1 1 100px;
            padding: 7px 8px;
            font-size: 12px;
        }
        .small-form .btn {
            flex: 0 0 auto;
            padding: 7px 9px;
            font-size: 12px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge.ok {
            background: var(--ok-bg);
            color: var(--ok-text);
            border: 1px solid #86efac;
        }
        .badge.off {
            background: var(--off-bg);
            color: var(--off-text);
            border: 1px solid #fca5a5;
        }
        .row-actions { min-width: 150px; }
        .toggle-btn {
            font-size: 11px;
            padding: 6px 8px;
        }
        @media (min-width: 980px) {
            body { padding: 24px; }
            .wrap { padding: 20px; }
            .stats {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .grid {
                grid-template-columns: 360px 1fr;
            }
            .form-grid.two {
                grid-template-columns: 1fr 1fr;
            }
            h1 { font-size: 30px; }
        }
        @media (max-width: 700px) {
            body {
                padding: 10px;
            }
            .wrap {
                padding: 10px;
                border-radius: 14px;
            }
            h1 {
                font-size: 21px;
            }
            .subtitle {
                font-size: 12px;
            }
            .actions {
                width: 100%;
                flex-direction: column;
            }
            .actions .btn {
                width: 100%;
            }
            .stats {
                grid-template-columns: 1fr 1fr;
            }
            .card {
                padding: 10px;
            }
            .form-grid.two {
                grid-template-columns: 1fr;
            }
            .table-wrap {
                overflow: visible;
                border: none;
            }
            table, thead, tbody, tr, th, td {
                display: block;
                width: 100%;
            }
            thead {
                display: none;
            }
            tr {
                background: rgba(255, 255, 255, 0.82);
                border: 1px solid var(--line);
                border-radius: 10px;
                margin-bottom: 8px;
                padding: 8px;
            }
            td {
                border: none;
                border-bottom: 1px dashed var(--line);
                padding: 6px 6px 6px 44%;
                position: relative;
                font-size: 12px;
                min-height: 30px;
            }
            td:last-child {
                border-bottom: none;
            }
            td::before {
                content: attr(data-label);
                position: absolute;
                left: 6px;
                top: 6px;
                width: 40%;
                font-weight: 700;
                color: var(--muted);
                white-space: normal;
            }
            .row-actions {
                min-width: 0;
            }
            .small-form {
                gap: 5px;
            }
            .small-form input,
            .small-form select,
            .small-form .btn,
            .row-actions .btn {
                width: 100%;
                flex: 1 1 100%;
            }
            .stats .stat-card:last-child {
                grid-column: 1 / -1;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <div>
                <h1>Gestion de Usuarios</h1>
                <div class="subtitle">Sesion: <strong><?php echo htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8'); ?></strong> (admin)</div>
            </div>
            <div class="actions">
                <a class="btn primary" href="/dragstore-pos/views/admin/audit.php">Auditoria</a>
                <a class="btn subtle" href="/dragstore-pos/index.php">Menu</a>
                <a class="btn" href="/dragstore-pos/logout.php">Salir</a>
            </div>
        </div>

        <?php if ($flash['message'] !== ''): ?>
            <div class="flash <?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="label">Usuarios totales</div>
                <div class="value"><?php echo (int)$totalUsuarios; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Activos</div>
                <div class="value"><?php echo (int)$totalActivos; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Inactivos</div>
                <div class="value"><?php echo (int)$totalInactivos; ?></div>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <div class="card-head">
                    <h2>Crear Usuario</h2>
                    <div class="card-help">Completa los datos del nuevo acceso al sistema.</div>
                </div>
                <form method="POST" action="usuarios.php">
                    <input type="hidden" name="action" value="create_user">

                    <div class="form-grid">
                        <div class="field">
                            <label for="username">Usuario</label>
                            <input id="username" name="username" type="text" required>
                        </div>

                        <div class="field">
                            <label for="display_name">Nombre visible</label>
                            <input id="display_name" name="display_name" type="text" required>
                        </div>

                        <div class="field">
                            <label for="role">Rol</label>
                            <select id="role" name="role" required>
                                <option value="caja">caja</option>
                                <option value="admin">admin</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid two">
                        <div class="field">
                            <label for="password">Contrasena</label>
                            <input id="password" name="password" type="password" minlength="8" required>
                        </div>

                        <div class="field">
                            <label for="password_confirm">Confirmar contrasena</label>
                            <input id="password_confirm" name="password_confirm" type="password" minlength="8" required>
                        </div>
                    </div>
                    <div class="policy">Politica: minimo 8 caracteres, una mayuscula y un numero.</div>

                    <div class="create-actions">
                        <button class="btn primary" type="submit">Crear usuario</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-head">
                    <h2>Usuarios Registrados</h2>
                    <div class="card-help">Gestion de rol, clave y estado por usuario.</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Activo</th>
                                <th>Cambiar rol</th>
                                <th>Cambiar contrasena</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $item): ?>
                                <tr>
                                    <td data-label="ID"><?php echo (int)$item['id']; ?></td>
                                    <td data-label="Usuario"><?php echo htmlspecialchars($item['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td data-label="Nombre"><?php echo htmlspecialchars($item['display_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td data-label="Rol"><?php echo htmlspecialchars($item['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td data-label="Activo">
                                        <?php if ((int)$item['activo'] === 1): ?>
                                            <span class="badge ok">Activo</span>
                                        <?php else: ?>
                                            <span class="badge off">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="row-actions" data-label="Cambiar rol">
                                        <form class="small-form" method="POST" action="usuarios.php">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$item['id']; ?>">
                                            <select name="role" required>
                                                <option value="admin" <?php echo $item['role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                                                <option value="caja" <?php echo $item['role'] === 'caja' ? 'selected' : ''; ?>>caja</option>
                                            </select>
                                            <button class="btn subtle" type="submit">Aplicar</button>
                                        </form>
                                    </td>
                                    <td class="row-actions" data-label="Cambiar contrasena">
                                        <form class="small-form" method="POST" action="usuarios.php">
                                            <input type="hidden" name="action" value="change_password">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$item['id']; ?>">
                                            <input type="password" name="new_password" placeholder="Nueva clave" minlength="8" required>
                                            <input type="password" name="new_password_confirm" placeholder="Confirmar" minlength="8" required>
                                            <button class="btn subtle" type="submit">Guardar</button>
                                        </form>
                                    </td>
                                    <td class="row-actions" data-label="Estado">
                                        <form method="POST" action="usuarios.php">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$item['id']; ?>">
                                            <input type="hidden" name="activo" value="<?php echo ((int)$item['activo'] === 1) ? '0' : '1'; ?>">
                                            <button class="btn toggle-btn <?php echo ((int)$item['activo'] === 1) ? '' : 'primary'; ?>" type="submit">
                                                <?php echo ((int)$item['activo'] === 1) ? 'Desactivar' : 'Activar'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
