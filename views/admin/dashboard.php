<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin(['admin']);
require_once __DIR__ . '/../../controllers/DashboardController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new DashboardController($db);
$user = currentUser();
$cacheCleared = null;
$selfPath = '/dragstore-pos/views/admin/dashboard.php';

if (isset($_POST['clear_cache']) && (string)$_POST['clear_cache'] === '1') {
    $cacheCleared = $controller->clearCache();
}

$filters = [
    'date_from' => isset($_GET['date_from']) ? (string)$_GET['date_from'] : '',
    'date_to' => isset($_GET['date_to']) ? (string)$_GET['date_to'] : ''
];
if ($filters['date_from'] === '' && isset($_POST['date_from'])) {
    $filters['date_from'] = (string)$_POST['date_from'];
}
if ($filters['date_to'] === '' && isset($_POST['date_to'])) {
    $filters['date_to'] = (string)$_POST['date_to'];
}
if (isset($_GET['quick'])) {
    $quick = (string)$_GET['quick'];
    $todayDate = date('Y-m-d');
    if ($quick === 'today') {
        $filters['date_from'] = $todayDate;
        $filters['date_to'] = $todayDate;
    } elseif ($quick === '7d') {
        $filters['date_from'] = date('Y-m-d', strtotime('-6 days'));
        $filters['date_to'] = $todayDate;
    } elseif ($quick === '30d') {
        $filters['date_from'] = date('Y-m-d', strtotime('-29 days'));
        $filters['date_to'] = $todayDate;
    }
}
$filters['scope_user_id'] = (int)($user['id'] ?? 0);
$filters['scope_role'] = (string)($user['role'] ?? '');
$data = $controller->generarResumen($filters);
$f = $data['filters'];
$kpi = $data['kpi'];
$today = date('Y-m-d');
$dashboardQuery = http_build_query([
    'date_from' => $f['date_from'],
    'date_to' => $f['date_to']
]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ejecutivo - Drugstore POS</title>
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
            max-width: 1260px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.76);
            border: 1px solid rgba(255, 255, 255, 0.52);
            border-radius: 16px;
            padding: 16px;
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
        h1 { margin: 0; font-size: 28px; }
        .muted { color: #475569; font-size: 13px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .actions.secondary { margin-top: 8px; }
        .inline-form { margin: 0; }
        .btn {
            border: 1px solid rgba(255,255,255,0.45);
            background: rgba(44, 62, 80, 0.92);
            color: #fff;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        .btn.subtle {
            background: rgba(71, 85, 105, 0.9);
        }
        .quick-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .cache-badge {
            display: inline-block;
            margin-top: 6px;
            font-size: 12px;
            color: #334155;
            background: rgba(148, 163, 184, 0.2);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 999px;
            padding: 3px 8px;
        }
        .cache-cleared {
            display: inline-block;
            margin-top: 6px;
            margin-left: 8px;
            font-size: 12px;
            color: #065f46;
            background: rgba(16, 185, 129, 0.16);
            border: 1px solid rgba(16, 185, 129, 0.35);
            border-radius: 999px;
            padding: 3px 8px;
        }
        .panel-head {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }
        .panel-head h2 { margin: 0; font-size: 17px; }
        .panel-tools { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-sm {
            border-radius: 8px;
            font-size: 12px;
            padding: 6px 10px;
            font-weight: 700;
            background: rgba(51, 65, 85, 0.9);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.4);
            cursor: pointer;
        }
        .filters {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }
        .filters label { font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 3px; display: block; }
        .filters input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 14px;
            background: rgba(255,255,255,0.88);
        }
        .kpis {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }
        .kpi {
            background: rgba(255,255,255,0.58);
            border: 1px solid rgba(148,163,184,0.25);
            border-radius: 10px;
            padding: 10px;
        }
        .kpi .label { font-size: 12px; color: #64748b; }
        .kpi .value { margin-top: 3px; font-size: 22px; font-weight: 800; }
        .panels {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }
        .panel {
            background: rgba(255,255,255,0.56);
            border: 1px solid rgba(148,163,184,0.25);
            border-radius: 10px;
            padding: 10px;
        }
        .panel h2 { margin: 0 0 8px 0; font-size: 17px; }
        .chart-wrap {
            position: relative;
            width: 100%;
            min-height: 280px;
        }
        .chart-empty {
            font-size: 13px;
            color: #64748b;
            padding: 10px;
            border: 1px dashed rgba(148,163,184,0.45);
            border-radius: 8px;
            background: rgba(255,255,255,0.6);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            border: 1px solid rgba(148,163,184,0.3);
            padding: 8px;
            font-size: 13px;
            text-align: left;
            white-space: nowrap;
        }
        th { background: rgba(51,65,85,0.92); color: #fff; }
        .table-wrap { overflow-x: auto; border-radius: 8px; }
        @media (min-width: 980px) {
            body { padding: 24px; }
            .wrap { padding: 20px; }
            .filters {
                grid-template-columns: 160px 160px auto auto auto auto;
                align-items: end;
            }
            .kpis {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
            .panels {
                grid-template-columns: 1fr 1fr;
            }
            .panel.full {
                grid-column: 1 / -1;
            }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div>
            <h1>Dashboard Ejecutivo</h1>
            <div class="muted">Usuario: <?php echo htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8'); ?> (admin)</div>
        </div>
        <div class="actions">
            <a class="btn" href="/dragstore-pos/views/ventas/reporte.php">Reporte</a>
            <a class="btn" href="/dragstore-pos/views/ventas/historial_turnos.php">Turnos</a>
            <a class="btn" href="/dragstore-pos/index.php">Menu</a>
        </div>
        <div class="actions secondary">
            <a class="btn subtle" href="dashboard_export.php?format=csv&<?php echo htmlspecialchars($dashboardQuery, ENT_QUOTES, 'UTF-8'); ?>">Exportar KPI CSV</a>
            <a class="btn subtle" target="_blank" rel="noopener" href="dashboard_export.php?format=pdf&<?php echo htmlspecialchars($dashboardQuery, ENT_QUOTES, 'UTF-8'); ?>">Exportar KPI PDF</a>
            <form class="inline-form" method="POST" action="<?php echo htmlspecialchars($selfPath, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($f['date_from'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($f['date_to'], ENT_QUOTES, 'UTF-8'); ?>">
                <button class="btn subtle" type="submit" name="clear_cache" value="1">Limpiar cache</button>
            </form>
        </div>
    </div>

    <form class="filters" method="GET" action="dashboard.php">
        <div>
            <label>Desde</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($f['date_from'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div>
            <label>Hasta</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($f['date_to'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <button class="btn" type="submit">Aplicar</button>
        <button class="btn" type="submit" name="quick" value="today">Hoy</button>
        <button class="btn" type="submit" name="quick" value="7d">7 dias</button>
        <button class="btn" type="submit" name="quick" value="30d">30 dias</button>
    </form>
    <div class="cache-badge">
        Cache: <?php echo !empty($data['cache']['hit']) ? 'HIT' : 'MISS'; ?> (TTL <?php echo (int)($data['cache']['ttl'] ?? 0); ?>s)
    </div>
    <div class="cache-badge">
        Ult. actualizacion: <?php echo htmlspecialchars((string)($data['meta']['generated_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
        | Servido: <?php echo htmlspecialchars((string)($data['meta']['served_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php if ($cacheCleared !== null): ?>
        <div class="cache-cleared">Cache limpiada: <?php echo $cacheCleared; ?> archivo(s)</div>
    <?php endif; ?>

    <div class="kpis">
        <div class="kpi"><div class="label">Ventas Netas</div><div class="value">$<?php echo number_format((float)$kpi['total_neto'], 2, '.', ''); ?></div></div>
        <div class="kpi"><div class="label">Ventas Anuladas</div><div class="value">$<?php echo number_format((float)$kpi['total_anulado'], 2, '.', ''); ?></div></div>
        <div class="kpi"><div class="label">Cantidad Ventas</div><div class="value"><?php echo (int)$kpi['cantidad_neta']; ?></div></div>
        <div class="kpi"><div class="label">Ticket Promedio</div><div class="value">$<?php echo number_format((float)$kpi['ticket_promedio'], 2, '.', ''); ?></div></div>
        <div class="kpi"><div class="label">Turnos Abiertos</div><div class="value"><?php echo (int)$data['turnos']['abiertos']; ?></div></div>
        <div class="kpi"><div class="label">Dif. Turnos</div><div class="value">$<?php echo number_format((float)$data['turnos']['diferencia_acumulada'], 2, '.', ''); ?></div></div>
    </div>

    <div class="panels">
        <div class="panel">
            <div class="panel-head">
                <h2>Metodos de Pago</h2>
                <div class="panel-tools">
                    <button type="button" class="btn-sm" id="btn-png-metodos">PNG</button>
                </div>
            </div>
            <div class="chart-wrap" style="margin-bottom: 10px;">
                <canvas id="chartMetodos"></canvas>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Metodo</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php if (empty($data['metodos_pago'])): ?>
                        <tr><td colspan="2">Sin datos</td></tr>
                    <?php else: ?>
                        <?php foreach ($data['metodos_pago'] as $m): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)$m['metodo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>$<?php echo number_format((float)$m['total'], 2, '.', ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <h2>Top Productos</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Producto</th><th>Unidades</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php if (empty($data['top_productos'])): ?>
                        <tr><td colspan="3">Sin datos</td></tr>
                    <?php else: ?>
                        <?php foreach ($data['top_productos'] as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)$p['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int)$p['unidades']; ?></td>
                                <td>$<?php echo number_format((float)$p['total'], 2, '.', ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel full">
            <div class="panel-head">
                <h2>Evolucion Diaria</h2>
                <div class="panel-tools">
                    <button type="button" class="btn-sm" id="btn-png-serie">PNG</button>
                </div>
            </div>
            <div class="chart-wrap">
                <canvas id="chartSerie"></canvas>
            </div>
        </div>

        <div class="panel full">
            <h2>Ventas por Usuario</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Usuario</th><th>Cantidad</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php if (empty($data['ventas_por_usuario'])): ?>
                        <tr><td colspan="3">Sin datos</td></tr>
                    <?php else: ?>
                        <?php foreach ($data['ventas_por_usuario'] as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(($u['username'] ?? '-') . ' - ' . ($u['display_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int)$u['cantidad']; ?></td>
                                <td>$<?php echo number_format((float)$u['total'], 2, '.', ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const serieDiaria = <?php echo json_encode($data['serie_diaria'], JSON_UNESCAPED_UNICODE); ?>;
const metodosPago = <?php echo json_encode($data['metodos_pago'], JSON_UNESCAPED_UNICODE); ?>;
let chartMetodosRef = null;
let chartSerieRef = null;

function renderCharts() {
    if (typeof Chart === 'undefined') {
        document.querySelectorAll('.chart-wrap').forEach(function(el) {
            el.innerHTML = '<div class="chart-empty">No se pudo cargar la libreria de graficos.</div>';
        });
        return;
    }

    const metodosLabels = metodosPago.map(item => String(item.metodo || 'N/A'));
    const metodosData = metodosPago.map(item => Number(item.total || 0));
    const ctxMetodos = document.getElementById('chartMetodos');
    if (ctxMetodos) {
        chartMetodosRef = new Chart(ctxMetodos, {
            type: 'bar',
            data: {
                labels: metodosLabels,
                datasets: [{
                    label: 'Total por metodo',
                    data: metodosData,
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.55)',
                        'rgba(59, 130, 246, 0.55)',
                        'rgba(245, 158, 11, 0.55)',
                        'rgba(168, 85, 247, 0.55)'
                    ],
                    borderColor: [
                        'rgba(5, 150, 105, 1)',
                        'rgba(37, 99, 235, 1)',
                        'rgba(217, 119, 6, 1)',
                        'rgba(147, 51, 234, 1)'
                    ],
                    borderWidth: 1.2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    const serieLabels = serieDiaria.map(item => String(item.fecha || ''));
    const serieNeto = serieDiaria.map(item => Number(item.neto || 0));
    const serieAnulado = serieDiaria.map(item => Number(item.anulado || 0));
    const ctxSerie = document.getElementById('chartSerie');
    if (ctxSerie) {
        chartSerieRef = new Chart(ctxSerie, {
            type: 'line',
            data: {
                labels: serieLabels,
                datasets: [
                    {
                        label: 'Neto',
                        data: serieNeto,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.15)',
                        tension: 0.35,
                        pointRadius: 3,
                        fill: true
                    },
                    {
                        label: 'Anulado',
                        data: serieAnulado,
                        borderColor: 'rgba(239, 68, 68, 1)',
                        backgroundColor: 'rgba(239, 68, 68, 0.12)',
                        tension: 0.35,
                        pointRadius: 3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
}

function downloadChartPng(chart, filename) {
    if (!chart || typeof chart.toBase64Image !== 'function') {
        alert('No hay grafico para exportar.');
        return;
    }
    const dataUrl = chart.toBase64Image('image/png', 1);
    const link = document.createElement('a');
    link.href = dataUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

renderCharts();
const btnPngMetodos = document.getElementById('btn-png-metodos');
if (btnPngMetodos) {
    btnPngMetodos.addEventListener('click', function () {
        downloadChartPng(chartMetodosRef, 'dashboard_metodos.png');
    });
}
const btnPngSerie = document.getElementById('btn-png-serie');
if (btnPngSerie) {
    btnPngSerie.addEventListener('click', function () {
        downloadChartPng(chartSerieRef, 'dashboard_evolucion.png');
    });
}
</script>
</body>
</html>
