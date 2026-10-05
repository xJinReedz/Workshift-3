<?php
/**
 * Database Migrator and Seed Utility
 */

namespace WorkShift\Database;

use PDO;
use Exception;

class Migrator
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public static function createDatabaseIfNotExists(array $dbConfig): void
    {
        if ($dbConfig['driver'] === 'mysql') {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $dbname = $dbConfig['database'];
            $charset = $dbConfig['charset'] ?? 'utf8mb4';
            $collation = $dbConfig['collation'] ?? 'utf8mb4_unicode_ci';
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET {$charset} COLLATE {$collation}");
        } elseif ($dbConfig['driver'] === 'sqlite') {
            $sqlitePath = $dbConfig['sqlite_path'];
            $dir = dirname($sqlitePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            if (!file_exists($sqlitePath)) {
                touch($sqlitePath);
            }
        }
    }

    public function isInstalled(): bool
    {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'users'");
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            try {
                $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
                return (bool)$stmt->fetch();
            } catch (Exception $ex) {
                return false;
            }
        }
    }

    public function runSchema(string $schemaFile): void
    {
        if (!file_exists($schemaFile)) {
            throw new Exception("Schema file not found: {$schemaFile}");
        }
        $sql = file_get_contents($schemaFile);
        $this->executeSqlScript($sql);
    }

    public function runSeed(string $seedFile): void
    {
        if (!file_exists($seedFile)) {
            throw new Exception("Seed file not found: {$seedFile}");
        }
        $sql = file_get_contents($seedFile);
        $this->executeSqlScript($sql);
    }

    private function executeSqlScript(string $sql): void
    {
        // First disable foreign key checks
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Remove SQL comments (-- style) but keep content inside strings
        $lines = explode("\n", $sql);
        $cleanedLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }
            $cleanedLines[] = $line;
        }
        $cleanSql = implode("\n", $cleanedLines);

        // Split on semicolons - handles both ";\n" and trailing ";"
        $statements = preg_split('/;\s*\n|;\s*$/', $cleanSql, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (!empty($stmt) && strtoupper($stmt) !== 'SET FOREIGN_KEY_CHECKS = 0' && strtoupper($stmt) !== 'SET FOREIGN_KEY_CHECKS = 1') {
                $this->pdo->exec($stmt);
            }
        }

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
}
