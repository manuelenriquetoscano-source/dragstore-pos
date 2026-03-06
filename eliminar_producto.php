<?php
// Archivo: eliminar_producto.php
require_once 'config/database.php';
require_once 'models/Producto.php';

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $producto = new Producto($db);
    
    $producto->id = $_GET['id'];

    if ($producto->eliminar()) {
        header("Location: index.php?msg=eliminado");
    } else {
        echo "Error al eliminar.";
    }
}
?>