<?php
require_once __DIR__ . '/../../config/auth.php';
requireLogin(['admin', 'caja']);
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja - Dragstore</title>
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.68);
            --glass-border: rgba(255, 255, 255, 0.42);
            --glass-shadow: 0 24px 45px -30px rgba(44, 62, 80, 0.5);
            --glass-blur: 12px;
            --text-primary: #1e293b;
            --text-muted: #64748b;
            --brand: #28a745;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            flex-direction: column;
            gap: 14px;
            padding: 14px;
            margin: 0;
            color: var(--text-primary);
            min-height: 100vh;
            background:
                radial-gradient(circle at 12% 20%, rgba(52, 152, 219, 0.2), transparent 38%),
                radial-gradient(circle at 85% 15%, rgba(40, 167, 69, 0.15), transparent 34%),
                linear-gradient(145deg, #ebf4fc 0%, #f8fbff 46%, #edf7f1 100%);
            box-sizing: border-box;
        }

        .scanner-section,
        .cart-section {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            box-shadow: var(--glass-shadow);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
        }

        .scanner-section {
            width: 100%;
            padding: 16px;
            max-width: 100%;
            overflow: hidden;
        }

        .cart-section {
            width: 100%;
            padding: 16px;
            max-width: 100%;
            overflow: hidden;
        }

        h2 {
            margin-top: 0;
            color: #1f2d3d;
        }

        #input_codigo {
            width: 100%;
            padding: 12px 14px;
            font-size: 18px;
            border: 1px solid rgba(148, 163, 184, 0.5);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.75);
            color: var(--text-primary);
            box-sizing: border-box;
        }

        #input_codigo:focus {
            outline: none;
            border-color: rgba(40, 167, 69, 0.75);
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.18);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            overflow: hidden;
            border-radius: 12px;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 12px;
        }

        th {
            background: rgba(44, 62, 80, 0.9);
            color: #ffffff;
            padding: 10px;
            text-align: left;
            font-weight: 700;
            font-size: 14px;
            white-space: nowrap;
        }

        td {
            border-bottom: 1px solid rgba(148, 163, 184, 0.28);
            padding: 10px;
            text-align: left;
            background: rgba(255, 255, 255, 0.38);
            font-size: 14px;
            white-space: nowrap;
        }

        .total-box {
            font-size: 22px;
            font-weight: bold;
            margin-top: 20px;
            color: var(--brand);
        }

        .payment-box {
            margin-top: 12px;
            padding: 10px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.5);
        }

        .payment-title {
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .payment-grid label {
            display: block;
            font-size: 12px;
            color: #334155;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .payment-grid input,
        .payment-grid select {
            width: 100%;
            padding: 9px 10px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            border-radius: 8px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.85);
        }

        .payment-hidden {
            display: none;
        }

        .change-box {
            margin-top: 8px;
            font-size: 14px;
            color: #1e293b;
            font-weight: 700;
        }

        .btn-confirmar {
            width: 100%;
            padding: 13px 16px;
            background: linear-gradient(135deg, #28a745, #23903c);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            letter-spacing: 0.3px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 16px 24px -20px rgba(35, 144, 60, 0.8);
        }

        .btn-confirmar:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 28px -20px rgba(35, 144, 60, 0.95);
        }

        #info_producto {
            color: var(--text-muted) !important;
        }

        .turno-box {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.5);
        }

        .turno-title {
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
        }

        .turno-status {
            font-size: 13px;
            margin-bottom: 8px;
            color: #334155;
        }

        .turno-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .turno-grid label {
            display: block;
            font-size: 12px;
            color: #334155;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .turno-grid input {
            width: 100%;
            padding: 9px 10px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            border-radius: 8px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.85);
        }

        .turno-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-turno {
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 8px;
            padding: 9px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            color: #fff;
            background: rgba(30, 41, 59, 0.92);
        }

        .btn-turno.open {
            background: #0f766e;
        }

        .btn-turno.close {
            background: #b91c1c;
        }

        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            gap: 8px;
        }

        .top-actions-left {
            font-size: 13px;
            color: #334155;
            overflow-wrap: anywhere;
        }

        .top-actions-right {
            display: flex;
            gap: 8px;
        }

        .btn-index,
        .btn-logout {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            padding: 9px 14px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.45);
            background: rgba(44, 62, 80, 0.88);
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.2px;
            box-shadow: 0 14px 20px -18px rgba(44, 62, 80, 0.9);
        }

        .btn-index:hover,
        .btn-logout:hover {
            background: rgba(31, 45, 61, 0.95);
        }

        @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
            .scanner-section,
            .cart-section {
                background: rgba(255, 255, 255, 0.9);
            }
        }

        @media (min-width: 981px) {
            body {
                flex-direction: row;
                padding: 20px;
                gap: 20px;
            }

            .scanner-section,
            .cart-section {
                padding: 24px;
            }

            .scanner-section {
                width: 40%;
            }

            .cart-section {
                width: 60%;
            }

            .btn-confirmar {
                width: auto;
                padding: 15px 30px;
            }

            th {
                padding: 12px 10px;
                font-size: 16px;
            }

            td {
                font-size: 16px;
            }

            .total-box {
                font-size: 24px;
            }

            .btn-index {
                font-size: 14px;
                padding: 10px 16px;
            }
        }

        @media (max-width: 700px) {
            body {
                padding: 10px;
                gap: 10px;
            }

            .scanner-section,
            .cart-section {
                padding: 12px;
                border-radius: 12px;
            }

            .top-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .top-actions-right {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 8px;
            }

            .btn-index,
            .btn-logout {
                width: 100%;
                padding: 10px;
                font-size: 12px;
            }

            h2 {
                font-size: 20px;
                margin-bottom: 10px;
            }

            #input_codigo {
                font-size: 16px;
                padding: 11px 12px;
            }

            .total-box {
                font-size: 20px;
                margin-top: 14px;
            }

            .btn-confirmar {
                font-size: 13px;
                padding: 12px 14px;
            }

            .payment-grid input,
            .payment-grid select {
                font-size: 13px;
                padding: 8px 9px;
            }

            .turno-grid input {
                font-size: 13px;
                padding: 8px 9px;
            }

            .table-wrap {
                overflow: visible;
                border-radius: 0;
                width: 100%;
            }

            #tabla_venta,
            #tabla_venta thead,
            #tabla_venta tbody,
            #tabla_venta tr,
            #tabla_venta th,
            #tabla_venta td {
                display: block;
                width: 100%;
            }

            #tabla_venta thead {
                display: none;
            }

            #tabla_venta tr {
                background: rgba(255, 255, 255, 0.72);
                border: 1px solid rgba(148, 163, 184, 0.35);
                border-radius: 10px;
                padding: 8px;
                margin-bottom: 8px;
            }

            #tabla_venta td {
                white-space: normal;
                padding: 6px 6px 6px 40%;
                border: none;
                border-bottom: 1px dashed rgba(148, 163, 184, 0.35);
                position: relative;
                background: transparent;
                font-size: 13px;
                overflow-wrap: anywhere;
            }

            #tabla_venta td:last-child {
                border-bottom: none;
            }

            #tabla_venta td::before {
                content: attr(data-label);
                position: absolute;
                left: 6px;
                top: 6px;
                width: 35%;
                font-weight: 700;
                color: #334155;
                white-space: normal;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 8px;
                gap: 8px;
            }

            .scanner-section,
            .cart-section {
                padding: 10px;
                border-radius: 10px;
            }

            h2 {
                font-size: 18px;
            }

            #input_codigo {
                font-size: 15px;
                padding: 10px 11px;
            }

            .top-actions-left {
                font-size: 12px;
            }

            .top-actions-right {
                grid-template-columns: 1fr;
            }

            #tabla_venta tr {
                padding: 7px;
                margin-bottom: 7px;
            }

            #tabla_venta td {
                padding: 6px 4px 6px 46%;
                font-size: 12px;
                min-height: 30px;
            }

            #tabla_venta td::before {
                width: 42%;
                font-size: 11px;
            }

            .btn-confirmar {
                font-size: 12px;
                padding: 11px 12px;
            }

            .total-box {
                font-size: 18px;
            }

            .payment-box {
                padding: 8px;
            }

            .payment-title {
                font-size: 13px;
            }

            .change-box {
                font-size: 13px;
            }

            .turno-box {
                padding: 8px;
            }

            .turno-title {
                font-size: 13px;
            }

            .turno-actions {
                flex-direction: column;
            }

            .btn-turno {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="scanner-section">
    <div class="top-actions">
        <div class="top-actions-left">
            <?php echo htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>)
        </div>
        <div class="top-actions-right">
            <a href="/dragstore-pos/views/ventas/historial_turnos.php" class="btn-index">Turnos</a>
            <a href="/dragstore-pos/index.php" class="btn-index">Menu</a>
            <a href="/dragstore-pos/logout.php" class="btn-logout">Salir</a>
        </div>
    </div>
    <h2>Scanner de Productos</h2>
    <input type="text" id="input_codigo" placeholder="Escanee codigo de barras..." autofocus>
    <p><small>El sistema buscara automaticamente al detectar un codigo.</small></p>
    <hr>
    <div id="info_producto" style="color: #666;">Esperando escaneo...</div>
    <div class="turno-box">
        <p class="turno-title">Turno de Caja</p>
        <div id="turno_status" class="turno-status">Cargando estado...</div>
        <div class="turno-grid">
            <div>
                <label for="turno_monto_inicial">Monto inicial</label>
                <input type="number" id="turno_monto_inicial" min="0" step="0.01" placeholder="Ej: 10000">
            </div>
            <div>
                <label for="turno_monto_final">Monto final declarado</label>
                <input type="number" id="turno_monto_final" min="0" step="0.01" placeholder="Ej: 18500">
            </div>
        </div>
        <div class="turno-actions">
            <button type="button" class="btn-turno open" onclick="abrirTurno()">ABRIR TURNO</button>
            <button type="button" class="btn-turno close" onclick="cerrarTurno()">CERRAR TURNO</button>
        </div>
    </div>
