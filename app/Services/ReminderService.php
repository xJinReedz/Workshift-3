<?php
/**
 * Reminder Service (Automated Blocker Reminders for Pro plan)
 */

namespace WorkShift\Services;

use WorkShift\Models\TaskBlocker;
use WorkShift\Models\Notification;

class ReminderService
{
    private TaskBlocker $blockerModel;
    private Notification $notificationModel;
    private Mailer $mailer;
    private array $config;

    public function __construct()
    {
        $this->blockerModel = new TaskBlocker();
        $this->notificationModel = new Notification();
        $this->mailer = new Mailer();
        $this->config = require dirname(__DIR__, 2) . '/config/config.php';
    }

    public function processStalledBlockers(?int $customDays = null): array
    {
        $stalledDays = $customDays ?: ($this->config['reminders']['default_stalled_days'] ?? 3);
        $stalled = $this->blockerModel->getStalledBlockers($stalledDays);
        $sentCount = 0;
        $details = [];

        $appUrl = rtrim($this->config['app']['url'] ?? 'http://localhost:8000', '/');

        foreach ($stalled as $item) {
            $clientEmail = $item['client_email'];
            $clientName = $item['client_name'];
            $freelancerName = $item['freelancer_name'];
            $taskTitle = $item['task_title'];
            $reason = $item['reason'];
            $token = $item['portal_token'];
            $portalUrl = "{$appUrl}/portal/{$token}";

            $subject = "Friendly Reminder: {$taskTitle} is waiting on your input";

            $html = <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 24px;">
    <div style="background-color: #3b6fd8; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h2 style="color: #ffffff; margin: 0; font-size: 20px;">WorkShift · Friendly Update</h2>
    </div>
    <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-top: none; padding: 24px; border-radius: 0 0 8px 8px;">
        <p>Hi {$clientName},</p>
        <p>This is a quick automated note from <strong>{$freelancerName}</strong> regarding your project workspace.</p>
        <div style="background-color: #f8fafc; border-left: 4px solid #f59e0b; padding: 16px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0 0 8px 0; font-weight: 600; color: #b45309;">Task: {$taskTitle}</p>
            <p style="margin: 0; color: #475569; font-size: 14px;"><strong>Waiting on:</strong> {$reason}</p>
        </div>
        <p>To keep the project moving smoothly and avoid timeline delays, please take a moment to review and provide your input:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$portalUrl}" style="background-color: #3b6fd8; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block;">Open Your Client Board</a>
        </div>
        <p style="color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 24px;">
            Sent via WorkShift for {$freelancerName}. If you have already responded, please disregard this note.
        </p>
    </div>
</body>
</html>
HTML;

            $this->mailer->send($clientEmail, $subject, $html, $freelancerName, $item['freelancer_email']);

            // Notify freelancer that reminder was dispatched
            $this->notificationModel->create(
                (int)$item['freelancer_name'],
                'reminder_sent',
                "Reminder sent to {$clientName}",
                "Automated reminder dispatched for task \"{$taskTitle}\" (stalled {$stalledDays}+ days).",
                "/boards/{$item['board_id']}"
            );

            $sentCount++;
            $details[] = [
                'client' => $clientName,
                'email' => $clientEmail,
                'task' => $taskTitle,
                'days_waiting' => days_waiting($item['waiting_since']),
            ];
        }

        return [
            'sent_count' => $sentCount,
            'details' => $details,
        ];
    }
}
