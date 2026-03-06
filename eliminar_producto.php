<?php
require_once 'config/bootstrap.php';
requireLogin(['admin']);
require_once 'controllers/ProductoController.php';

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $controller = new ProductoController($db);
    $id = (int)$_GET['id'];

    if ($controller->eliminarProducto($id)) {
        header("Location: /dragstore-pos/views/productos/index_productos.php?msg=eliminado");
    } else {
        header("Location: /dragstore-pos/views/productos/index_productos.php?msg=error");
    }
    exit;
}