</div>

<div class="cart-section">
    <h2>Detalle de la Venta</h2>
    <div class="table-wrap">
    <table id="tabla_venta">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Precio</th>
                <th>Cant.</th>
                <th>Subtotal</th>
                <th>Accion</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    </div>
    <div class="total-box">Total: $<span id="total_venta">0.00</span></div>
    <div class="payment-box">
        <p class="payment-title">Pago</p>
        <div class="payment-grid">
            <div>
                <label for="metodo_pago">Metodo</label>
                <select id="metodo_pago">
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="mixto">Mixto</option>
                </select>
            </div>
            <div id="field_monto_recibido">
                <label for="monto_recibido">Monto recibido</label>
                <input type="number" id="monto_recibido" min="0" step="0.01" placeholder="Ej: 5000">
            </div>
            <div id="field_monto_efectivo" class="payment-hidden">
                <label for="monto_efectivo">Monto efectivo</label>
                <input type="number" id="monto_efectivo" min="0" step="0.01" placeholder="Ej: 2500">
            </div>
            <div id="field_monto_digital" class="payment-hidden">
                <label for="monto_digital">Monto digital</label>
                <input type="number" id="monto_digital" min="0" step="0.01" placeholder="Ej: 2500">
            </div>
        </div>
        <div class="change-box">Vuelto: $<span id="vuelto_venta">0.00</span></div>
    </div>
    <button onclick="finalizarVenta()" class="btn-confirmar">CONFIRMAR VENTA</button>
