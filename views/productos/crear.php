<?php
// Archivo: views/productos/crear.php
require_once '../../config/database.php';
require_once '../../models/Producto.php';

$mensaje = "";
$tipo_mensaje = "";

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    $producto = new Producto($db);

    $producto->codigo_barras = $_POST['codigo_barras'];
    $producto->nombre = $_POST['nombre'];
    $producto->precio = $_POST['precio'];
    $producto->stock = $_POST['stock'];

    if ($producto->crear()) {
        $mensaje = "¡Producto guardado con éxito!";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "No se pudo guardar el producto. Verifica el código.";
        $tipo_mensaje = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Producto - Drugstore POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #2c3e50;
            --success: #27ae60;
            --bg: #f8fafc;
        }
        body { font-family: 'Nunito Sans', sans-serif; background: var(--bg); margin: 0; padding: 20px; color: #334155; }
        
        .form-container {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .header { text-align: center; margin-bottom: 30px; }
        .header i { font-size: 40px; color: var(--success); margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; color: var(--primary); }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        
        .input-group { position: relative; display: flex; align-items: center; }
        .input-group i { position: absolute; left: 15px; color: #94a3b8; }
        
        .form-control {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-family: 'Nunito Sans', sans-serif;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus { outline: none; border-color: var(--success); box-shadow: 0 0 0 4px rgba(39, 174, 96, 0.1); }

        .btn-submit {
            width: 100%;
            background: var(--success);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.2s, background 0.3s;
            margin-top: 10px;
        }
        .btn-submit:hover { background: #219150; transform: translateY(-2px); }
        .btn-submit:active { transform: translateY(0); }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-back:hover { color: var(--primary); }

        /* Alertas */
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 25px; text-align: center; font-weight: 600; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

<div class="form-container">
    <div class="header">
        <i class="fa-solid fa-circle-plus"></i>
        <h1>Nuevo Producto</h1>
    </div>

    <?php if($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <form action="crear.php" method="POST" id="formProducto">
        <div class="form-group">
            <label>Código de Barras</label>
            <div class="input-group">
                <i class="fa-solid fa-barcode"></i>
                <input type="text" name="codigo_barras" class="form-control" placeholder="Escanea o escribe el código" required autofocus>
            </div>
        </div>

        <div class="form-group">
            <label>Nombre del Producto</label>
            <div class="input-group">
                <i class="fa-solid fa-tag"></i>
                <input type="text" name="nombre" class="form-control" placeholder="Ej: Alfajor de Chocolate" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label>Precio Venta</label>
                <div class="input-group">
                    <i class="fa-solid fa-dollar-sign"></i>
                    <input type="number" step="0.01" name="precio" class="form-control" placeholder="0.00" required>
                </div>
            </div>

            <div class="form-group">
                <label>Stock Inicial</label>
                <div class="input-group">
                    <i class="fa-solid fa-cubes"></i>
                    <input type="number" name="stock" class="form-control" placeholder="0" required>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-submit">
            <i class="fa-solid fa-save"></i> GUARDAR PRODUCTO
        </button>
    </form>

    <a href="index_productos.php" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Volver al Inventario
    </a>
</div>
<script>
    // Esperamos a que la página cargue
    document.addEventListener('DOMContentLoaded', function() {
        // Buscamos si existe el mensaje de éxito en la pantalla
        const alertaExito = document.querySelector('.alert-success');
        const formulario = document.getElementById('formProducto');
        const inputCodigo = document.querySelector('input[name="codigo_barras"]');

        if (alertaExito) {
            // Si hubo éxito, limpiamos el formulario
            formulario.reset();
            
            // Ponemos el cursor de nuevo en el código de barras para el siguiente producto
            inputCodigo.focus();

            // Opcional: Desvanecer la alerta después de 3 segundos para limpiar la vista
            setTimeout(() => {
                alertaExito.style.transition = "opacity 0.5s ease";
                alertaExito.style.opacity = "0";
                setTimeout(() => alertaExito.remove(), 500);
            }, 3000);
        }
    });
</script>
</body>
</html>