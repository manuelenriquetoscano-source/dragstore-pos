<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin(['admin', 'caja']);
require_once __DIR__ . '/../../controllers/VentaController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new VentaController($db);
$ventaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = $controller->obtenerDetalleTicket($ventaId);
$errorMessage = null;
$venta = null;
$items = [];
if (!$result['ok']) {
    http_response_code(404);
    $errorMessage = (string)$result['message'];
} else {
    $venta = $result['venta'];
    $items = $result['items'];
}
$metodoMap = [
    'efectivo' => 'Efectivo',
    'tarjeta' => 'Tarjeta',
    'transferencia' => 'Transferencia',
    'mixto' => 'Mixto'
];
$metodoPago = 'No informado';
if (is_array($venta)) {
    $metodoPago = $metodoMap[$venta['metodo_pago'] ?? 'efectivo'] ?? 'No informado';
}
$titleVentaId = is_array($venta) ? (int)$venta['id'] : $ventaId;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Venta #<?php echo (int)$titleVentaId; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 340px;
            margin: 10px auto;
            padding: 18px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
        }
        .text-center { text-align: center; }
        .small { font-size: 12px; color: #334155; }
        hr { border: none; border-top: 1px dashed #334155; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 0; font-size: 13px; vertical-align: top; }
        .right { text-align: right; }
        .total { font-size: 21px; font-weight: 800; }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            border: 1px solid #fecaca;
            background: #fee2e2;
            color: #991b1b;
        }
        .btn-print {
            margin-top: 14px;
            width: 100%;
            border: none;
            border-radius: 6px;
            padding: 12px;
            font-size: 14px;
            font-weight: 800;
            color: #fff;
            background: #16a34a;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none !important; }
            body { border: none; width: 100%; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <?php if ($errorMessage !== null): ?>
    <div class="text-center" style="padding: 18px 0;">
        <h2 style="margin:0 0 8px;">Ticket no disponible</h2>
        <p class="small" style="margin:0 0 12px;">
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <p class="small" style="margin:0 0 12px;">Verifique el ID de venta e intente nuevamente.</p>
        <button class="btn-print no-print" onclick="window.close()">Cerrar</button>
    </div>
    <?php else: ?>
    <div class="text-center">
        <h2 style="margin:0;">DRUGSTORE POS</h2>
        <p class="small" style="margin:3px 0;">Reimpresion de Ticket</p>
        <p class="small" style="margin:3px 0;">Venta #<?php echo (int)$venta['id']; ?></p>
        <p class="small" style="margin:3px 0;">Fecha: <?php echo htmlspecialchars((string)$venta['fecha'], ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <?php if (($venta['estado'] ?? 'completada') === 'anulada'): ?>
        <div class="text-center" style="margin-top:6px;">
            <span class="badge">VENTA ANULADA</span>
            <?php if (!empty($venta['motivo_anulacion'])): ?>
                <p class="small" style="margin:6px 0 0 0;">Motivo: <?php echo htmlspecialchars((string)$venta['motivo_anulacion'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <hr>
    <table>
        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <?php echo htmlspecialchars($item['producto_nombre'], ENT_QUOTES, 'UTF-8'); ?><br>
                    <span class="small"><?php echo (int)$item['cantidad']; ?> x $<?php echo number_format((float)$item['precio_unitario'], 2, '.', ''); ?></span>
                </td>
                <td class="right">$<?php echo number_format((float)$item['cantidad'] * (float)$item['precio_unitario'], 2, '.', ''); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <hr>

    <div class="small">Metodo: <?php echo htmlspecialchars($metodoPago, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php if (($venta['metodo_pago'] ?? '') === 'efectivo'): ?>
        <div class="small">Recibido: $<?php echo number_format((float)($venta['monto_recibido'] ?? $venta['total']), 2, '.', ''); ?></div>
    <?php elseif (($venta['metodo_pago'] ?? '') === 'mixto'): ?>
        <div class="small">Efectivo: $<?php echo number_format((float)($venta['monto_efectivo'] ?? 0), 2, '.', ''); ?></div>
        <div class="small">Digital: $<?php echo number_format((float)($venta['monto_digital'] ?? 0), 2, '.', ''); ?></div>
    <?php endif; ?>
    <div class="small">Vuelto: $<?php echo number_format((float)($venta['vuelto'] ?? 0), 2, '.', ''); ?></div>

    <div class="right" style="margin-top:8px;">
        <span class="total">TOTAL: $<?php echo number_format((float)$venta['total'], 2, '.', ''); ?></span>
    </div>

    <button class="btn-print no-print" onclick="window.print()">Imprimir</button>
    <?php endif; ?>
</body>
</html>