</div>

<script>
let carrito = [];
const inputCodigo = document.getElementById('input_codigo');
const btnConfirmar = document.querySelector('.btn-confirmar');
const metodoPagoEl = document.getElementById('metodo_pago');
const montoRecibidoEl = document.getElementById('monto_recibido');
const montoEfectivoEl = document.getElementById('monto_efectivo');
const montoDigitalEl = document.getElementById('monto_digital');
const fieldMontoRecibidoEl = document.getElementById('field_monto_recibido');
const fieldMontoEfectivoEl = document.getElementById('field_monto_efectivo');
const fieldMontoDigitalEl = document.getElementById('field_monto_digital');
const turnoStatusEl = document.getElementById('turno_status');
const turnoMontoInicialEl = document.getElementById('turno_monto_inicial');
const turnoMontoFinalEl = document.getElementById('turno_monto_final');
let ventaEnProceso = false;
let turnoActual = null;
let turnoResumen = null;

function getTotalActual() {
    return parseFloat(document.getElementById('total_venta').innerText || '0') || 0;
}

function parseMoney(input) {
    const value = parseFloat((input || '').toString());
    return Number.isFinite(value) ? value : 0;
}

function updatePaymentFields() {
    const metodo = metodoPagoEl.value;
    fieldMontoRecibidoEl.classList.toggle('payment-hidden', metodo !== 'efectivo');
    fieldMontoEfectivoEl.classList.toggle('payment-hidden', metodo !== 'mixto');
    fieldMontoDigitalEl.classList.toggle('payment-hidden', metodo !== 'mixto');

    if (metodo === 'efectivo' && !montoRecibidoEl.value) {
        montoRecibidoEl.value = getTotalActual().toFixed(2);
    }
    if (metodo === 'mixto') {
        const total = getTotalActual();
        if (!montoEfectivoEl.value && !montoDigitalEl.value) {
            montoEfectivoEl.value = total.toFixed(2);
            montoDigitalEl.value = '0.00';
        }
    }
    updateVuelto();
}

