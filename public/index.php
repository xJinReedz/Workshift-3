<?php
/**
 * WorkShift Front Controller
 * Product: WorkShift by Studio Click Up
 */

declare(strict_types=1);

// 1. Path Definitions
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// 2. Load Configuration
$config = require CONFIG_PATH . '/config.php';

// 3. Error Reporting
if (!empty($config['app']['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// 4. PSR-4 Style Autoloader
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
    } elseif (str_starts_with($relativeClass, 'Database\\') && file_exists(ROOT_PATH . '/database/' . substr($relativeClass, 9) . '.php')) {
        require_once ROOT_PATH . '/database/' . substr($relativeClass, 9) . '.php';
    }
});

// 5. Load Global Helper Functions
require_once APP_PATH . '/Helpers/functions.php';

// 6. Start Session
use WorkShift\Core\Session;
Session::start();

// 7. Initialize Request & Router
use WorkShift\Core\Request;
use WorkShift\Core\Router;
use WorkShift\Controllers\LandingController;
use WorkShift\Controllers\AuthController;
use WorkShift\Controllers\DashboardController;
use WorkShift\Controllers\ClientController;
use WorkShift\Controllers\BoardController;
use WorkShift\Controllers\TaskController;
use WorkShift\Controllers\TimeController;
use WorkShift\Controllers\InvoiceController;
use WorkShift\Controllers\PortalController;
use WorkShift\Controllers\NotificationController;
use WorkShift\Controllers\FileController;
use WorkShift\Controllers\SettingsController;
use WorkShift\Controllers\InstallController;

$request = new Request();
$router = new Router();

// ==========================================
// Route Registrations
// ==========================================

// Public Landing & Install
$router->get('/', [LandingController::class, 'index']);
$router->get('/install', [InstallController::class, 'index']);
$router->post('/install', [InstallController::class, 'run']);

// Authentication
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'showLogoutConfirm']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'showForgot']);
$router->post('/forgot-password', [AuthController::class, 'forgot']);
$router->get('/reset-password', [AuthController::class, 'showReset']);
$router->post('/reset-password', [AuthController::class, 'reset']);

// Freelancer Dashboard
$router->get('/dashboard', [DashboardController::class, 'index']);

// Client CRM Records
$router->get('/clients', [ClientController::class, 'index']);
$router->get('/clients/pipeline', [ClientController::class, 'pipeline']);
$router->get('/clients/create', [ClientController::class, 'showCreate']);
$router->post('/clients', [ClientController::class, 'store']);
$router->get('/clients/{id}', [ClientController::class, 'show']);
$router->get('/clients/{id}/edit', [ClientController::class, 'showEdit']);
$router->post('/clients/{id}/update', [ClientController::class, 'update']);
$router->post('/clients/{id}/stage', [ClientController::class, 'updateStage']);
$router->post('/clients/{id}/regenerate-token', [ClientController::class, 'regeneratePortalToken']);
$router->post('/clients/{id}/delete', [ClientController::class, 'delete']);

// Dedicated Client Boards
$router->get('/boards/{id}', [BoardController::class, 'show']);
$router->post('/boards/{id}/stages', [BoardController::class, 'addStage']);
$router->post('/boards/{id}/stages/{stageId}', [BoardController::class, 'updateStage']);
$router->delete('/boards/{id}/stages/{stageId}', [BoardController::class, 'deleteStage']);

// Tasks & Signature Blocker Engine
$router->post('/tasks', [TaskController::class, 'store']);
$router->get('/tasks/{id}/modal', [TaskController::class, 'modal']);
$router->post('/tasks/{id}', [TaskController::class, 'update']);
$router->post('/tasks/{id}/move', [TaskController::class, 'move']);
$router->post('/tasks/{id}/blocker', [TaskController::class, 'setBlocker']);
$router->delete('/tasks/{id}/blocker', [TaskController::class, 'removeBlocker']);
$router->post('/tasks/{id}/comment', [TaskController::class, 'addComment']);
$router->post('/tasks/{id}/upload', [TaskController::class, 'uploadFile']);
$router->delete('/tasks/{id}', [TaskController::class, 'delete']);

// Built-in Time Tracker
$router->post('/time/start', [TimeController::class, 'start']);
$router->post('/time/stop', [TimeController::class, 'stop']);
$router->post('/time/manual', [TimeController::class, 'logManual']);
$router->get('/time/active', [TimeController::class, 'active']);
$router->post('/time/{id}/privacy', [TimeController::class, 'togglePrivacy']);
$router->delete('/time/{id}', [TimeController::class, 'delete']);

// Invoices & Billing
$router->get('/invoices', [InvoiceController::class, 'index']);
$router->get('/invoices/create', [InvoiceController::class, 'showCreate']);
$router->post('/invoices', [InvoiceController::class, 'store']);
$router->get('/invoices/{id}', [InvoiceController::class, 'show']);
$router->get('/invoices/{id}/print', [InvoiceController::class, 'print']);
$router->post('/invoices/{id}/status', [InvoiceController::class, 'updateStatus']);

// Client Portal (Unguessable Invite Link & Strict Query Isolation)
$router->get('/portal/{token}', [PortalController::class, 'show']);
$router->get('/portal/{token}/poll', [PortalController::class, 'poll']);
$router->post('/portal/{token}/review/{taskId}', [PortalController::class, 'reviewTask']);
$router->get('/portal/{token}/pay/{invoiceId}', [PortalController::class, 'showCheckout']);
$router->post('/portal/{token}/pay/{invoiceId}/process', [PortalController::class, 'processPayment']);
$router->get('/portal/{token}/pay/{invoiceId}/success', [PortalController::class, 'paymentSuccess']);
$router->post('/webhook/maya', [PortalController::class, 'mayaWebhook']);

// Secure File Downloads
$router->get('/files/{id}/download', [FileController::class, 'download']);
$router->delete('/files/{id}', [FileController::class, 'delete']);

// Notifications
$router->get('/notifications', [NotificationController::class, 'index']);
$router->post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
$router->post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);

// Settings & Plans
$router->get('/settings', [SettingsController::class, 'index']);
$router->post('/settings', [SettingsController::class, 'update']);
$router->post('/settings/toggle-plan', [SettingsController::class, 'togglePlan']);

// 8. Dispatch Request
$router->dispatch($request);
