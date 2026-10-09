<?php
/**
 * WorkShift Configuration Example
 * Copy this file to config.php or set environment variables.
 */

return [
    'app' => [
        'name' => 'WorkShift',
        'company' => 'Studio Click Up',
        'url' => 'http://localhost:8000',
        'env' => 'production',
        'debug' => false,
        'secret_key' => 'change-this-to-a-random-32-character-secret-string',
        'allow_dev_plan_toggle' => false,
    ],

    'database' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'u123456789_workshift',
        'username' => 'u123456789_admin',
        'password' => 'YourStrongDbPasswordHere',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],

    'mail' => [
        'driver' => 'mail', // 'mail', 'smtp', or 'log'
        'from_address' => 'notifications@yourdomain.com',
        'from_name' => 'WorkShift',
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 465,
        'smtp_username' => 'notifications@yourdomain.com',
        'smtp_password' => 'YourEmailPassword',
        'smtp_encryption' => 'ssl',
    ],

    'reminders' => [
        'default_stalled_days' => 3,
    ],

    'payment' => [
        'default_provider' => 'mock',
        'maya' => [
            'public_key' => 'pk-your-maya-public-key',
            'secret_key' => 'sk-your-maya-secret-key',
            'webhook_secret' => 'whsec-your-maya-webhook-secret',
            'sandbox' => true,
        ],
    ],
];
