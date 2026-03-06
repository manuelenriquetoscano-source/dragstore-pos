<?php

require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../controllers/VentaController.php';
require_once __DIR__ . '/../services/ProductoService.php';
require_once __DIR__ . '/../services/VentaService.php';

function testAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function runTest(string $name, callable $fn): array
{
    try {
        $fn();
        return ['name' => $name, 'ok' => true, 'error' => null];
    } catch (Throwable $e) {
        return ['name' => $name, 'ok' => false, 'error' => $e->getMessage()];
    }
}

$tests = [];

$tests[] = runTest('ProductoController: termino vacio', function () {
    $pdo = new PDO('sqlite::memory:');
    $controller = new ProductoController($pdo);
    $result = $controller->buscarParaCaja('   ');
    testAssert($result['ok'] === false, 'Debe fallar término vacío');
    testAssert($result['statusCode'] === 400, 'Código esperado 400');
});

$tests[] = runTest('VentaController: JSON invalido', function () {
    $pdo = new PDO('sqlite::memory:');
    $controller = new VentaController($pdo);
    $result = $controller->procesarDesdeJson('{invalid-json}');
    testAssert($result['ok'] === false, 'Debe fallar JSON inválido');
    testAssert($result['statusCode'] === 400, 'Código esperado 400');
});

$tests[] = runTest('ProductoService: validacion de precio', function () {
    $pdo = new PDO('sqlite::memory:');
    $service = new ProductoService($pdo);
    $result = $service->crear([
        'codigo_barras' => '123',
        'nombre' => 'Producto test',
        'precio' => 0,
        'stock' => 10
    ]);
    testAssert($result['ok'] === false, 'Debe fallar precio inválido');
});

$tests[] = runTest('VentaService: carrito vacio', function () {
    $pdo = new PDO('sqlite::memory:');
    $service = new VentaService($pdo);
    $result = $service->registrar([], 100);
    testAssert($result['ok'] === false, 'Debe fallar carrito vacío');
});

$passed = 0;
$failed = 0;

foreach ($tests as $test) {
    if ($test['ok']) {
        echo "[OK] {$test['name']}\n";
        $passed++;
    } else {
        echo "[FAIL] {$test['name']} - {$test['error']}\n";
        $failed++;
    }
}

echo "\nResumen: $passed OK, $failed FAIL\n";
exit($failed > 0 ? 1 : 0);

