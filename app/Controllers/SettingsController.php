<?php
/**
 * Settings and Plan Controller
 * Product: WorkShift
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Helpers\Auth;
use WorkShift\Models\User;
use WorkShift\Services\SupabaseAuth;
use WorkShift\Services\PlanLimitService;

class SettingsController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    public function index(Request $request): void
    {
        $this->requireAuth();
        $user = Auth::user();
        $planDetails = PlanLimitService::getUserPlanDetails($user);

        $this->view('settings.index', [
            'pageTitle' => 'Account & Plan Settings — WorkShift',
            'user' => $user,
            'planDetails' => $planDetails,
            'showUpgrade' => (bool)$request->input('upgrade'),
        ], 'main');
    }

    public function update(Request $request): void
    {
        $this->requireAuth();
        $user = Auth::user();
        $userId = (string)$user['id'];

        $name = trim($request->input('name', $request->input('full_name', '')));
        if (empty($name)) {
            $this->flash('error', 'Full name is required.');
            $this->redirect('/settings');
            return;
        }

        $this->userModel->update($userId, [
            'name' => $name,
            'company_name' => $request->input('company_name', $request->input('studio_name')),
            'scheduling_link' => $request->input('scheduling_link'),
            'hourly_rate' => (float)$request->input('hourly_rate', $request->input('default_hourly_rate', 600.00)),
        ]);

        Auth::refresh();
        $this->flash('success', 'Profile and settings updated.');
        $this->redirect('/settings');
    }

    public function changePassword(Request $request): void
    {
        $this->requireAuth();
        $currentPassword = $request->input('current_password', '');
        $newPassword = $request->input('new_password', '');
        $confirmPassword = $request->input('confirm_password', '');

        if (empty($newPassword) || strlen($newPassword) < 8) {
            $this->flash('error', 'New password must be at least 8 characters long.');
            $this->redirect('/settings');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->flash('error', 'New password and confirmation do not match.');
            $this->redirect('/settings');
            return;
        }

        try {
            $accessToken = \WorkShift\Core\Session::get('_access_token');
            if (!$accessToken) {
                $this->flash('error', 'Session expired. Please log in again.');
                $this->redirect('/login');
                return;
            }

            $supabaseAuth = new SupabaseAuth();
            $res = $supabaseAuth->updateUserPassword($accessToken, $newPassword);

            if ($res['success']) {
                $this->flash('success', 'Your password has been changed successfully.');
            } else {
                $msg = $res['data']['message'] ?? 'Failed to update password.';
                $this->flash('error', $msg);
            }
        } catch (\Exception $e) {
            $this->flash('error', 'Password update error: ' . $e->getMessage());
        }

        $this->redirect('/settings');
    }

    public function togglePlan(Request $request): void
    {
        $this->requireAuth();
        $config = require dirname(__DIR__, 2) . '/config/config.php';

        if (empty($config['app']['allow_dev_plan_toggle'])) {
            $this->flash('error', 'Plan toggling is disabled in production.');
            $this->redirect('/settings');
            return;
        }

        $targetPlan = $request->input('plan');
        if (!in_array($targetPlan, ['basic', 'pro'])) {
            $targetPlan = 'basic';
        }

        $this->userModel->updatePlan(Auth::id(), $targetPlan);
        Auth::refresh();

        $this->flash('success', "Plan switched to " . ucfirst($targetPlan) . ".");
        $this->redirect('/settings');
    }
}
