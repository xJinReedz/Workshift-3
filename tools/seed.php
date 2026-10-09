<?php
/**
 * WorkShift PostgreSQL Seeder Tool
 * Run via CLI: php tools/seed.php
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

define('APP_PATH', ROOT_PATH . '/app');

// PSR-4 Style Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'WorkShift\\';
    $baseDir = APP_PATH . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

require_once APP_PATH . '/Helpers/functions.php';

// Load environment variables from .env
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

use WorkShift\Core\Database;
use WorkShift\Services\SupabaseAuth;

echo "===================================================\n";
echo "  WorkShift PostgreSQL Data Seeder\n";
echo "===================================================\n\n";

try {
    $db = Database::getConnection();
    $supabaseAuth = new SupabaseAuth();

    // 1. Create Freelancer Demo User in Supabase Auth
    $freelancerEmail = 'demo@workshift.app';
    $freelancerPass = 'Password123!';
    echo "[-] Creating Freelancer Account ({$freelancerEmail})...\n";
    
    $freelancerRes = $supabaseAuth->adminCreateUser($freelancerEmail, $freelancerPass, [
        'full_name' => 'Alex Vance',
        'studio_name' => 'Vance Design Studio',
        'is_freelancer' => true,
    ]);

    // Also register legacy demo email alex@studioclickup.com
    $legacyEmail = 'alex@studioclickup.com';
    $legacyRes = $supabaseAuth->adminCreateUser($legacyEmail, 'Password123!', [
        'full_name' => 'Alex Vance',
        'studio_name' => 'Studio Click Up',
        'is_freelancer' => true,
    ]);
    if ($legacyRes['success'] && !empty($legacyRes['data']['id'])) {
        $legacyId = $legacyRes['data']['id'];
        $db->prepare("
            INSERT INTO profiles (id, email, full_name, studio_name, default_hourly_rate, plan, is_freelancer, updated_at)
            VALUES (:id, :email, 'Alex Vance', 'Studio Click Up', 850.00, 'pro', true, CURRENT_TIMESTAMP)
            ON CONFLICT (id) DO NOTHING
        ")->execute(['id' => $legacyId, 'email' => $legacyEmail]);
    }

    $freelancerId = null;
    if ($freelancerRes['success'] && !empty($freelancerRes['data']['id'])) {
        $freelancerId = $freelancerRes['data']['id'];
        echo "    [OK] Supabase Auth User Created/Exists ID: {$freelancerId}\n";
    } else {
        // Try sign in to fetch existing ID
        $loginRes = $supabaseAuth->signInWithPassword($freelancerEmail, $freelancerPass);
        if ($loginRes['success'] && !empty($loginRes['data']['user']['id'])) {
            $freelancerId = $loginRes['data']['user']['id'];
            echo "    [OK] User exists in Supabase Auth ID: {$freelancerId}\n";
        }
    }

    if (!$freelancerId) {
        // Generate a random fallback UUID for profile if local test
        $stmt = $db->query("SELECT gen_random_uuid()");
        $freelancerId = $stmt->fetchColumn();
    }

    // Upsert Freelancer Profile
    $stmt = $db->prepare("
        INSERT INTO profiles (id, email, full_name, studio_name, default_hourly_rate, scheduling_link, plan, is_freelancer, avatar_url, updated_at)
        VALUES (:id, :email, :full_name, :studio_name, :hourly_rate, :scheduling_link, 'pro', true, null, CURRENT_TIMESTAMP)
        ON CONFLICT (id) DO UPDATE SET
            full_name = EXCLUDED.full_name,
            studio_name = EXCLUDED.studio_name,
            default_hourly_rate = EXCLUDED.default_hourly_rate
    ");
    $stmt->execute([
        'id' => $freelancerId,
        'email' => $freelancerEmail,
        'full_name' => 'Alex Vance',
        'studio_name' => 'Vance Design Studio',
        'hourly_rate' => 850.00,
        'scheduling_link' => 'https://cal.com/alexvance',
    ]);
    echo "    [OK] Freelancer Profile Seeded.\n\n";

    // 2. Create Client Demo User in Supabase Auth
    $clientEmail = 'client@workshift.app';
    $clientPass = 'Password123!';
    echo "[-] Creating Client Portal Account ({$clientEmail})...\n";

    $clientRes = $supabaseAuth->adminCreateUser($clientEmail, $clientPass, [
        'full_name' => 'Sarah Jenkins',
        'studio_name' => 'Acme Corporation',
        'is_freelancer' => false,
    ]);

    $clientUserId = null;
    if ($clientRes['success'] && !empty($clientRes['data']['id'])) {
        $clientUserId = $clientRes['data']['id'];
        echo "    [OK] Supabase Auth Client User Created ID: {$clientUserId}\n";
    } else {
        $loginRes = $supabaseAuth->signInWithPassword($clientEmail, $clientPass);
        if ($loginRes['success'] && !empty($loginRes['data']['user']['id'])) {
            $clientUserId = $loginRes['data']['user']['id'];
            echo "    [OK] Client User exists in Supabase Auth ID: {$clientUserId}\n";
        }
    }

    if (!$clientUserId) {
        $stmt = $db->query("SELECT gen_random_uuid()");
        $clientUserId = $stmt->fetchColumn();
    }

    // Upsert Client User Profile
    $stmt = $db->prepare("
        INSERT INTO profiles (id, email, full_name, studio_name, default_hourly_rate, scheduling_link, plan, is_freelancer, avatar_url, updated_at)
        VALUES (:id, :email, :full_name, :studio_name, 0.00, null, 'basic', false, null, CURRENT_TIMESTAMP)
        ON CONFLICT (id) DO UPDATE SET
            full_name = EXCLUDED.full_name,
            studio_name = EXCLUDED.studio_name
    ");
    $stmt->execute([
        'id' => $clientUserId,
        'email' => $clientEmail,
        'full_name' => 'Sarah Jenkins',
        'studio_name' => 'Acme Corporation',
    ]);
    echo "    [OK] Client User Profile Seeded.\n\n";

    // 2.5 Clean existing demo data if re-running
    $db->exec("DELETE FROM clients WHERE owner_id = " . $db->quote($freelancerId));

    // 3. Seed Clients
    echo "[-] Seeding Clients & Projects...\n";
    $stmt = $db->prepare("
        INSERT INTO clients (id, owner_id, profile_id, name, company_name, email, phone, portal_token, status, created_at, updated_at)
        VALUES (gen_random_uuid(), :owner_id, :profile_id, 'Acme Corporation', 'Acme Corp', :email, '+1 (555) 234-5678', md5(random()::text), 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        RETURNING id
    ");
    $stmt->execute([
        'owner_id' => $freelancerId,
        'profile_id' => $clientUserId,
        'email' => $clientEmail,
    ]);
    $clientId = $stmt->fetchColumn();

    // Second Client
    $stmt = $db->prepare("
        INSERT INTO clients (id, owner_id, name, company_name, email, phone, portal_token, status, created_at, updated_at)
        VALUES (gen_random_uuid(), :owner_id, 'Nexus Technologies', 'Nexus Tech', 'michael@nexustech.io', '+1 (555) 987-6543', md5(random()::text), 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        RETURNING id
    ");
    $stmt->execute(['owner_id' => $freelancerId]);
    $client2Id = $stmt->fetchColumn();

    // 4. Seed Board for Acme Corp
    $stmt = $db->prepare("
        INSERT INTO boards (id, owner_id, client_id, title, description, portal_token, created_at, updated_at)
        VALUES (gen_random_uuid(), :owner_id, :client_id, 'Acme Web Redesign', 'Complete redesign of corporate portal and SaaS landing pages', md5(random()::text), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        RETURNING id
    ");
    $stmt->execute([
        'owner_id' => $freelancerId,
        'client_id' => $clientId,
    ]);
    $boardId = $stmt->fetchColumn();

    // Seed Board for Nexus Tech
    $stmt = $db->prepare("
        INSERT INTO boards (id, owner_id, client_id, title, description, portal_token, created_at, updated_at)
        VALUES (gen_random_uuid(), :owner_id, :client_id, 'Nexus Mobile App Design', 'Cross-platform Flutter mobile application development & UX design', md5(random()::text), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        RETURNING id
    ");
    $stmt->execute([
        'owner_id' => $freelancerId,
        'client_id' => $client2Id,
    ]);
    $board2Id = $stmt->fetchColumn();

    // 5. Seed Stages for Board
    $stages = [
        ['title' => 'To Do', 'color' => '#64748b', 'is_done_stage' => false, 'client_review' => false, 'position' => 1],
        ['title' => 'In Progress', 'color' => '#3b82f6', 'is_done_stage' => false, 'client_review' => false, 'position' => 2],
        ['title' => 'In Review', 'color' => '#f59e0b', 'is_done_stage' => false, 'client_review' => true, 'position' => 3],
        ['title' => 'Done', 'color' => '#10b981', 'is_done_stage' => true, 'client_review' => false, 'position' => 4],
    ];

    $stageIds = [];
    $stmt = $db->prepare("
        INSERT INTO stages (id, board_id, title, position, color, is_done_stage, client_review, created_at, updated_at)
        VALUES (gen_random_uuid(), :board_id, :title, :position, :color, :is_done_stage, :client_review, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        RETURNING id, title
    ");

    foreach ($stages as $s) {
        $stmt->execute([
            'board_id' => $boardId,
            'title' => $s['title'],
            'position' => $s['position'],
            'color' => $s['color'],
            'is_done_stage' => $s['is_done_stage'] ? 1 : 0,
            'client_review' => $s['client_review'] ? 1 : 0,
        ]);
        $row = $stmt->fetch();
        $stageIds[$s['title']] = $row['id'];

        $stmt->execute([
            'board_id' => $board2Id,
            'title' => $s['title'],
            'position' => $s['position'],
            'color' => $s['color'],
            'is_done_stage' => $s['is_done_stage'] ? 1 : 0,
            'client_review' => $s['client_review'] ? 1 : 0,
        ]);
    }

    // 6. Seed Tasks
    $stmt = $db->prepare("
        INSERT INTO tasks (id, board_id, stage_id, title, description, due_date, position, created_at, updated_at)
        VALUES (gen_random_uuid(), :board_id, :stage_id, :title, :description, :due_date, :position, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        RETURNING id
    ");

    // Task 1: To Do
    $stmt->execute([
        'board_id' => $boardId,
        'stage_id' => $stageIds['To Do'],
        'title' => 'Design System Tokens & Micro-interactions',
        'description' => 'Establish color tokens, dark mode variables, and button hover micro-animations',
        'due_date' => date('Y-m-d', strtotime('+7 days')),
        'position' => 1,
    ]);
    $task1Id = $stmt->fetchColumn();

    // Task 2: In Progress with Blocker
    $stmt->execute([
        'board_id' => $boardId,
        'stage_id' => $stageIds['In Progress'],
        'title' => 'User Auth & Role Management Setup',
        'description' => 'Integrate Supabase Auth REST endpoints for freelancer and client portal authentication',
        'due_date' => date('Y-m-d', strtotime('+3 days')),
        'position' => 1,
    ]);
    $task2Id = $stmt->fetchColumn();

    // Add Blocker to Task 2
    $stmtBlocker = $db->prepare("
        INSERT INTO task_blockers (id, task_id, type, waiting_on, reason, created_at)
        VALUES (gen_random_uuid(), :task_id, 'content', 'client', 'Waiting for Brand Colors & Vector Assets from Marketing Team', CURRENT_TIMESTAMP - INTERVAL '2 days')
    ");
    $stmtBlocker->execute(['task_id' => $task2Id]);

    $stmtUpdateTask = $db->prepare("
        UPDATE tasks SET
            blocker_type = 'content',
            blocker_waiting_on = 'client',
            blocker_reason = 'Waiting for Brand Colors & Vector Assets from Marketing Team',
            blocker_waiting_since = CURRENT_TIMESTAMP - INTERVAL '2 days'
        WHERE id = :task_id
    ");
    $stmtUpdateTask->execute(['task_id' => $task2Id]);

    // Task 3: In Review (Client Review stage)
    $stmt->execute([
        'board_id' => $boardId,
        'stage_id' => $stageIds['In Review'],
        'title' => 'Dashboard UI & Client Portal Preview',
        'description' => 'Interactive mockup for client task approvals and stage feedback',
        'due_date' => date('Y-m-d', strtotime('+1 day')),
        'position' => 1,
    ]);
    $task3Id = $stmt->fetchColumn();

    // Task 4: Done
    $stmt->execute([
        'board_id' => $boardId,
        'stage_id' => $stageIds['Done'],
        'title' => 'Database Schema Migration to PostgreSQL',
        'description' => 'Convert legacy MySQL tables to Supabase PostgreSQL with RLS policies and UUID keys',
        'due_date' => date('Y-m-d', strtotime('-2 days')),
        'position' => 1,
    ]);

    // 7. Seed Time Entries
    $stmt = $db->prepare("
        INSERT INTO time_entries (id, owner_id, client_id, task_id, description, start_time, end_time, duration_minutes, hourly_rate, created_at)
        VALUES (gen_random_uuid(), :owner_id, :client_id, :task_id, :description, CURRENT_TIMESTAMP - INTERVAL '4 hours', CURRENT_TIMESTAMP - INTERVAL '30 minutes', :duration_minutes, :hourly_rate, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        'owner_id' => $freelancerId,
        'client_id' => $clientId,
        'task_id' => $task2Id,
        'description' => 'Supabase Auth REST API Integration & cURL wrapper',
        'duration_minutes' => 210,
        'hourly_rate' => 850.00,
    ]);
    $stmt->execute([
        'owner_id' => $freelancerId,
        'client_id' => $clientId,
        'task_id' => $task3Id,
        'description' => 'Atlassian Jira-style glassmorphism styling & modal refactoring',
        'duration_minutes' => 240,
        'hourly_rate' => 850.00,
    ]);

    // 8. Seed Sample Invoice
    $stmt = $db->prepare("
        INSERT INTO invoices (id, owner_id, client_id, invoice_number, status, issue_date, due_date, subtotal, tax_amount, total_amount, notes, created_at, updated_at)
        VALUES (gen_random_uuid(), :owner_id, :client_id, 'INV-2026-001', 'sent', CURRENT_DATE, CURRENT_DATE + INTERVAL '14 days', 6375.00, 0.00, 6375.00, 'Thank you for your business! Please settle payment within 14 days.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        RETURNING id
    ");
    $stmt->execute([
        'owner_id' => $freelancerId,
        'client_id' => $clientId,
    ]);
    $invoiceId = $stmt->fetchColumn();

    $stmtItem = $db->prepare("
        INSERT INTO invoice_items (id, invoice_id, description, quantity, unit_price, amount)
        VALUES (gen_random_uuid(), :invoice_id, 'Frontend UX Redesign & PostgreSQL Migration', 7.5, 850.00, 6375.00)
    ");
    $stmtItem->execute(['invoice_id' => $invoiceId]);

    echo "===================================================\n";
    echo "  SEEDING COMPLETE SUCCESSFUL!\n";
    echo "===================================================\n\n";
    echo "Freelancer Login Credentials:\n";
    echo "  Email:    {$freelancerEmail}\n";
    echo "  Password: {$freelancerPass}\n\n";
    echo "Client Portal Login Credentials:\n";
    echo "  Email:    {$clientEmail}\n";
    echo "  Password: {$clientPass}\n\n";

} catch (Exception $e) {
    echo "\n[FATAL] Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
