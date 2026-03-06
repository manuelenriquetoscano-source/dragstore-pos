<?php
// Archivo: guardar_producto.php

require_once 'config/database.php';
require_once 'models/Producto.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    $producto = new Producto($db);

    // Asignamos los valores recibidos del formulario
    $producto->codigo_barras = $_POST['codigo_barras'];
    $producto->nombre = $_POST['nombre'];
    $producto->precio = $_POST['precio'];
    $producto->stock = $_POST['stock'];

    // Intentamos guardar
    if ($producto->crear()) {
        // Redirección con un mensaje de éxito
        echo "<script>
                alert('¡Producto guardado correctamente!');
                window.location.href='index.php';
              </script>";
    } else {
        echo "Hubo un error al guardar el producto. Verifica que el código de barras no esté duplicado.";
    }
} else {
    // Si alguien intenta entrar a este archivo sin enviar datos, lo mandamos al inicio
    header("Location: index.php");
}
?>