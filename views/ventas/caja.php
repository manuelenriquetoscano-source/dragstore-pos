<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
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
            gap: 20px;
            padding: 20px;
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
            width: 40%;
            padding: 24px;
        }

        .cart-section {
            width: 60%;
            padding: 24px;
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

        th {
            background: rgba(44, 62, 80, 0.9);
            color: #ffffff;
            padding: 12px 10px;
            text-align: left;
            font-weight: 700;
        }

        td {
            border-bottom: 1px solid rgba(148, 163, 184, 0.28);
            padding: 10px;
            text-align: left;
            background: rgba(255, 255, 255, 0.38);
        }

        .total-box {
            font-size: 24px;
            font-weight: bold;
            margin-top: 20px;
            color: var(--brand);
        }

        .btn-confirmar {
            padding: 15px 30px;
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

        @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
            .scanner-section,
            .cart-section {
                background: rgba(255, 255, 255, 0.9);
            }
        }

        @media (max-width: 980px) {
            body {
                flex-direction: column;
            }

            .scanner-section,
            .cart-section {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="scanner-section">
    <h2>Scanner de Productos</h2>
    <input type="text" id="input_codigo" placeholder="Escanee código de barras..." autofocus>
    <p><small>El sistema buscará automáticamente al detectar un código.</small></p>
    <hr>
    <div id="info_producto" style="color: #666;">Esperando escaneo...</div>
</div>

<div class="cart-section">
    <h2>Detalle de la Venta</h2>
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
    <div class="total-box">Total: $<span id="total_venta">0.00</span></div>
    <br>
    <button onclick="finalizarVenta()" class="btn-confirmar">CONFIRMAR VENTA</button>
</div>

<script>
let carrito = [];
const inputCodigo = document.getElementById('input_codigo');

// 1. EVENTO DE ESCANEO
inputCodigo.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const codigo = this.value;
        if(codigo) buscarProducto(codigo);
        this.value = ''; 
    }
});

// 2. BUSCAR PRODUCTO (AJAX)
function buscarProducto(codigo) {
    fetch('/dragstore-pos/buscar_ajax.php?codigo=' + codigo)
        .then(response => {
            if (!response.ok) throw new Error('Error en la red');
            return response.json();
        })
        .then(res => {
            if(res.status === 'success') {
                agregarAlCarrito(res.data);
                document.getElementById('info_producto').innerHTML = 
                    `<span style="color:green">Añadido: ${res.data.nombre}</span>`;
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
    if (carrito.length === 0) return alert("El carrito está vacío.");

    const total = document.getElementById('total_venta').innerText;
    const datosVenta = { total: parseFloat(total), carrito: carrito };

    fetch('../../procesar_venta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datosVenta)
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success') {
    // LLAMAMOS A LA IMPRESIÓN ANTES DE LIMPIAR EL CARRITO
    imprimirTicket(datosVenta); 

    alert("✅ ¡Venta realizada con éxito!");
    carrito = [];
    actualizarTabla();
    inputCodigo.focus();
}
    })
    .catch(error => alert("Error al procesar la venta."));
}
function imprimirTicket(datosVenta) {
    // Abrimos la ventana
    const ventana = window.open('', '_blank', 'width=450,height=600');
    
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

    ventana.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
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

        </body>
        </html>
    `);

    // Importante: cerrar el flujo para que el navegador renderice YA
    ventana.document.close();
}
</script>
</body>
</html>
