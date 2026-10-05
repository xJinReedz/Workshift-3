<?php
/**
 * Cron Job: Automated Blocker Stalled Reminder Dispatcher
 * Product: WorkShift by Studio Click Up
 *
 * Runs daily or every few hours via server cron (Hostinger/cPanel):
 * Example Cron:
 * 0 9 * * * /usr/bin/php /home/username/public_html/cron/send_reminders.php > /dev/null 2>&1
 */

// Autoload & Bootstrap
define('WORKSHIFT_CRON', true);

require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Helpers/functions.php';
require_once dirname(__DIR__) . '/app/Models/TaskBlocker.php';
require_once dirname(__DIR__) . '/app/Models/Notification.php';
require_once dirname(__DIR__) . '/app/Services/Mailer.php';
require_once dirname(__DIR__) . '/app/Services/ReminderService.php';

use WorkShift\Services\ReminderService;

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    // Basic secret key check if triggered via HTTP web cron
    $config = require dirname(__DIR__) . '/config/config.php';
    $cronKey = $_GET['key'] ?? '';
    if (empty($cronKey) || $cronKey !== ($config['app']['secret_key'] ?? '')) {
        http_response_code(403);
        echo "Access denied: Invalid cron secret key.\n";
        exit;
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Starting WorkShift Stalled Blocker Reminder Scan...\n";

try {
    $service = new ReminderService();
    $result = $service->processStalledBlockers();

    echo "Scan complete. Dispatched {$result['sent_count']} reminder(s).\n";

    if (!empty($result['details'])) {
        foreach ($result['details'] as $d) {
            echo "  - Sent to: {$d['client']} ({$d['email']}) for \"{$d['task']}\" (stalled {$d['days_waiting']} days)\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
