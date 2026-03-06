<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Drugstore POS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    :root {
        --primary: #2c3e50;
        --success: #27ae60;
        --info: #3498db;
        --warning: #f1c40f;
        --danger: #e74c3c;
        --glass-bg: rgba(255, 255, 255, 0.62);
        --glass-bg-strong: rgba(255, 255, 255, 0.75);
        --glass-border: rgba(255, 255, 255, 0.45);
        --glass-shadow: 0 22px 40px -24px rgba(44, 62, 80, 0.45);
        --glass-blur: 12px;
    }

    body {
        font-family: 'Nunito Sans', sans-serif;
        background:
            radial-gradient(circle at 18% 20%, rgba(52, 152, 219, 0.2), transparent 42%),
            radial-gradient(circle at 80% 8%, rgba(39, 174, 96, 0.16), transparent 34%),
            linear-gradient(145deg, #eaf2fb 0%, #f7fbff 45%, #edf6f1 100%);
        margin: 0;
        min-height: 100vh;
        color: #334155;
    }

    .container {
        max-width: 1200px;
        margin: 60px auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        padding: 0 20px;
    }

    .card {
        background: var(--glass-bg);
        padding: 50px 30px;
        border-radius: 20px;
        text-align: center;
        text-decoration: none;
        color: #334155;
        display: flex;
        flex-direction: column;
        transition: all 0.4s ease;
        box-shadow: var(--glass-shadow);
        border: 1px solid var(--glass-border);
        position: relative;
        backdrop-filter: blur(var(--glass-blur));
        -webkit-backdrop-filter: blur(var(--glass-blur));
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-10px) scale(1.01);
        background: var(--glass-bg-strong);
        box-shadow: 0 28px 45px -24px rgba(44, 62, 80, 0.5);
    }

    .card::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(140deg, rgba(255, 255, 255, 0.28), transparent 55%);
        pointer-events: none;
    }

    .icon-wrapper {
        font-size: 55px;
        margin-bottom: 25px;
        transition: transform 0.3s ease;
        position: relative;
        z-index: 1;
    }

    .card:hover .icon-wrapper {
        transform: scale(1.1);
    }

    .sales .icon-wrapper { color: var(--success); }
    .inventory .icon-wrapper { color: var(--info); }
    .reports .icon-wrapper { color: var(--warning); }

    .card h3 {
        margin: 0 0 12px 0;
        font-weight: 800;
        font-size: 24px;
        color: var(--primary);
        position: relative;
        z-index: 1;
    }

    .card p {
        margin: 0;
        font-size: 16px;
        color: #64748b;
        font-weight: 400;
        position: relative;
        z-index: 1;
    }

    .badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(231, 76, 60, 0.9);
        color: white;
        padding: 5px 12px;
        border-radius: 9999px;
        font-size: 13px;
        font-weight: 700;
        box-shadow: 0 4px 6px -1px rgba(231, 76, 60, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.4);
        z-index: 2;
    }

    .search-wrapper {
        max-width: 600px;
        margin: 20px auto;
        position: relative;
        padding: 0 20px;
    }

    .search-box {
        background: var(--glass-bg);
        display: flex;
        align-items: center;
        padding: 15px 25px;
        border-radius: 50px;
        box-shadow: var(--glass-shadow);
        border: 1px solid var(--glass-border);
        backdrop-filter: blur(var(--glass-blur));
        -webkit-backdrop-filter: blur(var(--glass-blur));
    }

    .search-icon {
        color: var(--info);
        font-size: 20px;
        margin-right: 15px;
    }

    #quick-search {
        border: none;
        outline: none;
        width: 100%;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 18px;
        color: var(--primary);
        background: transparent;
    }

    #quick-search::placeholder { color: #64748b; }

    .search-results-container {
        position: absolute;
        top: 100%;
        left: 20px;
        right: 20px;
        background: var(--glass-bg-strong);
        border-radius: 15px;
        box-shadow: var(--glass-shadow);
        border: 1px solid var(--glass-border);
        backdrop-filter: blur(var(--glass-blur));
        -webkit-backdrop-filter: blur(var(--glass-blur));
        z-index: 1000;
        margin-top: 10px;
        max-height: 300px;
        overflow-y: auto;
        display: none;
    }

    .result-item {
        padding: 12px 20px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.24);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .result-item:last-child { border: none; }

    .result-info { font-weight: 700; color: var(--primary); }
    .result-stock {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 800;
    }

    .stock-ok { background: #dcfce7; color: #166534; }
    .stock-low { background: #fee2e2; color: #991b1b; }

    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        .card,
        .search-box,
        .search-results-container {
            background: rgba(255, 255, 255, 0.92);
        }
    }

    @media (max-width: 768px) {
        .container {
            gap: 20px;
            margin: 30px auto;
        }

        .card {
            padding: 36px 22px;
        }
    }
</style>

<!-- barra de búsqueda rápida -->

<div class="search-wrapper">
    <div class="search-box">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" id="quick-search" placeholder="Busca stock rápido (nombre o código)..." autocomplete="off">
    </div>
    <div id="search-results" class="search-results-container"></div>
</div>

<div class="container">
    <a href="views/ventas/caja.php" class="card sales">
        <div class="icon-wrapper">
            <i class="fa-solid fa-cash-register"></i>
        </div>
        <h3>Punto de Venta</h3>
        <p>Abrir la caja y procesar cobros rápidamente con escáner.</p>
    </a>

    <a href="views/productos/index_productos.php" class="card inventory">
        <?php if(isset($alertas) && $alertas > 0): ?>
            <span class="badge"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $alertas; ?> críticos</span>
        <?php endif; ?>
        <div class="icon-wrapper">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
        <h3>Inventario</h3>
        <p>Controlar existencias, actualizar precios y gestionar stock.</p>
    </a>

    <a href="views/ventas/reporte.php" class="card reports">
        <div class="icon-wrapper">
            <i class="fa-solid fa-chart-line"></i>
        </div>
        <h3>Reporte Diario</h3>
        <p>Analizar ingresos y volumen de ventas del día de hoy.</p>
    </a>
</div>

<div class="footer">
    Sistema de Gestión de Drugstore v1.0 - Conectado a MySQL ✅
</div>
<script>
    const searchInput = document.getElementById('quick-search');
    const resultsContainer = document.getElementById('search-results');

    searchInput.addEventListener('input', function() {
    const query = this.value.trim();

        // Si el usuario borra la búsqueda, limpiamos y ocultamos de inmediato
        if (query.length === 0) {
            resultsContainer.innerHTML = '';
            resultsContainer.style.display = 'none';
            return; 
        }

        if (query.length > 0) {
            fetch('buscar_ajax.php?codigo=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    
                    if (data.status === 'success') {
                        resultsContainer.style.display = 'block';
                        
                        // Convertimos a array siempre para manejar 1 o varios productos
                        const productos = Array.isArray(data.data) ? data.data : [data.data];
                        
                        productos.forEach(prod => {
                            const stockClass = parseInt(prod.stock) < 5 ? 'stock-low' : 'stock-ok';
                            
                            // Usamos || para que si codigo_barras no existe, no muestre "undefined"
                            const codigo = prod.codigo_barras || 'S/C'; 

                            resultsContainer.innerHTML += `
                                <div class="result-item">
                                    <div class="result-info">
                                        <strong>${prod.nombre}</strong> <br>
                                        <small style="color:#64748b; font-weight:400;">Cód: ${codigo}</small>
                                    </div>
                                    <div class="result-stock ${stockClass}">
                                        Stock: ${prod.stock}
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        // Opcional: Mostrar "No se encontraron resultados"
                        resultsContainer.style.display = 'none';
                    }
                })
                .catch(err => {
                    console.error("Error en búsqueda rápida:", err);
                });
        } else {
            resultsContainer.style.display = 'none';
        }
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
</script>
</body>
</html>