function updateVuelto() {
    const metodo = metodoPagoEl.value;
    const total = getTotalActual();
    let vuelto = 0;

    if (metodo === 'efectivo') {
        const recibido = parseMoney(montoRecibidoEl.value);
        vuelto = Math.max(0, recibido - total);
    } else if (metodo === 'mixto') {
        const efectivo = parseMoney(montoEfectivoEl.value);
        const digital = parseMoney(montoDigitalEl.value);
        vuelto = Math.max(0, (efectivo + digital) - total);
    }

    document.getElementById('vuelto_venta').innerText = vuelto.toFixed(2);
}

function buildPaymentPayload(total) {
    const metodo = metodoPagoEl.value;
    if (metodo === 'efectivo') {
        const recibido = parseMoney(montoRecibidoEl.value);
        if (recibido < total) {
            return { ok: false, message: 'El monto recibido es menor al total.' };
        }
        return {
            ok: true,
            pago: {
                metodo_pago: 'efectivo',
                monto_recibido: recibido
            }
        };
    }

    if (metodo === 'tarjeta' || metodo === 'transferencia') {
        return {
            ok: true,
            pago: {
                metodo_pago: metodo
            }
        };
    }

    const efectivo = parseMoney(montoEfectivoEl.value);
    const digital = parseMoney(montoDigitalEl.value);
    if ((efectivo + digital) < total) {
        return { ok: false, message: 'La suma de pago mixto es menor al total.' };
    }

    return {
        ok: true,
        pago: {
            metodo_pago: 'mixto',
            monto_efectivo: efectivo,
            monto_digital: digital
        }
    };
}

function renderTurnoStatus() {
    if (!turnoStatusEl) return;
    if (turnoActual && turnoActual.estado === 'abierto') {
        const apertura = turnoActual.opened_at || '';
        const montoInicial = parseMoney(turnoActual.monto_inicial).toFixed(2);
        const efectivo = parseMoney(turnoResumen && turnoResumen.total_efectivo).toFixed(2);
        const esperado = parseMoney(turnoResumen && turnoResumen.esperado_caja).toFixed(2);
        turnoStatusEl.innerText = `Abierto | ID: ${turnoActual.id} | Apertura: ${apertura} | Inicial: $${montoInicial} | Efectivo ventas: $${efectivo} | Sugerido cierre: $${esperado}`;
        if (turnoMontoFinalEl && turnoMontoFinalEl.value.trim() === '') {
            turnoMontoFinalEl.value = esperado;
        }
        return;
    }
    turnoStatusEl.innerText = 'Sin turno abierto.';
    if (turnoMontoFinalEl) {
        turnoMontoFinalEl.value = '';
    }
}

function cargarEstadoTurno() {
    fetch('/dragstore-pos/turno_caja.php?action=status')
        .then(response => response.json())
        .then(res => {
            if (res.status !== 'success') {
                throw new Error(res.message || 'No se pudo obtener estado de turno');
            }
            turnoActual = (res.data && res.data.turno) ? res.data.turno : null;
            turnoResumen = (res.data && res.data.resumen) ? res.data.resumen : null;
            renderTurnoStatus();
        })
        .catch(() => {
            turnoActual = null;
            turnoResumen = null;
            renderTurnoStatus();
        });
}

