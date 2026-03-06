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
        }

        .cart-section {
            width: 100%;
            padding: 16px;
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
    </style>
</head>
<body>

<div class="scanner-section">
    <div class="top-actions">
        <div class="top-actions-left">
            <?php echo htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>)
        </div>
        <div class="top-actions-right">
            <a href="/dragstore-pos/index.php" class="btn-index">Menú</a>
            <a href="/dragstore-pos/logout.php" class="btn-logout">Salir</a>
        </div>
    </div>
    <h2>Scanner de Productos</h2>
    <input type="text" id="input_codigo" placeholder="Escanee código de barras..." autofocus>
    <p><small>El sistema buscará automáticamente al detectar un código.</small></p>
    <hr>
    <div id="info_producto" style="color: #666;">Esperando escaneo...</div>
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
                <th>Acción</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    </div>
    <div class="total-box">Total: $<span id="total_venta">0.00</span></div>
    <br>
    <button onclick="finalizarVenta()" class="btn-confirmar">CONFIRMAR VENTA</button>
</div>

<script>
let carrito = [];
const inputCodigo = document.getElementById('input_codigo');
const btnConfirmar = document.querySelector('.btn-confirmar');
let ventaEnProceso = false;

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
                    `<span style="color:green">Añadido: ${producto.nombre}</span>`;
            } else {
                alert("Producto no encontrado");
            }
        })
        .catch(error => alert("Error de comunicación con el servidor."));
}

// 3. AGREGAR AL CARRITO (CON VALIDACIÓN DE STOCK)
function agregarAlCarrito(producto) {
    const existe = carrito.find(item => item.id === producto.id);
    
    if (existe) {
        if (existe.cantidad + 1 > producto.stock) {
            alert(`⚠️ Solo quedan ${producto.stock} unidades de ${producto.nombre}`);
            return;
        }
        existe.cantidad++;
    } else {
        if (producto.stock < 1) {
            alert(`❌ ${producto.nombre} no tiene stock.`);
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
                <td>${item.nombre}</td>
                <td>$${item.precio}</td>
                <td style="${colorStock}">${item.cantidad} <small>(Stock: ${item.stock})</small></td>
                <td>$${subtotal.toFixed(2)}</td>
                <td><button onclick="eliminarDelCarrito(${index})" style="border:none; color:red; cursor:pointer;">🗑️</button></td>
            </tr>
        `;
    });
    document.getElementById('total_venta').innerText = total.toFixed(2);
}

// 5. ELIMINAR ITEM
function eliminarDelCarrito(index) {
    carrito.splice(index, 1);
    actualizarTabla();
}

// 6. FINALIZAR VENTA (ENVÍO AL SERVIDOR)
function finalizarVenta() {
    if (ventaEnProceso) return;
    if (carrito.length === 0) return alert("El carrito está vacío.");

    ventaEnProceso = true;
    if (btnConfirmar) {
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'PROCESANDO...';
        btnConfirmar.style.opacity = '0.75';
        btnConfirmar.style.cursor = 'wait';
    }

    const total = document.getElementById('total_venta').innerText;
    const datosVenta = { total: parseFloat(total), carrito: carrito };
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
    // LLAMAMOS A LA IMPRESIÓN ANTES DE LIMPIAR EL CARRITO
    imprimirTicket(datosVenta, ventanaTicket); 

    alert("✅ ¡Venta realizada con éxito!");
    carrito = [];
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
                
                /* Estilos para el botón que NO se imprime */
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

                /* REGLA DE ORO: Ocultar el botón al imprimir */
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
            <br>
            <div class="text-center" style="font-size: 12px;">
                ¡Gracias por su compra!
            </div>
            
            <button class="no-print btn-print" onclick="window.print()">
                🖨️ CONFIRMAR E IMPRIMIR
            </button>

            <p class="no-print" style="text-align:center; font-size:11px; color:#666;">
                (Al presionar el botón se abrirá el diálogo de su impresora)
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
</script>
</body>
</html>
