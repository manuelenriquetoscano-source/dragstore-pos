<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin(['admin', 'caja']);
require_once __DIR__ . '/../../controllers/TurnoCajaController.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../services/DashboardService.php';

$database = new Database();
$db = $database->getConnection();
$controller = new TurnoCajaController($db);
$user = currentUser();
$isAdmin = (($user['role'] ?? '') === 'admin');
$usuarioActualId = (int)($user['id'] ?? 0);
$cacheCleared = null;

$filters = [
    'date_from' => isset($_GET['date_from']) ? (string)$_GET['date_from'] : '',
    'date_to' => isset($_GET['date_to']) ? (string)$_GET['date_to'] : '',
    'estado' => isset($_GET['estado']) ? (string)$_GET['estado'] : '',
    'usuario_id' => isset($_GET['usuario_id']) ? (string)$_GET['usuario_id'] : ''
];
if (isset($_POST['clear_cache']) && (string)$_POST['clear_cache'] === '1' && $isAdmin) {
    $cacheCleared = DashboardService::clearCacheGlobal();
}
if ($filters['date_from'] === '' && isset($_POST['date_from'])) {
    $filters['date_from'] = (string)$_POST['date_from'];
}
if ($filters['date_to'] === '' && isset($_POST['date_to'])) {
    $filters['date_to'] = (string)$_POST['date_to'];
}
if ($filters['estado'] === '' && isset($_POST['estado'])) {
    $filters['estado'] = (string)$_POST['estado'];
}
if ($filters['usuario_id'] === '' && isset($_POST['usuario_id'])) {
    $filters['usuario_id'] = (string)$_POST['usuario_id'];
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
if (!isset($_GET['per_page']) && isset($_POST['per_page'])) {
    $perPage = (int)$_POST['per_page'];
}
$perPage = in_array($perPage, [20, 50, 100], true) ? $perPage : 20;

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvData = $controller->exportarCsv($filters, $isAdmin, $usuarioActualId, 5000);
    if (!$csvData['ok']) {
        http_response_code(422);
        echo 'No se pudo exportar CSV.';
        exit;
    }

    $filename = 'historial_turnos_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['turno_id', 'usuario_id', 'username', 'display_name', 'opened_at', 'closed_at', 'estado', 'monto_inicial', 'total_ventas', 'cantidad_ventas', 'monto_final_declarado', 'diferencia'], ';');
    foreach ($csvData['items'] as $row) {
        fputcsv($out, [
            $row['id'] ?? '',
            $row['usuario_id'] ?? '',
            $row['username'] ?? '',
            $row['display_name'] ?? '',
            $row['opened_at'] ?? '',
            $row['closed_at'] ?? '',
            $row['estado'] ?? '',
            $row['monto_inicial'] ?? '',
            $row['total_ventas'] ?? '',
            $row['cantidad_ventas'] ?? '',
            $row['monto_final_declarado'] ?? '',
            $row['diferencia'] ?? ''
        ], ';');
    }
    fclose($out);
    exit;
}

$result = $controller->listarPaginado($filters, $isAdmin, $usuarioActualId, $page, $perPage);
$items = $result['items'] ?? [];
$filters = $result['filters'] ?? $filters;
$page = (int)($result['page'] ?? 1);
$totalPages = (int)($result['total_pages'] ?? 1);
$totalRows = (int)($result['total'] ?? 0);

$usuarios = [];
if ($isAdmin) {
    $usuarioModel = new Usuario($db);
    $usuarios = $usuarioModel->listarTodos()->fetchAll(PDO::FETCH_ASSOC);
}

