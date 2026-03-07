<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin(['admin']);
require_once __DIR__ . '/../../controllers/DashboardController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new DashboardController($db);

$filters = [
    'date_from' => isset($_GET['date_from']) ? (string)$_GET['date_from'] : '',
    'date_to' => isset($_GET['date_to']) ? (string)$_GET['date_to'] : ''
];

if (isset($_GET['quick'])) {
    $quick = (string)$_GET['quick'];
    $today = date('Y-m-d');
    if ($quick === 'today') {
        $filters['date_from'] = $today;
        $filters['date_to'] = $today;
    } elseif ($quick === '7d') {
        $filters['date_from'] = date('Y-m-d', strtotime('-6 days'));
        $filters['date_to'] = $today;
    } elseif ($quick === '30d') {
        $filters['date_from'] = date('Y-m-d', strtotime('-29 days'));
        $filters['date_to'] = $today;
    }
}

$user = currentUser();
$filters['scope_user_id'] = (int)($user['id'] ?? 0);
$filters['scope_role'] = (string)($user['role'] ?? '');
$data = $controller->generarResumen($filters, true);
$f = $data['filters'];
$kpi = $data['kpi'];
$format = isset($_GET['format']) ? strtolower((string)$_GET['format']) : 'csv';

if ($format === 'csv') {
    $filename = 'dashboard_kpis_' . $f['date_from'] . '_' . $f['date_to'] . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }

    fputcsv($out, ['Dashboard Ejecutivo']);
    fputcsv($out, ['Periodo', $f['date_from'] . ' a ' . $f['date_to']]);
    fputcsv($out, []);
    fputcsv($out, ['KPI', 'Valor']);
    fputcsv($out, ['Ventas Netas', number_format((float)$kpi['total_neto'], 2, '.', '')]);
    fputcsv($out, ['Ventas Anuladas', number_format((float)$kpi['total_anulado'], 2, '.', '')]);
    fputcsv($out, ['Cantidad Ventas', (int)$kpi['cantidad_neta']]);
    fputcsv($out, ['Ticket Promedio', number_format((float)$kpi['ticket_promedio'], 2, '.', '')]);
    fputcsv($out, ['Turnos Abiertos', (int)$data['turnos']['abiertos']]);
    fputcsv($out, ['Diferencia Turnos', number_format((float)$data['turnos']['diferencia_acumulada'], 2, '.', '')]);
    fputcsv($out, []);
    fputcsv($out, ['Metodos de Pago']);
    fputcsv($out, ['Metodo', 'Total']);
    foreach ($data['metodos_pago'] as $m) {
        fputcsv($out, [(string)$m['metodo'], number_format((float)$m['total'], 2, '.', '')]);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard KPI - Export PDF</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #111; }
        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .btn { border: 1px solid #111; padding: 8px 12px; background: #111; color: #fff; text-decoration: none; border-radius: 6px; }
        .muted { color: #444; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #bbb; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #efefef; }
        .section { margin-top: 18px; }
        @media print {
            .no-print { display: none; }
            body { margin: 8mm; }
        }
    </style>
</head>
<body>
    <div class="top">
        <h1>Dashboard KPI</h1>
        <button class="btn no-print" onclick="window.print()">Imprimir / Guardar PDF</button>
    </div>
    <div class="muted">Periodo: <?php echo htmlspecialchars($f['date_from'] . ' a ' . $f['date_to'], ENT_QUOTES, 'UTF-8'); ?></div>

    <div class="section">
        <h2>KPIs</h2>
        <table>
            <thead><tr><th>KPI</th><th>Valor</th></tr></thead>
            <tbody>
                <tr><td>Ventas Netas</td><td>$<?php echo number_format((float)$kpi['total_neto'], 2, '.', ''); ?></td></tr>
                <tr><td>Ventas Anuladas</td><td>$<?php echo number_format((float)$kpi['total_anulado'], 2, '.', ''); ?></td></tr>
                <tr><td>Cantidad Ventas</td><td><?php echo (int)$kpi['cantidad_neta']; ?></td></tr>
                <tr><td>Ticket Promedio</td><td>$<?php echo number_format((float)$kpi['ticket_promedio'], 2, '.', ''); ?></td></tr>
                <tr><td>Turnos Abiertos</td><td><?php echo (int)$data['turnos']['abiertos']; ?></td></tr>
                <tr><td>Diferencia Turnos</td><td>$<?php echo number_format((float)$data['turnos']['diferencia_acumulada'], 2, '.', ''); ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Metodos de Pago</h2>
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
</body>
</html>
