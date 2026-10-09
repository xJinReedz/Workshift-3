<?php
/**
 * PostgreSQL Database Connection Manager (PDO pgsql for Supabase)
 */

namespace WorkShift\Core;

use PDO;
use Exception;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__, 2) . '/config/config.php';
            $dbUrl = $config['database']['url'] ?? getenv('DATABASE_URL') ?: '';

            if (!empty($dbUrl) && (str_starts_with($dbUrl, 'postgres://') || str_starts_with($dbUrl, 'postgresql://'))) {
                $parts = parse_url($dbUrl);
                $host = $parts['host'] ?? '127.0.0.1';
                $port = $parts['port'] ?? 5432;
                $dbname = ltrim($parts['path'] ?? 'postgres', '/');
                $user = isset($parts['user']) ? rawurldecode($parts['user']) : '';
                $pass = isset($parts['pass']) ? rawurldecode($parts['pass']) : '';

                // Enable sslmode=require for remote Supabase connections unless localhost
                $sslMode = ($host === '127.0.0.1' || $host === 'localhost') ? 'disable' : 'require';
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslMode}";

                try {
                    self::$instance = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        // PDO::ATTR_EMULATE_PREPARES => true is required for Supabase Transaction Pooler (port 6543)
                        PDO::ATTR_EMULATE_PREPARES => true,
                    ]);
                    self::$instance->exec("SET timezone TO 'UTC';");
                } catch (Exception $e) {
                    throw new Exception("Database Connection Failure: " . $e->getMessage(), (int)$e->getCode(), $e);
                }
            } else {
                // Fallback to SQLite or local Postgres if DATABASE_URL is not set yet
                $sqlitePath = dirname(__DIR__, 2) . '/storage/database.sqlite';
                self::$instance = new PDO("sqlite:" . $sqlitePath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$instance->exec("PRAGMA foreign_keys = ON;");
            }
        }

        return self::$instance;
    }

    public static function setConnection(PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    public static function beginTransaction(): bool
    {
        $pdo = self::getConnection();
        return $pdo->inTransaction() ? true : $pdo->beginTransaction();
    }

    public static function commit(): bool
    {
        $pdo = self::getConnection();
        return $pdo->inTransaction() ? $pdo->commit() : true;
    }

    public static function rollBack(): bool
    {
        $pdo = self::getConnection();
        return $pdo->inTransaction() ? $pdo->rollBack() : true;
    }
}
