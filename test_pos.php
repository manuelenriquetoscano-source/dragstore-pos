<?php
// Archivo: test_pos.php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🧪 Diagnóstico del Sistema POS - Dragstore</h2>";
echo "<hr>";

// TEST 1: Conexión a la Base de Datos
echo "<strong>1. Probando Conexión:</strong> ";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        echo "<span style='color:green;'>✅ Conexión Exitosa (MySQL está online)</span>";
    }
} catch (Exception $e) {
    echo "<span style='color:red;'>❌ Error en Configuración: " . $e->getMessage() . "</span>";
}

echo "<br><br>";

// TEST 2: Existencia de Archivos Críticos
echo "<strong>2. Verificando Archivos:</strong><br>";
$archivos = [
    'config/database.php',
    'models/Producto.php',
    'index.php',
    'guardar_producto.php'
];

foreach ($archivos as $archivo) {
    if (file_exists($archivo)) {
        echo "✅ $archivo encontrado.<br>";
    } else {
        echo "❌ <span style='color:red;'>$archivo NO encontrado.</span><br>";
    }
}

echo "<br>";

// TEST 3: Operaciones del Modelo Producto
echo "<strong>3. Probando Modelo Producto:</strong> ";
try {
    require_once 'models/Producto.php';
    $prodTest = new Producto($db);
    $stmt = $prodTest->leerTodo();
    
    if($stmt) {
        $conteo = $stmt->rowCount();
        echo "<span style='color:green;'>✅ Modelo funcionando. Tienes <strong>$conteo</strong> productos en base de datos.</span>";
    }
} catch (Error $e) {
    echo "<span style='color:red;'>❌ Error en el Modelo: " . $e->getMessage() . "</span>";
}

echo "<hr>";
echo "<a href='index.php'>Ir al Inicio del Sistema</a>";
?>