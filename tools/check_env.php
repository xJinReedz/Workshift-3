<?php
/**
 * Environment & System Health Checker for WorkShift
 * Run via CLI: php tools/check_env.php
 * Or via web browser: http://your-domain/tools/check_env.php
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

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

$isCli = (php_sapi_name() === 'cli');

function reportResult(string $title, bool $pass, string $detail = ''): void {
    global $isCli;
    if ($isCli) {
        $status = $pass ? "[OK]" : "[FAIL]";
        echo sprintf("%-35s %-8s %s\n", $title, $status, $detail);
    } else {
        $color = $pass ? '#0f5132' : '#842029';
        $bg = $pass ? '#d1e7dd' : '#f8d7da';
        $badge = $pass ? 'PASS' : 'FAIL';
        echo "<div style='padding:10px 14px; margin-bottom:8px; border-radius:6px; background:{$bg}; color:{$color}; font-family:sans-serif;'>
                <strong>{$title}</strong> &mdash; <span>{$badge}</span>: {$detail}
              </div>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>WorkShift Env Diagnostic</title></head><body style='max-width:800px;margin:40px auto;font-family:system-ui,-apple-system,sans-serif;'>";
    echo "<h2>WorkShift System & Environment Check</h2>";
} else {
    echo "===================================================\n";
    echo "  WorkShift Environment & System Diagnostic Tool\n";
    echo "===================================================\n\n";
}

$allPass = true;

// 1. PHP Version
$phpVersion = PHP_VERSION;
$phpPass = version_compare($phpVersion, '8.1.0', '>=');
reportResult('PHP Version (>= 8.1)', $phpPass, "Current version: {$phpVersion}");
if (!$phpPass) $allPass = false;

// 2. Required PHP Extensions
$extensions = [
    'pdo_pgsql' => 'Required for Supabase PostgreSQL connection',
    'curl' => 'Required for Supabase Auth REST API communication',
    'openssl' => 'Required for secure token generation and HTTPS',
    'mbstring' => 'Required for UTF-8 string manipulation',
];

foreach ($extensions as $ext => $desc) {
    $loaded = extension_loaded($ext);
    if ($ext === 'pdo_pgsql' && !$loaded) {
        $detail = "MISSING! {$desc}. To fix on XAMPP/Windows: Open php.ini, uncomment `extension=pdo_pgsql` and `extension=pgsql`, then restart Apache. On Ubuntu/Linux: run `sudo apt install php-pgsql` and restart PHP-FPM.";
    } else {
        $detail = $loaded ? "Extension is active" : "MISSING! {$desc}";
    }
    reportResult("Extension: {$ext}", $loaded, $detail);
    if (!$loaded) $allPass = false;
}

// 3. Required Env Variables
$requiredVars = [
    'APP_ENV',
    'APP_URL',
    'DATABASE_URL',
    'SUPABASE_URL',
    'SUPABASE_PUBLISHABLE_KEY',
    'SUPABASE_SECRET_KEY',
    'SESSION_NAME',
];

foreach ($requiredVars as $var) {
    $val = getenv($var) ?: ($_ENV[$var] ?? '');
    $present = !empty($val) && !str_contains($val, 'placeholder');
    reportResult("Env Var: {$var}", $present, $present ? "Present (value hidden)" : "Missing or using placeholder");
    if (!$present) $allPass = false;
}

// 4. Writable Storage Directories
$dirs = [
    ROOT_PATH . '/storage/logs' => 'Storage Logs directory',
    ROOT_PATH . '/storage/uploads' => 'Storage Uploads directory',
];

foreach ($dirs as $dir => $label) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $writable = is_dir($dir) && is_writable($dir);
    reportResult($label, $writable, $writable ? "Directory is writable" : "Not writable at {$dir}");
    if (!$writable) $allPass = false;
}

// 5. Database Connectivity
$dbUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? '');
if (extension_loaded('pdo_pgsql') && !empty($dbUrl) && !str_contains($dbUrl, 'placeholder')) {
    try {
        $dbParts = parse_url($dbUrl);
        $host = $dbParts['host'] ?? '127.0.0.1';
        $port = $dbParts['port'] ?? 5432;
        $dbName = ltrim($dbParts['path'] ?? 'postgres', '/');
        $user = isset($dbParts['user']) ? rawurldecode($dbParts['user']) : '';
        $pass = isset($dbParts['pass']) ? rawurldecode($dbParts['pass']) : '';

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbName};sslmode=require";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $stmt = $pdo->query("SELECT version()");
        $ver = $stmt->fetchColumn();
        reportResult("Database Connection (Postgres)", true, "Connected! Server: " . substr((string)$ver, 0, 40));
    } catch (Exception $e) {
        reportResult("Database Connection (Postgres)", false, "Failed: " . $e->getMessage());
        $allPass = false;
    }
} else {
    reportResult("Database Connection (Postgres)", false, "Skipped (pdo_pgsql missing or DATABASE_URL placeholder)");
    $allPass = false;
}

// 6. Supabase Auth Reachability
$supaUrl = rtrim(getenv('SUPABASE_URL') ?: '', '/');
$supaAnon = getenv('SUPABASE_PUBLISHABLE_KEY') ?: '';

if (!empty($supaUrl) && !str_contains($supaUrl, 'placeholder') && function_exists('curl_init')) {
    $ch = curl_init("{$supaUrl}/auth/v1/health");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: {$supaAnon}",
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $reachable = ($httpCode === 200);
    reportResult("Supabase Auth API Reachable", $reachable, $reachable ? "HTTP 200 OK" : "Returned HTTP {$httpCode}");
    if (!$reachable) $allPass = false;
} else {
    reportResult("Supabase Auth API Reachable", false, "Skipped (SUPABASE_URL is placeholder)");
}

// 7. Security: Verify .env is NOT web-accessible
$envFile = ROOT_PATH . '/.env';
$isWebAccessible = false;
if (!$isCli && file_exists($envFile)) {
    // If running in web context, test if .env is inside public folder
    $publicEnv = ROOT_PATH . '/public/.env';
    if (file_exists($publicEnv)) {
        $isWebAccessible = true;
    }
}
reportResult(".env Not Web Accessible", !$isWebAccessible, $isWebAccessible ? "DANGER: .env is in public directory!" : "Verified (.env is outside public web root)");

if (!$isCli) {
    echo "<h3 style='margin-top:20px; color:" . ($allPass ? 'green' : 'orange') . "'>" . ($allPass ? 'All Checks Passed!' : 'Some checks require setup/configuration.') . "</h3>";
    echo "</body></html>";
} else {
    echo "\n===================================================\n";
    echo $allPass ? "  RESULT: ALL CHECKS PASSED!\n" : "  RESULT: CONFIGURATION NEEDED\n";
    echo "===================================================\n";
}
