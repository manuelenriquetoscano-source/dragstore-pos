<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/logger.php';
require_once __DIR__ . '/helpers/migrator.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Solo disponible por CLI.\n";
    exit(1);
}

$flags = array_flip(array_slice($argv, 1));
$skipLint = isset($flags['--skip-lint']);
$skipTests = isset($flags['--skip-tests']);
$skipMigrate = isset($flags['--skip-migrate']);
$skipHealth = isset($flags['--skip-health']);

function out(string $message): void
{
    echo $message . PHP_EOL;
}

function failDeploy(string $message): void
{
    appLog('error', 'deploy.failed', ['message' => $message]);
    fwrite(STDERR, "[FAIL] $message\n");
    exit(1);
}

function runCommand(string $command, string $stepName): void
{
    out("[RUN ] $stepName");
    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        failDeploy("$stepName falló con código $exitCode");
    }
    out("[ OK ] $stepName");
}

function runPhpLint(string $rootDir): void
{
    out("[RUN ] Lint PHP");
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        /** @var SplFileInfo $fileInfo */
        if (strtolower($fileInfo->getExtension()) !== 'php') {
            continue;
        }
        $path = $fileInfo->getPathname();
        $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path);
        exec($cmd, $output, $exitCode);
        if ($exitCode !== 0) {
            failDeploy("Lint falló en $path");
        }
    }
    out("[ OK ] Lint PHP");
}

function runMigrations(): void
{
    out("[RUN ] Migraciones");
    $database = new Database();
    $db = $database->getConnection();
    if (!$db instanceof PDO) {
        failDeploy('No se pudo conectar a DB para migraciones');
    }

    try {
        $executed = runPendingMigrations($db, __DIR__ . '/migrations', function (string $line): void {
            out("      $line");
        });
        if (count($executed) === 0) {
            out("      No hay migraciones pendientes.");
        } else {
            out("      Migraciones ejecutadas: " . count($executed));
        }
    } catch (Throwable $e) {
        failDeploy('Migraciones fallaron: ' . $e->getMessage());
    }

    out("[ OK ] Migraciones");
}

function runHealthCheck(): void
{
    out("[RUN ] Health interno");
    $database = new Database();
    $db = $database->getConnection();
    if (!$db instanceof PDO) {
        failDeploy('Health falló: conexión DB nula');
    }

    try {
        $db->query('SELECT 1');
    } catch (Throwable $e) {
        failDeploy('Health falló: ' . $e->getMessage());
    }
    out("[ OK ] Health interno");
}

appLog('info', 'deploy.started', [
    'skip_lint' => $skipLint,
    'skip_tests' => $skipTests,
    'skip_migrate' => $skipMigrate,
    'skip_health' => $skipHealth
]);

out("== Deploy Script ==");
out("Proyecto: dragstore-pos");
out("Fecha: " . date('c'));

if (!$skipLint) {
    runPhpLint(__DIR__);
} else {
    out("[SKIP] Lint PHP");
}

if (!$skipTests) {
    runCommand(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/tests/run.php'), 'Tests');
} else {
    out("[SKIP] Tests");
}

if (!$skipMigrate) {
    runMigrations();
} else {
    out("[SKIP] Migraciones");
}

if (!$skipHealth) {
    runHealthCheck();
} else {
    out("[SKIP] Health interno");
}

appLog('info', 'deploy.finished', ['status' => 'ok']);
out("== Deploy completado ==");

