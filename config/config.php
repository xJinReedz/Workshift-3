<?php
/**
 * WorkShift Configuration File
 * Product: WorkShift by Studio Click Up
 */

return [
    'app' => [
        'name' => 'WorkShift',
        'company' => 'Studio Click Up',
        'url' => getenv('APP_URL') ?: 'http://localhost:8000',
        'env' => getenv('APP_ENV') ?: 'development', // 'development' or 'production'
        'debug' => getenv('APP_DEBUG') !== false ? (bool)getenv('APP_DEBUG') : true,
        'secret_key' => getenv('APP_KEY') ?: 'workshift-super-secret-key-change-in-prod-32chars!',
        'allow_dev_plan_toggle' => true, // Enables switching between Basic and Pro in settings for demo/testing
    ],

    'database' => [
        'driver' => getenv('DB_DRIVER') ?: 'mysql', // 'mysql' or 'sqlite'
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'workshift_db',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        // In case sqlite is used locally:
        'sqlite_path' => dirname(__DIR__) . '/storage/database.sqlite',
    ],

    'mail' => [
        'driver' => getenv('MAIL_DRIVER') ?: 'log', // 'mail', 'smtp', or 'log'
        'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'notifications@workshift.local',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'WorkShift',
        'smtp_host' => getenv('MAIL_HOST') ?: 'smtp.mailtrap.io',
        'smtp_port' => getenv('MAIL_PORT') ?: 587,
        'smtp_username' => getenv('MAIL_USERNAME') ?: '',
        'smtp_password' => getenv('MAIL_PASSWORD') ?: '',
        'smtp_encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    ],

    'reminders' => [
        'default_stalled_days' => 3, // Auto-remind client if blocker is waiting > X days
    ],

    'storage' => [
        'upload_dir' => dirname(__DIR__) . '/storage/uploads',
        'logs_dir' => dirname(__DIR__) . '/storage/logs',
        'max_file_size_mb' => 25, // 25 MB per file
        'allowed_mimes' => [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
        ],
    ],

    'plans' => [
        'basic' => [
            'name' => 'Basic (Free)',
            'max_active_clients' => 3,
            'max_storage_bytes' => 2147483648, // 2 GB
            'has_portal_payments' => false,
            'has_automated_reminders' => false,
            'has_custom_scheduling' => false,
        ],
        'pro' => [
            'name' => 'Pro (₱499/mo)',
            'price_php' => 499,
            'max_active_clients' => -1, // Unlimited
            'max_storage_bytes' => 53687091200, // 50 GB
            'has_portal_payments' => true,
            'has_automated_reminders' => true,
            'has_custom_scheduling' => true,
        ],
    ],

    'payment' => [
        'default_provider' => 'mock', // 'mock' or 'maya'
        'maya' => [
            'public_key' => getenv('MAYA_PUBLIC_KEY') ?: 'pk-mock-maya-public-key',
            'secret_key' => getenv('MAYA_SECRET_KEY') ?: 'sk-mock-maya-secret-key',
            'webhook_secret' => getenv('MAYA_WEBHOOK_SECRET') ?: 'whsec-mock-maya-webhook-secret',
            'sandbox' => true,
        ],
    ],
];
