<?php
require_once '../../config/bootstrap.php';
requireLogin(['admin']);
require_once '../../controllers/ProductoController.php';

$mensaje = isset($_GET['mensaje']) ? (string)$_GET['mensaje'] : '';
$tipo_mensaje = isset($_GET['tipo']) ? (string)$_GET['tipo'] : '';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    $controller = new ProductoController($db);
    $result = $controller->crearProductoDesdeRequest($_POST);
    $mensaje = $result['message'];
    $tipo_mensaje = $result['ok'] ? 'success' : 'danger';
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
        :root { --primary:#2c3e50; --success:#27ae60; --bg:#f8fafc; }
        body { font-family:'Nunito Sans',sans-serif; background:var(--bg); margin:0; padding:12px; color:#334155; }
        .form-container {
            max-width: 700px;
            margin: 12px auto;
            background: white;
            padding: 24px 16px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .header { text-align:center; margin-bottom:20px; }
        .header i { font-size:40px; color:var(--success); margin-bottom:10px; }
        .header h1 { margin:0; font-size:22px; color:var(--primary); }
        .section-title {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.03em;
            color: #475569;
            margin: 8px 0 12px 0;
            text-transform: uppercase;
        }
        .form-group { margin-bottom: 16px; }
        .form-group label { display:block; margin-bottom:8px; font-weight:600; font-size:14px; }
        .form-grid { display:grid; grid-template-columns:1fr; gap:12px; }
        .input-group { position:relative; display:flex; align-items:center; }
        .input-group i { position:absolute; left:15px; color:#94a3b8; }
        .form-control {
            width:100%;
            padding:12px 12px 12px 45px;
            border:1.5px solid #e2e8f0;
            border-radius:10px;
            font-family:'Nunito Sans',sans-serif;
            font-size:16px;
            transition: all 0.2s ease;
        }
        .form-control:focus { outline:none; border-color:var(--success); box-shadow:0 0 0 4px rgba(39,174,96,0.1); }
        .helper { font-size: 12px; color: #64748b; margin-top: 5px; }
        .btn-submit {
            width:100%;
            background:var(--success);
            color:white;
            padding:13px;
            border:none;
            border-radius:10px;
            font-size:16px;
            font-weight:800;
            cursor:pointer;
            margin-top:10px;
        }
        .btn-submit:hover { background:#219150; }
        .btn-back { display:block; text-align:center; margin-top:18px; color:#64748b; text-decoration:none; font-size:14px; font-weight:600; }
        .alert { padding:15px; border-radius:10px; margin-bottom:18px; text-align:center; font-weight:700; }
        .alert-success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
        .alert-danger { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
        @media (min-width: 769px) {
            body { padding:20px; }
            .form-container { margin: 24px auto; padding: 34px; border-radius:20px; }
            .header h1 { font-size:24px; }
            .form-grid.two { grid-template-columns:1fr 1fr; }
            .form-grid.three { grid-template-columns:1fr 1fr 1fr; }
        }
    </style>
</head>
<body>
<div class="form-container">
    <div class="header">
        <i class="fa-solid fa-circle-plus"></i>
        <h1>Nuevo Producto</h1>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo htmlspecialchars($tipo_mensaje, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form action="crear.php" method="POST" id="formProducto">
        <div class="section-title">Datos principales</div>
        <div class="form-group">
            <label>Codigo de Barras</label>
            <div class="input-group">
                <i class="fa-solid fa-barcode"></i>
                <input type="text" name="codigo_barras" class="form-control" placeholder="Escanea o escribe el codigo" required autofocus>
            </div>
        </div>
        <div class="form-group">
            <label>Nombre del Producto</label>
            <div class="input-group">
                <i class="fa-solid fa-tag"></i>
                <input type="text" name="nombre" class="form-control" placeholder="Ej: Shampoo 400ml" required>
            </div>
        </div>
        <div class="form-grid three">
            <div class="form-group">
                <label>Precio Venta</label>
                <div class="input-group">
                    <i class="fa-solid fa-dollar-sign"></i>
                    <input type="number" step="0.01" min="0.01" name="precio" class="form-control" placeholder="0.00" required>
                </div>
            </div>
            <div class="form-group">
                <label>Stock Inicial</label>
                <div class="input-group">
                    <i class="fa-solid fa-cubes"></i>
                    <input type="number" min="0" name="stock" class="form-control" placeholder="0" required>
                </div>
            </div>
            <div class="form-group">
                <label>Stock Minimo</label>
                <div class="input-group">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <input type="number" min="1" name="stock_minimo" class="form-control" placeholder="5" value="5" required>
                </div>
            </div>
        </div>

        <div class="section-title">Lote inicial (opcional)</div>
        <div class="form-grid three">
            <div class="form-group">
                <label>Numero de Lote</label>
                <div class="input-group">
                    <i class="fa-solid fa-hashtag"></i>
                    <input type="text" name="numero_lote" class="form-control" placeholder="L-2026-001">
                </div>
            </div>
            <div class="form-group">
                <label>Vencimiento</label>
                <div class="input-group">
                    <i class="fa-solid fa-calendar-days"></i>
                    <input type="date" name="fecha_vencimiento" class="form-control" style="padding-left:45px;">
                </div>
            </div>
            <div class="form-group">
                <label>Cantidad de Lote</label>
                <div class="input-group">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <input type="number" min="0" name="cantidad_lote" class="form-control" placeholder="Usa stock inicial">
                </div>
            </div>
        </div>
        <div class="helper">Si completas lote, debes informar numero y fecha de vencimiento.</div>

        <button type="submit" class="btn-submit">
            <i class="fa-solid fa-save"></i> GUARDAR PRODUCTO
        </button>
    </form>

    <a href="index_productos.php" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Volver al Inventario
    </a>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const alertaExito = document.querySelector('.alert-success');
    const form = document.getElementById('formProducto');
    const inputCodigo = document.querySelector('input[name="codigo_barras"]');
    if (alertaExito && form && inputCodigo) {
        form.reset();
        inputCodigo.focus();
        setTimeout(() => {
            alertaExito.style.transition = 'opacity 0.4s ease';
            alertaExito.style.opacity = '0';
            setTimeout(() => alertaExito.remove(), 400);
        }, 2600);
    }
});
</script>
</body>
</html>
