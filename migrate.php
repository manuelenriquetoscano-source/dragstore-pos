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
$dryRun = isset($flags['--dry-run']);

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    fwrite(STDERR, "No se pudo conectar a la base de datos.\n");
    exit(1);
}

try {
    if ($dryRun) {
        $pending = getPendingMigrations($db, __DIR__ . '/migrations');
        if (count($pending) === 0) {
            echo "[DRY-RUN] No hay migraciones pendientes.\n";
        } else {
            echo "[DRY-RUN] Migraciones pendientes:\n";
            foreach ($pending as $migration) {
                echo " - $migration\n";
            }
            echo "[DRY-RUN] Total pendientes: " . count($pending) . "\n";
        }
        exit(0);
    }

    $executed = runPendingMigrations($db, __DIR__ . '/migrations', function (string $line): void {
        echo $line . PHP_EOL;
    });
} catch (Throwable $e) {
    appLog('error', 'Migration failed', ['error' => $e->getMessage()]);
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

if (count($executed) === 0) {
    echo "No hay migraciones pendientes.\n";
} else {
    echo "Migraciones ejecutadas: " . count($executed) . "\n";
}
