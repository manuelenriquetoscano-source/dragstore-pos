<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin(['admin']);
require_once __DIR__ . '/../../controllers/AuditController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new AuditController($db);
$user = currentUser();

$filters = [
    'date_from' => isset($_GET['date_from']) ? (string)$_GET['date_from'] : '',
    'date_to' => isset($_GET['date_to']) ? (string)$_GET['date_to'] : '',
    'actor_username' => isset($_GET['actor_username']) ? (string)$_GET['actor_username'] : '',
    'action' => isset($_GET['action']) ? (string)$_GET['action'] : '',
    'entity_type' => isset($_GET['entity_type']) ? (string)$_GET['entity_type'] : '',
    'entity_id' => isset($_GET['entity_id']) ? (string)$_GET['entity_id'] : '',
    'sort_by' => isset($_GET['sort_by']) ? (string)$_GET['sort_by'] : 'id',
    'sort_dir' => isset($_GET['sort_dir']) ? (string)$_GET['sort_dir'] : 'desc'
];
$quickRange = isset($_GET['quick_range']) ? (string)$_GET['quick_range'] : '';

if ($quickRange !== '') {
    $today = date('Y-m-d');
    if ($quickRange === 'today') {
        $filters['date_from'] = $today;
        $filters['date_to'] = $today;
    } elseif ($quickRange === '7d') {
        $filters['date_from'] = date('Y-m-d', strtotime('-6 days'));
        $filters['date_to'] = $today;
    } elseif ($quickRange === '30d') {
        $filters['date_from'] = date('Y-m-d', strtotime('-29 days'));
        $filters['date_to'] = $today;
    }
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
$perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 50;

$exportCsv = isset($_GET['export']) && $_GET['export'] === 'csv';
if ($exportCsv) {
    $controller->exportarCsv($filters, 5000);
}

$result = $controller->listarPaginado($filters, $page, $perPage);
$rows = $result['rows'];
$page = $result['page'];
$perPage = $result['per_page'];
$totalPages = $result['total_pages'];
$totalRows = $result['total'];
$summary = $controller->resumenActividad($filters);

function auditQuery(array $filters, int $page, int $perPage): string
{
    $query = array_merge($filters, [
        'page' => $page,
        'per_page' => $perPage
    ]);
    return http_build_query($query);
}

function quickRangeQuery(array $filters, int $perPage, string $range): string
{
    $query = array_merge($filters, [
        'quick_range' => $range,
        'page' => 1,
        'per_page' => $perPage
    ]);
    return http_build_query($query);
}

function sortLink(array $filters, int $page, int $perPage, string $column): string
{
    $currentBy = $filters['sort_by'] ?? 'id';
    $currentDir = strtolower((string)($filters['sort_dir'] ?? 'desc'));
    $nextDir = ($currentBy === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $q = array_merge($filters, [
        'sort_by' => $column,
        'sort_dir' => $nextDir,
        'page' => $page,
        'per_page' => $perPage
    ]);
    return 'audit.php?' . http_build_query($q);
}

function sortMarker(array $filters, string $column): string
{
    if (($filters['sort_by'] ?? '') !== $column) {
        return '';
    }
    return strtolower((string)$filters['sort_dir']) === 'asc' ? ' (asc)' : ' (desc)';
}
function formatAuditDetails(?array $details): string
{
    if (empty($details)) {
        return '-';
    }
    $labels = [
        'ip' => 'IP',
        'role' => 'Rol',
        'method' => 'Metodo',
        'user_agent' => 'Navegador'
    ];
    $lines = [];
    foreach ($details as $key => $value) {
        $label = $labels[$key] ?? (string)$key;
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $lines[] = $label . ': ' . (string)$value;
    }
    return implode("\n", $lines);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria - Drugstore POS</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            color: #1f2d3d;
            padding: 14px;
            background:
                radial-gradient(circle at 16% 16%, rgba(52, 152, 219, 0.16), transparent 42%),
                radial-gradient(circle at 86% 10%, rgba(39, 174, 96, 0.14), transparent 36%),
                linear-gradient(145deg, #e9f2fb 0%, #f7fbff 46%, #edf6f1 100%);
        }
        .wrap {
            max-width: 1250px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.74);
            border: 1px solid rgba(255, 255, 255, 0.48);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 24px 44px -30px rgba(44, 62, 80, 0.5);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .top {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 12px;
        }
        .actions {
            display: flex;
            gap: 8px;
        }
        .btn {
            border: 1px solid rgba(255,255,255,0.42);
            background: rgba(44, 62, 80, 0.92);
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        h1 { margin: 0; font-size: 26px; }
        .filters {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 10px;
        }
        .quick-ranges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .filters input, .filters select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 14px;
        }
        .table-wrap { overflow-x: auto; border-radius: 10px; }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 10px;
        }
        .summary-card {
            background: rgba(255, 255, 255, 0.56);
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 10px;
            padding: 10px;
        }
        .summary-card .label {
            font-size: 12px;
            color: #64748b;
        }
        .summary-card .value {
            margin-top: 3px;
            font-size: 20px;
            font-weight: 800;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            border: 1px solid rgba(148,163,184,0.3);
            padding: 8px;
            text-align: left;
            white-space: nowrap;
            font-size: 13px;
            vertical-align: top;
        }
        th { background: rgba(44,62,80,0.9); color: #fff; }
        .muted { color: #64748b; }
        th .muted {
            color: #f8fafc;
            text-decoration: none;
            font-weight: 700;
        }
        .details {
            font-family: Consolas, monospace;
            white-space: pre-wrap;
            max-width: 420px;
            color: #334155;
        }
        .pager {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .summary {
            font-size: 13px;
            color: #475569;
        }
        .pager-actions {
            display: flex;
            gap: 8px;
        }
        .btn.subtle {
            background: rgba(51, 65, 85, 0.85);
        }
        .btn.active {
            background: #0f766e;
        }
        @media (min-width: 980px) {
            body { padding: 24px; }
            .wrap { padding: 20px; }
            .filters {
                grid-template-columns: 140px 140px 1fr 170px 160px 130px 100px auto auto auto;
                align-items: end;
            }
            .summary-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <div>
                <h1>Auditoria del Sistema</h1>
                <div class="muted">Sesion: <?php echo htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8'); ?> (admin)</div>
            </div>
            <div class="actions">
                <a class="btn" href="/dragstore-pos/views/admin/usuarios.php">Usuarios</a>
                <a class="btn" href="/dragstore-pos/index.php">Menu</a>
                <a class="btn" href="/dragstore-pos/logout.php">Salir</a>
            </div>
        </div>

        <div class="quick-ranges">
            <a class="btn subtle <?php echo $quickRange === 'today' ? 'active' : ''; ?>" href="audit.php?<?php echo htmlspecialchars(quickRangeQuery($filters, $perPage, 'today'), ENT_QUOTES, 'UTF-8'); ?>">Hoy</a>
            <a class="btn subtle <?php echo $quickRange === '7d' ? 'active' : ''; ?>" href="audit.php?<?php echo htmlspecialchars(quickRangeQuery($filters, $perPage, '7d'), ENT_QUOTES, 'UTF-8'); ?>">Ultimos 7 dias</a>
            <a class="btn subtle <?php echo $quickRange === '30d' ? 'active' : ''; ?>" href="audit.php?<?php echo htmlspecialchars(quickRangeQuery($filters, $perPage, '30d'), ENT_QUOTES, 'UTF-8'); ?>">Ultimos 30 dias</a>
        </div>

        <form class="filters" method="GET" action="audit.php">
            <div>
                <label>Desde</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div>
                <label>Hasta</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div>
                <label>Usuario</label>
                <input type="text" name="actor_username" placeholder="admin, caja..." value="<?php echo htmlspecialchars($filters['actor_username'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div>
                <label>Accion</label>
                <select name="action">
                    <option value="">Todas</option>
                    <?php
                    $actions = [
                        'auth.login',
                        'auth.logout',
                        'user.create',
                        'user.password_change',
                        'user.role_change',
                        'user.status_change'
                    ];
                    foreach ($actions as $action) {
                        $selected = ($filters['action'] === $action) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div>
                <label>ID Entidad (id del registro afectado)</label>
                <input type="number" min="1" name="entity_id" placeholder="Ej: 12" value="<?php echo htmlspecialchars($filters['entity_id'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div>
                <label>Tipo Entidad (ej: user, session)</label>
                <input type="text" name="entity_type" placeholder="user, venta..." value="<?php echo htmlspecialchars($filters['entity_type'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div>
                <label>Por pagina</label>
                <select name="per_page">
                    <option value="25" <?php echo $perPage === 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $perPage === 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>
            <button class="btn" type="submit">Filtrar</button>
            <a class="btn" href="audit.php">Limpiar</a>
            <button class="btn" type="submit" name="export" value="csv">Exportar CSV</button>
        </form>

        <div class="table-wrap">
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="label">Total eventos filtrados</div>
                    <div class="value"><?php echo (int)$summary['total']; ?></div>
                </div>
                <?php foreach ($summary['items'] as $item): ?>
                    <div class="summary-card">
                        <div class="label"><?php echo htmlspecialchars($item['action'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="value"><?php echo (int)$item['total']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <table>
                <thead>
                    <tr>
                        <th><a class="muted" href="<?php echo htmlspecialchars(sortLink($filters, $page, $perPage, 'id'), ENT_QUOTES, 'UTF-8'); ?>">ID<?php echo htmlspecialchars(sortMarker($filters, 'id'), ENT_QUOTES, 'UTF-8'); ?></a></th>
                        <th><a class="muted" href="<?php echo htmlspecialchars(sortLink($filters, $page, $perPage, 'created_at'), ENT_QUOTES, 'UTF-8'); ?>">Fecha<?php echo htmlspecialchars(sortMarker($filters, 'created_at'), ENT_QUOTES, 'UTF-8'); ?></a></th>
                        <th><a class="muted" href="<?php echo htmlspecialchars(sortLink($filters, $page, $perPage, 'actor_username'), ENT_QUOTES, 'UTF-8'); ?>">Actor<?php echo htmlspecialchars(sortMarker($filters, 'actor_username'), ENT_QUOTES, 'UTF-8'); ?></a></th>
                        <th><a class="muted" href="<?php echo htmlspecialchars(sortLink($filters, $page, $perPage, 'action'), ENT_QUOTES, 'UTF-8'); ?>">Accion<?php echo htmlspecialchars(sortMarker($filters, 'action'), ENT_QUOTES, 'UTF-8'); ?></a></th>
                        <th><a class="muted" href="<?php echo htmlspecialchars(sortLink($filters, $page, $perPage, 'entity_type'), ENT_QUOTES, 'UTF-8'); ?>">Entidad<?php echo htmlspecialchars(sortMarker($filters, 'entity_type'), ENT_QUOTES, 'UTF-8'); ?></a></th>
                        <th><a class="muted" href="<?php echo htmlspecialchars(sortLink($filters, $page, $perPage, 'entity_id'), ENT_QUOTES, 'UTF-8'); ?>">ID Entidad<?php echo htmlspecialchars(sortMarker($filters, 'entity_id'), ENT_QUOTES, 'UTF-8'); ?></a></th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="7">No hay registros para los filtros seleccionados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?php echo (int)$row['id']; ?></td>
                                <td><?php echo htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($row['actor_username'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)$row['action'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)$row['entity_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo $row['entity_id'] !== null ? (int)$row['entity_id'] : '-'; ?></td>
                                <td class="details"><?php echo htmlspecialchars(formatAuditDetails($row['details'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pager">
            <div class="summary">
                Total registros: <strong><?php echo (int)$totalRows; ?></strong> |
                Pagina <strong><?php echo (int)$page; ?></strong> de <strong><?php echo (int)$totalPages; ?></strong>
            </div>
            <div class="pager-actions">
                <?php if ($page > 1): ?>
                    <a class="btn" href="audit.php?<?php echo htmlspecialchars(auditQuery($filters, $page - 1, $perPage), ENT_QUOTES, 'UTF-8'); ?>">Anterior</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="btn" href="audit.php?<?php echo htmlspecialchars(auditQuery($filters, $page + 1, $perPage), ENT_QUOTES, 'UTF-8'); ?>">Siguiente</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>


