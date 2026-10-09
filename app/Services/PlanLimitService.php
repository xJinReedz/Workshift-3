<?php
/**
 * Plan and Limits Service (Basic vs Pro feature gating)
 */

namespace WorkShift\Services;

use WorkShift\Models\Client;

class PlanLimitService
{
    public static function canCreateActiveClient(array $user): bool
    {
        $plan = $user['plan'] ?? 'basic';
        if ($plan === 'pro') {
            return true;
        }

        // Basic allows up to 3 active clients (archived/completed do not count)
        $clientModel = new Client();
        $activeCount = $clientModel->countActiveByUserId((string)($user['id'] ?? ''));

        return $activeCount < 3;
    }

    public static function canUseFeature(array $user, string $feature): bool
    {
        $plan = $user['plan'] ?? 'basic';
        if ($plan === 'pro') {
            return true;
        }

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $basicPlan = $config['plans']['basic'] ?? [];

        return match ($feature) {
            'portal_payments' => !empty($basicPlan['has_portal_payments']),
            'automated_reminders' => !empty($basicPlan['has_automated_reminders']),
            'custom_scheduling' => !empty($basicPlan['has_custom_scheduling']),
            default => true,
        };
    }

    public static function hasStorageAvailable(array $user, int $additionalBytes = 0): bool
    {
        $plan = $user['plan'] ?? 'basic';
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $maxBytes = $config['plans'][$plan]['max_storage_bytes'] ?? (2 * 1024 * 1024 * 1024);

        $currentUsed = (int)($user['storage_used_bytes'] ?? 0);
        return ($currentUsed + $additionalBytes) <= $maxBytes;
    }

    public static function getUserPlanDetails(array $user): array
    {
        $plan = $user['plan'] ?? 'basic';
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $planConfig = $config['plans'][$plan] ?? $config['plans']['basic'];

        $clientModel = new Client();
        $activeClients = $clientModel->countActiveByUserId((string)($user['id'] ?? ''));
        $maxClients = $planConfig['max_active_clients'];

        $usedStorage = (int)($user['storage_used_bytes'] ?? 0);
        $maxStorage = (int)$planConfig['max_storage_bytes'];

        return [
            'plan' => $plan,
            'plan_name' => $planConfig['name'],
            'is_pro' => ($plan === 'pro'),
            'active_clients' => $activeClients,
            'max_active_clients' => $maxClients,
            'can_add_client' => ($maxClients === -1 || $activeClients < $maxClients),
            'storage_used_bytes' => $usedStorage,
            'storage_max_bytes' => $maxStorage,
            'storage_percent' => min(100, round(($usedStorage / max(1, $maxStorage)) * 100, 1)),
        ];
    }
}
