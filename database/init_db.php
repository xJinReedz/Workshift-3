<?php
/**
 * CLI Database Initializer
 * Run: php database/init_db.php [--seed]
 */

require_once __DIR__ . '/Migrator.php';
$config = require dirname(__DIR__) . '/config/config.php';

use WorkShift\Database\Migrator;

echo "== WorkShift Database Setup ==\n";

try {
    echo "Checking / Creating database '{$config['database']['database']}'...\n";
    Migrator::createDatabaseIfNotExists($config['database']);

    $dsn = "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['database']};charset={$config['database']['charset']}";
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);

    $migrator = new Migrator($pdo, $config['database']);

    echo "Applying schema (database/schema.sql)...\n";
    $migrator->runSchema(__DIR__ . '/schema.sql');
    echo "Schema successfully applied!\n";

    echo "Applying seeds (database/seed.sql)...\n";
    $migrator->runSeed(__DIR__ . '/seed.sql');
    echo "Seeds successfully loaded!\n";

    echo "\nSetup Complete!\n";
    echo "Demo Freelancer Login:\n";
    echo "  Email:    alex@studioclickup.com\n";
    echo "  Password: password123\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
