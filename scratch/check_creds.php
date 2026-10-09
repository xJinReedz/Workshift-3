<?php
define('ROOT_PATH', __DIR__ . '/..');
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');

spl_autoload_register(function (string $class) {
    $prefix = 'WorkShift\\';
    $baseDir = APP_PATH . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once CONFIG_PATH . '/config.php';

$auth = new WorkShift\Services\SupabaseAuth();

$creds = [
    ['email' => 'demo@workshift.app', 'pass' => 'Password123!'],
    ['email' => 'client@workshift.app', 'pass' => 'Password123!'],
];

foreach ($creds as $c) {
    echo "Testing {$c['email']}...\n";
    $res = $auth->signInWithPassword($c['email'], $c['pass']);
    echo "  Status: " . ($res['status'] ?? 'N/A') . "\n";
    echo "  Success: " . ($res['success'] ? 'YES' : 'NO') . "\n";
    if ($res['success']) {
        echo "  User ID: " . ($res['data']['user']['id'] ?? 'N/A') . "\n";
        echo "  Confirmed At: " . ($res['data']['user']['email_confirmed_at'] ?? 'UNCONFIRMED') . "\n";
    } else {
        echo "  Error Data: " . json_encode($res['data']) . "\n";
    }
    echo "--------------------------------------\n";
}