function abrirTurno() {
    const montoInicial = parseMoney(turnoMontoInicialEl.value);
    if (montoInicial < 0) {
        alert('Monto inicial invalido.');
        return;
    }
    fetch('/dragstore-pos/turno_caja.php?action=abrir', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ monto_inicial: montoInicial })
    })
        .then(response => response.json())
        .then(res => {
            if (res.status !== 'success') {
                throw new Error(res.message || 'No se pudo abrir el turno');
            }
            alert('Turno abierto correctamente.');
            cargarEstadoTurno();
        })
        .catch(error => alert(error.message || 'Error al abrir turno.'));
}

function cerrarTurno() {
    if (turnoMontoFinalEl.value.trim() === '') {
        alert('Debe ingresar el monto final declarado.');
        return;
    }
    const montoFinal = parseMoney(turnoMontoFinalEl.value);
    if (montoFinal < 0) {
        alert('Monto final invalido.');
        return;
    }
    fetch('/dragstore-pos/turno_caja.php?action=cerrar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ monto_final_declarado: montoFinal })
    })
        .then(response => response.json())
        .then(res => {
            if (res.status !== 'success') {
                throw new Error(res.message || 'No se pudo cerrar el turno');
            }
            const resumen = (res.data && res.data.resumen) ? res.data.resumen : null;
            if (resumen) {
                alert(
                    `Turno cerrado.\nVentas: ${resumen.cantidad_ventas}\nTotal ventas: $${parseMoney(resumen.total_ventas).toFixed(2)}\nEfectivo ventas: $${parseMoney(resumen.total_efectivo).toFixed(2)}\nEsperado en caja: $${parseMoney(resumen.esperado_caja).toFixed(2)}\nDiferencia: $${parseMoney(resumen.diferencia).toFixed(2)}`
                );
            } else {
                alert('Turno cerrado correctamente.');
            }
            turnoMontoFinalEl.value = '';
            cargarEstadoTurno();
        })
        .catch(error => alert(error.message || 'Error al cerrar turno.'));
}

metodoPagoEl.addEventListener('change', updatePaymentFields);
montoRecibidoEl.addEventListener('input', updateVuelto);
montoEfectivoEl.addEventListener('input', updateVuelto);
montoDigitalEl.addEventListener('input', updateVuelto);

// 1. EVENTO DE ESCANEO
inputCodigo.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const codigo = this.value.trim();
        if(codigo) buscarProducto(codigo);
        this.value = ''; 
    }
});

// 2. BUSCAR PRODUCTO (AJAX)
function buscarProducto(codigo) {
    fetch('/dragstore-pos/buscar_ajax.php?codigo=' + encodeURIComponent(codigo))
        .then(response => {
            if (!response.ok) throw new Error('Error en la red');
            return response.json();
        })
        .then(res => {
            if(res.status === 'success') {
                const producto = Array.isArray(res.data) ? res.data[0] : res.data;
                if (!producto) {
                    alert("Producto no encontrado");
                    return;
                }

                agregarAlCarrito(producto);
                document.getElementById('info_producto').innerHTML = 
                    `<span style="color:green">Anadido: ${producto.nombre}</span>`;
            } else {
                alert("Producto no encontrado");
            }
        })
        .catch(error => alert("Error de comunicacion con el servidor."));
}

// 3. AGREGAR AL CARRITO (CON VALIDACION DE STOCK)
function agregarAlCarrito(producto) {
    const existe = carrito.find(item => item.id === producto.id);
    
    if (existe) {
        if (existe.cantidad + 1 > producto.stock) {
            alert(`Solo quedan ${producto.stock} unidades de ${producto.nombre}`);
            return;
        }
        existe.cantidad++;
    } else {
        if (producto.stock < 1) {
            alert(`${producto.nombre} no tiene stock.`);
            return;
        }
        carrito.push({ ...producto, cantidad: 1 });
    }
    actualizarTabla();
}

