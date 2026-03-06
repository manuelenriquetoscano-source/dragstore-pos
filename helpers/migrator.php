<?php

function ensureMigrationsTable(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
    )");
}

function runPendingMigrations(PDO $db, string $migrationsPath, ?callable $output = null): array
{
    ensureMigrationsTable($db);

    $files = glob(rtrim($migrationsPath, '/\\') . '/*.sql');
    sort($files);

    $applied = $db->query("SELECT migration FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);
    $appliedSet = array_flip($applied ?: []);

    $executed = [];
    foreach ($files as $file) {
        $name = basename($file);
        if (isset($appliedSet[$name])) {
            continue;
        }

        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException("No se pudo leer migración: $name");
        }

        $db->beginTransaction();
        try {
            $db->exec($sql);
            $stmt = $db->prepare("INSERT INTO schema_migrations (migration) VALUES (:migration)");
            $stmt->execute([':migration' => $name]);
            $db->commit();
            $executed[] = $name;
            if ($output) {
                $output("OK  $name");
            }
        } catch (Throwable $e) {
            $db->rollBack();
            throw new RuntimeException("ERROR $name: {$e->getMessage()}", 0, $e);
        }
    }

    return $executed;
}

