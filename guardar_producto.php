<?php
require_once 'config/bootstrap.php';
requireLogin(['admin']);
require_once 'controllers/ProductoController.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    $controller = new ProductoController($db);
    $result = $controller->crearProductoDesdeRequest($_POST);
    $target = 'views/productos/crear.php?tipo=' . ($result['ok'] ? 'success' : 'danger') .
        '&mensaje=' . urlencode($result['message']);
    header('Location: /dragstore-pos/' . $target);
    exit;
} else {
    header("Location: /dragstore-pos/index.php");
    exit;
}
