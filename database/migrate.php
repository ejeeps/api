<?php
// Simple PHP migration runner for MySQL (PDO)

// Or CLI: php database/migrate.php

declare(strict_types=1);

// Ensure errors are visible during migration
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/db.php'; // provides $pdo

function respond(string $message): void
{
    $isCli = php_sapi_name() === 'cli';
    if ($isCli) {
        echo $message . PHP_EOL;
    } else {
        echo nl2br(htmlspecialchars($message)) . "<br>";
    }
}

try {
    // Ensure migrations table exists
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS migrations (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			filename VARCHAR(255) NOT NULL UNIQUE,
			applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // Collect existing migrations
    $appliedStmt = $pdo->query('SELECT filename FROM migrations');
    $applied = $appliedStmt ? $appliedStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    $appliedSet = array_flip($applied);

    $migrationsDir = __DIR__ . '/migrations';
    if (!is_dir($migrationsDir)) {
        mkdir($migrationsDir, 0777, true);
    }

    $files = glob($migrationsDir . '/*.sql');
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    if (!$files) {
        respond('No migration files found in database/migrations.');
        exit(0);
    }

    $ran = 0;
    foreach ($files as $file) {
        $base = basename($file);
        if (isset($appliedSet[$base])) {
            continue;
        }

        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException('Unable to read migration: ' . $base);
        }

        respond("Applying: {$base}");
        try {
            // Some DDL causes implicit commits in MySQL; wrap safely
            $pdo->beginTransaction();
            $pdo->exec($sql);
            $ins = $pdo->prepare('INSERT INTO migrations (filename) VALUES (:f)');
            $ins->execute([':f' => $base]);
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
            $ran++;
            respond("Applied: {$base}");
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            respond('Error applying ' . $base . ': ' . $e->getMessage());
            throw $e;
        }
    }

    if ($ran === 0) {
        respond('No new migrations to run.');
    } else {
        respond("Migrations completed. Ran {$ran} migration(s).");
    }
} catch (Throwable $e) {
    respond('Migration failed: ' . $e->getMessage());
    exit(1);
}