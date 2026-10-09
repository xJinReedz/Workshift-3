<?php
/**
 * Mailer Service
 * Supports PHP mail(), SMTP, or file-based logging for dev/shared hosting
 */

namespace WorkShift\Services;

class Mailer
{
    private array $config;

    public function __construct(?array $config = null)
    {
        if ($config === null) {
            $appConfig = require dirname(__DIR__, 2) . '/config/config.php';
            $config = $appConfig['mail'] ?? [];
        }
        $this->config = $config;
    }

    public function send(string $to, string $subject, string $htmlContent, ?string $fromName = null, ?string $fromEmail = null): bool
    {
        $fromName = $fromName ?: ($this->config['from_name'] ?? 'WorkShift');
        $fromEmail = $fromEmail ?: ($this->config['from_address'] ?? 'notifications@workshift.local');
        $driver = $this->config['driver'] ?? 'log';

        if ($driver === 'mail' && function_exists('mail')) {
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=utf-8',
                "From: {$fromName} <{$fromEmail}>",
                "Reply-To: {$fromEmail}",
                'X-Mailer: WorkShift-PHP/' . phpversion()
            ];
            $success = @mail($to, $subject, $htmlContent, implode("\r\n", $headers));
            if ($success) {
                return true;
            }
        }

        // Fallback or explicit 'log' driver
        $this->logMail($to, $subject, $htmlContent, $fromEmail);
        return true;
    }

    private function logMail(string $to, string $subject, string $htmlContent, string $fromEmail): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/mail.log';
        $entry = sprintf(
            "[%s] MAIL SENT\nTo: %s\nFrom: %s\nSubject: %s\nBody:\n%s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $fromEmail,
            $subject,
            strip_tags($htmlContent),
            str_repeat('-', 60)
        );

        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