function turnosQuery(array $filters, int $page, int $perPage): string
{
    return http_build_query(array_merge($filters, ['page' => $page, 'per_page' => $perPage]));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Turnos - Drugstore POS</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            color: #1f2d3d;
            min-height: 100vh;
            padding: 14px;
            background:
                radial-gradient(circle at 16% 16%, rgba(52, 152, 219, 0.16), transparent 42%),
                radial-gradient(circle at 86% 10%, rgba(39, 174, 96, 0.14), transparent 36%),
                linear-gradient(145deg, #e9f2fb 0%, #f7fbff 46%, #edf6f1 100%);
        }
        .wrap {
            max-width: 1220px;
            margin: 0 auto;
            background: rgba(255,255,255,0.78);
            border: 1px solid rgba(255,255,255,0.52);
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 24px 44px -30px rgba(44, 62, 80, 0.5);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        h1 { margin: 0; font-size: 26px; }
        .muted { color: #475569; font-size: 13px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .inline-form { margin: 0; }
        .btn {
            border: 1px solid rgba(255,255,255,0.45);
            background: rgba(44, 62, 80, 0.92);
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        .btn.secondary { background: rgba(30, 64, 175, 0.92); }
        .filters {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 10px;
        }
        .filters label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #334155;
        }
        .filters input, .filters select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 14px;
            background: rgba(255,255,255,0.86);
        }
        .table-wrap {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.35);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 8px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            text-align: left;
            font-size: 13px;
            white-space: nowrap;
        }
        th {
            background: rgba(51, 65, 85, 0.92);
            color: #fff;
        }
        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 800;
        }
        .badge.open { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge.closed { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .pager {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .cache-cleared {
            display: inline-block;
            margin-bottom: 10px;
            font-size: 12px;
            color: #065f46;
            background: rgba(16, 185, 129, 0.16);
            border: 1px solid rgba(16, 185, 129, 0.35);
            border-radius: 999px;
            padding: 3px 8px;
        }
        @media (min-width: 980px) {
            body { padding: 24px; }
            .wrap { padding: 18px; }
            .filters {
                grid-template-columns: 150px 150px 170px 1fr 100px auto auto auto;
                align-items: end;
            }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div>
            <h1>Historial de Turnos</h1>
            <div class="muted">
                Usuario: <?php echo htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8'); ?>
                (<?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>)
            </div>
        </div>
        <div class="actions">
            <a class="btn secondary" href="/dragstore-pos/views/ventas/caja.php">Caja</a>
            <a class="btn" href="/dragstore-pos/index.php">Menu</a>
            <a class="btn" href="/dragstore-pos/logout.php">Salir</a>
            <?php if ($isAdmin): ?>
                <form class="inline-form" method="POST" action="/dragstore-pos/views/ventas/historial_turnos.php">
                    <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="estado" value="<?php echo htmlspecialchars($filters['estado'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($filters['usuario_id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="per_page" value="<?php echo (int)$perPage; ?>">
                    <button class="btn" type="submit" name="clear_cache" value="1">Limpiar cache</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($cacheCleared !== null): ?>
        <div class="cache-cleared">Cache limpiada: <?php echo (int)$cacheCleared; ?> archivo(s)</div>
    <?php endif; ?>

    <form class="filters" method="GET" action="historial_turnos.php">
        <div>
            <label>Desde</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div>
            <label>Hasta</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div>
            <label>Estado</label>
            <select name="estado">
                <option value="" <?php echo $filters['estado'] === '' ? 'selected' : ''; ?>>Todos</option>
                <option value="abierto" <?php echo $filters['estado'] === 'abierto' ? 'selected' : ''; ?>>Abierto</option>
                <option value="cerrado" <?php echo $filters['estado'] === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
            </select>
        </div>
        <div>
            <label>Usuario</label>
            <?php if ($isAdmin): ?>
                <select name="usuario_id">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo (int)$u['id']; ?>" <?php echo (string)$filters['usuario_id'] === (string)$u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['username'] . ' - ' . $u['display_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="text" value="<?php echo htmlspecialchars($user['username'] . ' - ' . $user['display_name'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
            <?php endif; ?>
        </div>
        <div>
            <label>Por pagina</label>
            <select name="per_page">
                <option value="20" <?php echo $perPage === 20 ? 'selected' : ''; ?>>20</option>
                <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $perPage === 100 ? 'selected' : ''; ?>>100</option>
            </select>
        </div>
        <button class="btn" type="submit">Filtrar</button>
        <a class="btn" href="historial_turnos.php">Limpiar</a>
        <button class="btn" type="submit" name="export" value="csv">Exportar CSV</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Apertura</th>
                <th>Cierre</th>
                <th>Estado</th>
                <th>Inicial</th>
                <th>Ventas</th>
                <th>Cantidad</th>
                <th>Final</th>
                <th>Diferencia</th>
                <th>Acta</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="11">No hay turnos para los filtros seleccionados.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $row): ?>
                    <tr>
                        <td><?php echo (int)$row['id']; ?></td>
                        <td><?php echo htmlspecialchars(($row['username'] ?? '-') . ' - ' . ($row['display_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['opened_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['closed_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if (($row['estado'] ?? '') === 'abierto'): ?>
                                <span class="badge open">Abierto</span>
                            <?php else: ?>
                                <span class="badge closed">Cerrado</span>
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo number_format((float)($row['monto_inicial'] ?? 0), 2, '.', ''); ?></td>
                        <td>$<?php echo number_format((float)($row['total_ventas'] ?? 0), 2, '.', ''); ?></td>
                        <td><?php echo (int)($row['cantidad_ventas'] ?? 0); ?></td>
                        <td><?php echo $row['monto_final_declarado'] !== null ? ('$' . number_format((float)$row['monto_final_declarado'], 2, '.', '')) : '-'; ?></td>
                        <td><?php echo $row['diferencia'] !== null ? ('$' . number_format((float)$row['diferencia'], 2, '.', '')) : '-'; ?></td>
                        <td>
                            <?php if (($row['estado'] ?? '') === 'cerrado'): ?>
                                <a class="btn" href="/dragstore-pos/views/ventas/acta_turno.php?id=<?php echo (int)$row['id']; ?>" target="_blank" rel="noopener">Ver/Imprimir</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pager">
        <div class="muted">
            Total turnos: <strong><?php echo $totalRows; ?></strong> |
            Pagina <strong><?php echo $page; ?></strong> de <strong><?php echo $totalPages; ?></strong>
        </div>
        <div class="actions">
            <?php if ($page > 1): ?>
                <a class="btn" href="historial_turnos.php?<?php echo htmlspecialchars(turnosQuery($filters, $page - 1, $perPage), ENT_QUOTES, 'UTF-8'); ?>">Anterior</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a class="btn" href="historial_turnos.php?<?php echo htmlspecialchars(turnosQuery($filters, $page + 1, $perPage), ENT_QUOTES, 'UTF-8'); ?>">Siguiente</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
