<?php
/**
 * MySQL to Supabase PostgreSQL Migration Tool for WorkShift
 * Reads existing MySQL database data and migrates it to PostgreSQL tables,
 * mapping integer auto-increment IDs to PostgreSQL UUIDs inside a transaction.
 *
 * Usage via CLI: php tools/mysql_to_postgres.php
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

$isCli = (php_sapi_name() === 'cli');

function outputMsg(string $msg): void {
    global $isCli;
    if ($isCli) {
        echo $msg . "\n";
    } else {
        echo "<div style='font-family:monospace; margin-bottom:4px;'>" . htmlspecialchars($msg) . "</div>";
    }
}

outputMsg("===================================================");
outputMsg("  WorkShift MySQL to Supabase PostgreSQL Migrator  ");
outputMsg("===================================================");

$dbUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? '');
if (empty($dbUrl) || str_contains($dbUrl, 'placeholder')) {
    outputMsg("[ERROR] DATABASE_URL is not configured in .env. Please set valid Supabase connection string.");
    exit(1);
}

try {
    // 1. Connect to MySQL (Source)
    $config = require ROOT_PATH . '/config/config.php';
    $mysqlHost = getenv('DB_HOST') ?: '127.0.0.1';
    $mysqlPort = getenv('DB_PORT') ?: '3306';
    $mysqlDb   = getenv('DB_DATABASE') ?: 'workshift_db';
    $mysqlUser = getenv('DB_USERNAME') ?: 'root';
    $mysqlPass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';

    $mysqlDsn = "mysql:host={$mysqlHost};port={$mysqlPort};dbname={$mysqlDb};charset=utf8mb4";
    $mysqlPdo = new PDO($mysqlDsn, $mysqlUser, $mysqlPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    outputMsg("[OK] Connected to source MySQL database '{$mysqlDb}'.");

    // 2. Connect to PostgreSQL (Destination)
    $parts = parse_url($dbUrl);
    $pgHost = $parts['host'] ?? '127.0.0.1';
    $pgPort = $parts['port'] ?? 5432;
    $pgDb   = ltrim($parts['path'] ?? 'postgres', '/');
    $pgUser = isset($parts['user']) ? rawurldecode($parts['user']) : '';
    $pgPass = isset($parts['pass']) ? rawurldecode($parts['pass']) : '';

    $sslMode = ($pgHost === '127.0.0.1' || $pgHost === 'localhost') ? 'disable' : 'require';
    $pgDsn = "pgsql:host={$pgHost};port={$pgPort};dbname={$pgDb};sslmode={$sslMode}";
    $pgPdo = new PDO($pgDsn, $pgUser, $pgPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);
    outputMsg("[OK] Connected to destination PostgreSQL database '{$pgDb}'.");

    // Start transaction in PostgreSQL
    $pgPdo->beginTransaction();

    // ID Mapping lookup maps: [ 'table' => [ oldIntId => newUuidStr ] ]
    $idMap = [
        'users' => [],
        'clients' => [],
        'boards' => [],
        'stages' => [],
        'tasks' => [],
        'invoices' => [],
    ];

    // Helper to generate deterministic UUID from namespace & ID or random
    function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // A. Migrate Users -> Profiles
    $oldUsers = $mysqlPdo->query("SELECT * FROM users")->fetchAll();
    $insertedProfiles = 0;
    $stmtProfile = $pgPdo->prepare("
        INSERT INTO profiles (id, email, full_name, studio_name, default_hourly_rate, scheduling_link, plan, is_freelancer, avatar_url, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, true, ?, ?, ?)
        ON CONFLICT (email) DO UPDATE SET full_name = EXCLUDED.full_name
        RETURNING id
    ");

    foreach ($oldUsers as $u) {
        $newUuid = generateUuid();
        $idMap['users'][(int)$u['id']] = $newUuid;

        $stmtProfile->execute([
            $newUuid,
            $u['email'],
            $u['name'] ?? $u['email'],
            $u['company_name'] ?? null,
            $u['hourly_rate'] ?? 600.00,
            $u['scheduling_link'] ?? null,
            $u['plan'] ?? 'basic',
            $u['avatar_url'] ?? null,
            $u['created_at'] ?? date('Y-m-d H:i:s'),
            $u['updated_at'] ?? date('Y-m-d H:i:s'),
        ]);
        $insertedProfiles++;
    }
    outputMsg("[MIGRATED] Profiles (Users): {$insertedProfiles} row(s).");

    // B. Migrate Clients
    $oldClients = $mysqlPdo->query("SELECT * FROM clients")->fetchAll();
    $insertedClients = 0;
    $stmtClient = $pgPdo->prepare("
        INSERT INTO clients (id, owner_id, name, company_name, email, phone, portal_token, status, notes, hourly_rate, currency, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (portal_token) DO NOTHING
    ");

    foreach ($oldClients as $c) {
        $newUuid = generateUuid();
        $idMap['clients'][(int)$c['id']] = $newUuid;
        $ownerUuid = $idMap['users'][(int)$c['user_id']] ?? null;

        if ($ownerUuid) {
            $stmtClient->execute([
                $newUuid,
                $ownerUuid,
                $c['name'],
                $c['company_name'] ?? null,
                $c['email'],
                $c['phone'] ?? null,
                $c['portal_token'] ?? bin2hex(random_bytes(16)),
                $c['status'] ?? 'active',
                $c['notes'] ?? null,
                $c['hourly_rate'] ?? null,
                $c['currency'] ?? 'PHP',
                $c['created_at'] ?? date('Y-m-d H:i:s'),
                $c['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);
            $insertedClients++;
        }
    }
    outputMsg("[MIGRATED] Clients: {$insertedClients} row(s).");

    // C. Migrate Boards
    $oldBoards = $mysqlPdo->query("SELECT * FROM boards")->fetchAll();
    $insertedBoards = 0;
    $stmtBoard = $pgPdo->prepare("
        INSERT INTO boards (id, owner_id, client_id, title, description, portal_token, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (portal_token) DO NOTHING
    ");

    foreach ($oldBoards as $b) {
        $newUuid = generateUuid();
        $idMap['boards'][(int)$b['id']] = $newUuid;
        $ownerUuid = $idMap['users'][(int)$b['user_id']] ?? null;
        $clientUuid = $idMap['clients'][(int)$b['client_id']] ?? null;

        if ($ownerUuid && $clientUuid) {
            $stmtBoard->execute([
                $newUuid,
                $ownerUuid,
                $clientUuid,
                $b['title'] ?? 'Client Board',
                $b['description'] ?? null,
                $b['portal_token'] ?? bin2hex(random_bytes(16)),
                $b['created_at'] ?? date('Y-m-d H:i:s'),
                $b['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);
            $insertedBoards++;
        }
    }
    outputMsg("[MIGRATED] Boards: {$insertedBoards} row(s).");

    // D. Migrate Stages
    $oldStages = $mysqlPdo->query("SELECT * FROM stages")->fetchAll();
    $insertedStages = 0;
    $stmtStage = $pgPdo->prepare("
        INSERT INTO stages (id, board_id, title, position, color, is_done_stage, client_review, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($oldStages as $s) {
        $newUuid = generateUuid();
        $idMap['stages'][(int)$s['id']] = $newUuid;
        $boardUuid = $idMap['boards'][(int)$s['board_id']] ?? null;

        if ($boardUuid) {
            $isReview = !empty($s['is_review_stage']) || (str_contains(strtolower($s['title']), 'review'));
            $title = ($s['title'] === 'Review (Client)') ? 'In Review' : $s['title'];

            $stmtStage->execute([
                $newUuid,
                $boardUuid,
                $title,
                $s['position'] ?? 0,
                $s['color'] ?? '#4C9AFF',
                !empty($s['is_done_stage']),
                $isReview,
                $s['created_at'] ?? date('Y-m-d H:i:s'),
                $s['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);
            $insertedStages++;
        }
    }
    outputMsg("[MIGRATED] Stages: {$insertedStages} row(s).");

    // E. Migrate Tasks
    $oldTasks = $mysqlPdo->query("SELECT * FROM tasks")->fetchAll();
    $insertedTasks = 0;
    $stmtTask = $pgPdo->prepare("
        INSERT INTO tasks (id, board_id, stage_id, title, description, position, due_date, review_status, review_notes, blocker_type, blocker_waiting_on, blocker_reason, blocker_waiting_since, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($oldTasks as $t) {
        $newUuid = generateUuid();
        $idMap['tasks'][(int)$t['id']] = $newUuid;
        $boardUuid = $idMap['boards'][(int)$t['board_id']] ?? null;
        $stageUuid = $idMap['stages'][(int)$t['stage_id']] ?? null;

        if ($boardUuid && $stageUuid) {
            $stmtTask->execute([
                $newUuid,
                $boardUuid,
                $stageUuid,
                $t['title'],
                $t['description'] ?? null,
                $t['position'] ?? 0,
                $t['due_date'] ?? null,
                $t['review_status'] ?? 'pending',
                $t['review_notes'] ?? null,
                $t['blocker_type'] ?? null,
                $t['blocker_waiting_on'] ?? null,
                $t['blocker_reason'] ?? null,
                $t['blocker_waiting_since'] ?? null,
                $t['created_at'] ?? date('Y-m-d H:i:s'),
                $t['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);
            $insertedTasks++;
        }
    }
    outputMsg("[MIGRATED] Tasks: {$insertedTasks} row(s).");

    // Commit Transaction
    $pgPdo->commit();
    outputMsg("===================================================");
    outputMsg("[SUCCESS] All MySQL data successfully migrated to PostgreSQL!");
    outputMsg("===================================================");
} catch (\Exception $e) {
    if (isset($pgPdo) && $pgPdo->inTransaction()) {
        $pgPdo->rollBack();
    }
    outputMsg("[FATAL ERROR] Migration failed: " . $e->getMessage());
    exit(1);
}
