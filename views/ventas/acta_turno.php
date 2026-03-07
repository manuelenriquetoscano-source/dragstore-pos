<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin(['admin', 'caja']);
require_once __DIR__ . '/../../controllers/TurnoCajaController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new TurnoCajaController($db);
$user = currentUser();
$isAdmin = (($user['role'] ?? '') === 'admin');
$usuarioActualId = (int)($user['id'] ?? 0);
$turnoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$result = $controller->obtenerActa($turnoId, $isAdmin, $usuarioActualId);
if (!$result['ok']) {
    http_response_code(403);
    echo 'No se pudo generar el acta: ' . htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
    exit;
}

$turno = $result['turno'];
$resumen = $result['resumen_pagos'];
$montoInicial = (float)($turno['monto_inicial'] ?? 0);
$montoFinal = isset($turno['monto_final_declarado']) ? (float)$turno['monto_final_declarado'] : 0;
$efectivoReal = (float)($resumen['total_efectivo_real'] ?? 0);
$esperadoCaja = $montoInicial + $efectivoReal;
$diferencia = $montoFinal - $esperadoCaja;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acta de Cierre - Turno #<?php echo (int)$turno['id']; ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 16px;
            background: #f8fafc;
            font-family: 'Segoe UI', sans-serif;
            color: #0f172a;
        }
        .sheet {
            max-width: 780px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 15px 35px -22px rgba(15, 23, 42, 0.45);
        }
        .head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 12px;
        }
        h1 {
            margin: 0 0 4px 0;
            font-size: 24px;
        }
        .muted {
            color: #475569;
            font-size: 13px;
        }
        .btn-print {
            border: none;
            background: #1d4ed8;
            color: #fff;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }
        .card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px;
            background: #f8fafc;
        }
        .label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 3px;
        }
        .value {
            font-size: 18px;
            font-weight: 800;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
            font-size: 13px;
        }
        th {
            background: #e2e8f0;
            color: #0f172a;
        }
        .section-title {
            margin-top: 12px;
            margin-bottom: 6px;
            font-size: 16px;
            font-weight: 800;
        }
        .signatures {
            margin-top: 22px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            font-size: 12px;
            color: #334155;
            text-align: center;
        }
        @media (min-width: 760px) {
            .grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet {
                border: none;
                border-radius: 0;
                box-shadow: none;
                max-width: none;
                padding: 0;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="head">
        <div>
            <h1>Acta de Cierre de Caja</h1>
            <div class="muted">Turno #<?php echo (int)$turno['id']; ?> - Usuario: <?php echo htmlspecialchars(($turno['username'] ?? '-') . ' - ' . ($turno['display_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="muted">Apertura: <?php echo htmlspecialchars((string)($turno['opened_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?> | Cierre: <?php echo htmlspecialchars((string)($turno['closed_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <button class="btn-print no-print" onclick="window.print()">Imprimir Acta</button>
    </div>

    <div class="grid">
        <div class="card">
            <div class="label">Monto Inicial</div>
            <div class="value">$<?php echo number_format($montoInicial, 2, '.', ''); ?></div>
        </div>
        <div class="card">
            <div class="label">Efectivo Ventas</div>
            <div class="value">$<?php echo number_format($efectivoReal, 2, '.', ''); ?></div>
        </div>
        <div class="card">
            <div class="label">Esperado en Caja</div>
            <div class="value">$<?php echo number_format($esperadoCaja, 2, '.', ''); ?></div>
        </div>
        <div class="card">
            <div class="label">Diferencia</div>
            <div class="value">$<?php echo number_format($diferencia, 2, '.', ''); ?></div>
        </div>
    </div>

    <div class="section-title">Detalle de Pagos del Turno</div>
    <table>
        <thead>
        <tr>
            <th>Concepto</th>
            <th>Monto</th>
        </tr>
        </thead>
        <tbody>
        <tr><td>Total Ventas</td><td>$<?php echo number_format((float)$resumen['total_ventas'], 2, '.', ''); ?></td></tr>
        <tr><td>Total Efectivo (solo ventas efectivas)</td><td>$<?php echo number_format((float)$resumen['total_efectivo_puro'], 2, '.', ''); ?></td></tr>
        <tr><td>Total Tarjeta</td><td>$<?php echo number_format((float)$resumen['total_tarjeta'], 2, '.', ''); ?></td></tr>
        <tr><td>Total Transferencia</td><td>$<?php echo number_format((float)$resumen['total_transferencia'], 2, '.', ''); ?></td></tr>
        <tr><td>Total Mixto</td><td>$<?php echo number_format((float)$resumen['total_mixto'], 2, '.', ''); ?></td></tr>
        <tr><td>Total Efectivo Real (incluye parte mixta)</td><td>$<?php echo number_format((float)$resumen['total_efectivo_real'], 2, '.', ''); ?></td></tr>
        <tr><td>Total Digital Real (incluye parte mixta)</td><td>$<?php echo number_format((float)$resumen['total_digital_real'], 2, '.', ''); ?></td></tr>
        <tr><td>Monto Final Declarado</td><td>$<?php echo number_format($montoFinal, 2, '.', ''); ?></td></tr>
        <tr><td><strong>Diferencia Final</strong></td><td><strong>$<?php echo number_format($diferencia, 2, '.', ''); ?></strong></td></tr>
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-line">Firma Cajero/a</div>
        <div class="signature-line">Firma Supervisor/a</div>
    </div>
</div>
</body>
</html>
