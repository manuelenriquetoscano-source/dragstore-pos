<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Caja - Dragstore</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; gap: 20px; padding: 20px; }
        .scanner-section { width: 40%; background: #f8f9fa; padding: 20px; border-radius: 8px; }
        .cart-section { width: 60%; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
        .total-box { font-size: 24px; font-weight: bold; margin-top: 20px; color: #28a745; }
        #input_codigo { width: 100%; padding: 10px; font-size: 18px; }
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
    <button onclick="finalizarVenta()" style="padding: 15px 30px; background: #28a745; color: white; border: none; cursor: pointer;">CONFIRMAR VENTA</button>
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