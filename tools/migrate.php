<?php
/**
 * PostgreSQL Database Migrator Runner for WorkShift
 * Run via CLI: php tools/migrate.php
 * Or via browser: http://your-domain/tools/migrate.php
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Load environment variables
$envPath = ROOT_PATH . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// PSR-4 Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'WorkShift\\';
    $baseDir = APP_PATH . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once APP_PATH . '/Helpers/functions.php';

use WorkShift\Core\Database;

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>WorkShift Migration Runner</title></head><body style='max-width:800px;margin:40px auto;font-family:sans-serif;'>";
    echo "<h2>WorkShift Database Migration Runner</h2>";
} else {
    echo "===================================================\n";
    echo "  WorkShift PostgreSQL Migration Runner\n";
    echo "===================================================\n\n";
}

try {
    $pdo = Database::getConnection();

    // Ensure schema_migrations exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id SERIAL PRIMARY KEY,
            filename TEXT NOT NULL UNIQUE,
            executed_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $executed = $pdo->query("SELECT filename FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);

    $migrationDir = ROOT_PATH . '/database/postgres';
    $files = glob($migrationDir . '/*.sql');
    sort($files);

    $appliedCount = 0;
    foreach ($files as $file) {
        $filename = basename($file);
        if (in_array($filename, $executed, true)) {
            $msg = "Skipping {$filename} (already applied)";
            if ($isCli) echo "[SKIP] {$msg}\n"; else echo "<div style='color:#6c757d;'>{$msg}</div>";
            continue;
        }

        $sql = file_get_contents($file);
        $pdo->beginTransaction();
        try {
            $pdo->exec($sql);
            $stmt = $pdo->prepare("INSERT INTO schema_migrations (filename) VALUES (?)");
            $stmt->execute([$filename]);
            $pdo->commit();
            $appliedCount++;

            $msg = "Successfully applied {$filename}";
            if ($isCli) echo "[OK]   {$msg}\n"; else echo "<div style='color:#198754;font-weight:bold;'>{$msg}</div>";
        } catch (\Exception $e) {
            $pdo->rollBack();
            $msg = "Error applying {$filename}: " . $e->getMessage();
            if ($isCli) echo "[FAIL] {$msg}\n"; else echo "<div style='color:#dc3545;font-weight:bold;'>{$msg}</div>";
            break;
        }
    }

    $summary = "Migrations completed. Applied {$appliedCount} new file(s).";
    if ($isCli) echo "\n{$summary}\n"; else echo "<h3 style='color:#0d6efd;'>{$summary}</h3></body></html>";
} catch (\Exception $e) {
    $err = "Migration Error: " . $e->getMessage();
    if ($isCli) echo "[FATAL] {$err}\n"; else echo "<div style='color:#dc3545;font-weight:bold;'>{$err}</div></body></html>";
}