// 4. ACTUALIZAR TABLA VISUAL
function actualizarTabla() {
    const tbody = document.querySelector('#tabla_venta tbody');
    tbody.innerHTML = '';
    let total = 0;

    carrito.forEach((item, index) => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;
        const colorStock = (item.cantidad >= item.stock) ? 'color: red; font-weight: bold;' : '';

        tbody.innerHTML += `
            <tr>
                <td data-label="Producto">${item.nombre}</td>
                <td data-label="Precio">$${item.precio}</td>
                <td data-label="Cant." style="${colorStock}">${item.cantidad} <small>(Stock: ${item.stock})</small></td>
                <td data-label="Subtotal">$${subtotal.toFixed(2)}</td>
                <td data-label="Accion"><button onclick="eliminarDelCarrito(${index})" style="border:none; color:red; cursor:pointer; background:transparent;">X</button></td>
            </tr>
        `;
    });
    document.getElementById('total_venta').innerText = total.toFixed(2);
    updatePaymentFields();
}

// 5. ELIMINAR ITEM
function eliminarDelCarrito(index) {
    carrito.splice(index, 1);
    actualizarTabla();
}

// 6. FINALIZAR VENTA (ENVIO AL SERVIDOR)
function finalizarVenta() {
    if (ventaEnProceso) return;
    if (carrito.length === 0) return alert("El carrito esta vacio.");
    if (!turnoActual || turnoActual.estado !== 'abierto') {
        return alert("Debe abrir un turno de caja antes de vender.");
    }

    ventaEnProceso = true;
    if (btnConfirmar) {
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'PROCESANDO...';
        btnConfirmar.style.opacity = '0.75';
        btnConfirmar.style.cursor = 'wait';
    }

    const total = parseFloat(document.getElementById('total_venta').innerText);
    const pagoValidado = buildPaymentPayload(total);
    if (!pagoValidado.ok) {
        alert(pagoValidado.message);
        return;
    }
    const datosVenta = { total: total, carrito: carrito, pago: pagoValidado.pago };
    const ventanaTicket = window.open('', 'ticketPreview', 'width=450,height=600');

    if (!ventanaTicket) {
        alert("No se pudo abrir la vista del ticket. Habilita ventanas emergentes para este sitio.");
        return;
    }

    ventanaTicket.document.open();
    ventanaTicket.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Generando ticket...</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                    color: #334155;
                    background: #f8fafc;
                }
            </style>
        </head>
        <body>Generando ticket...</body>
        </html>
    `);
    ventanaTicket.document.close();

    fetch('../../procesar_venta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datosVenta)
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success') {
    // LLAMAMOS A LA IMPRESION ANTES DE LIMPIAR EL CARRITO
    imprimirTicket(datosVenta, ventanaTicket); 

    alert("Venta realizada con exito!");
    carrito = [];
    montoRecibidoEl.value = '';
    montoEfectivoEl.value = '';
    montoDigitalEl.value = '';
    actualizarTabla();
    inputCodigo.focus();
} else {
    if (!ventanaTicket.closed) ventanaTicket.close();
    alert("No se pudo completar la venta.");
}
    })
    .catch(error => {
        if (!ventanaTicket.closed) ventanaTicket.close();
        alert("Error al procesar la venta.");
    })
    .finally(() => {
        ventaEnProceso = false;
        if (btnConfirmar) {
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = 'CONFIRMAR VENTA';
            btnConfirmar.style.opacity = '';
            btnConfirmar.style.cursor = '';
        }
    });
}
function imprimirTicket(datosVenta, ventanaExistente = null) {
    const ventana = (ventanaExistente && !ventanaExistente.closed)
        ? ventanaExistente
        : window.open('', '_blank', 'width=450,height=600');

    if (!ventana) {
        alert("No se pudo abrir la ventana del ticket.");
        return;
    }
    
    let filas = '';
    datosVenta.carrito.forEach(item => {
        filas += `
            <tr>
                <td style="padding: 5px 0; font-size: 14px;">
                    ${item.nombre} <br> 
                    <small>${item.cantidad} x $${item.precio}</small>
                </td>
                <td style="text-align:right; vertical-align: top; font-size: 14px;">
                    $${(item.precio * item.cantidad).toFixed(2)}
                </td>
            </tr>`;
    });

    const pago = datosVenta.pago || {};
    const metodoMap = {
        efectivo: 'Efectivo',
        tarjeta: 'Tarjeta',
        transferencia: 'Transferencia',
        mixto: 'Mixto'
    };
    const metodoPago = metodoMap[pago.metodo_pago] || 'No informado';
    const montoRecibido = parseMoney(pago.monto_recibido);
    const montoEfectivo = parseMoney(pago.monto_efectivo);
    const montoDigital = parseMoney(pago.monto_digital);
    const vuelto = parseMoney(pago.vuelto ?? (montoRecibido - datosVenta.total));

    let detallePago = `<p style="margin:4px 0;"><strong>Metodo:</strong> ${metodoPago}</p>`;
    if (pago.metodo_pago === 'efectivo') {
        detallePago += `<p style="margin:4px 0;"><strong>Recibido:</strong> $${montoRecibido.toFixed(2)}</p>`;
    } else if (pago.metodo_pago === 'mixto') {
        detallePago += `<p style="margin:4px 0;"><strong>Efectivo:</strong> $${montoEfectivo.toFixed(2)}</p>`;
        detallePago += `<p style="margin:4px 0;"><strong>Digital:</strong> $${montoDigital.toFixed(2)}</p>`;
        detallePago += `<p style="margin:4px 0;"><strong>Recibido:</strong> $${(montoEfectivo + montoDigital).toFixed(2)}</p>`;
    }
    detallePago += `<p style="margin:4px 0;"><strong>Vuelto:</strong> $${Math.max(0, vuelto).toFixed(2)}</p>`;

    ventana.document.open();
    ventana.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Ticket de Venta - Vista Previa</title>
            <style>
                body { 
                    font-family: 'Courier New', Courier, monospace; 
                    width: 320px; 
                    margin: 0 auto; 
                    padding: 20px; 
                    border: 1px solid #ccc;
                    background-color: #fff;
                }
                .text-center { text-align: center; }
                hr { border-top: 1px dashed black; margin: 10px 0; }
                table { width: 100%; border-collapse: collapse; }
                .total { font-size: 22px; font-weight: bold; margin-top: 10px; display: block; }
                
                /* Estilos para el boton que NO se imprime */
                .btn-print { 
                    background: #28a745; 
                    color: white; 
                    padding: 15px; 
                    width: 100%; 
                    border: none; 
                    border-radius: 5px;
                    font-size: 16px;
                    font-weight: bold;
                    margin-top: 20px; 
                    cursor: pointer; 
                }
                .btn-print:hover { background: #218838; }

                /* REGLA DE ORO: Ocultar el boton al imprimir */
                @media print {
                    .no-print { display: none !important; }
                    body { border: none; width: 100%; padding: 0; }
                }
            </style>
        </head>
        <body>
            <div class="text-center">
                <h2 style="margin:0;">DRUGSTORE POS</h2>
                <p style="font-size: 12px;">Comprobante de Venta</p>
                <p style="font-size: 12px;">Turno: ${turnoActual ? turnoActual.id : '-'}</p>
                <p>Fecha: ${new Date().toLocaleString()}</p>
            </div>
            <hr>
            <table>
                ${filas}
            </table>
            <hr>
            <div style="text-align:right;">
                <span class="total">TOTAL: $${datosVenta.total.toFixed(2)}</span>
            </div>
            <div style="font-size:12px; margin-top:8px;">
                ${detallePago}
            </div>
            <br>
            <div class="text-center" style="font-size: 12px;">
                Gracias por su compra!
            </div>
            
            <button class="no-print btn-print" onclick="window.print()">
                CONFIRMAR E IMPRIMIR
            </button>

            <p class="no-print" style="text-align:center; font-size:11px; color:#666;">
                (Al presionar el boton se abrira el dialogo de su impresora)
            </p>

            <script>
                function volverAlPOS() {
                    if (window.opener && !window.opener.closed) {
                        window.opener.location.href = '/dragstore-pos/views/ventas/caja.php';
                        window.opener.focus();
                    }
                    window.close();
                }

                window.addEventListener('afterprint', function () {
                    setTimeout(volverAlPOS, 200);
                });
            <\/script>

        </body>
        </html>
    `);

    // Importante: cerrar el flujo para que el navegador renderice YA
    ventana.document.close();
}

updatePaymentFields();
cargarEstadoTurno();
</script>
</body>
</html>

