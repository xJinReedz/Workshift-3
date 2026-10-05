<?php
/**
 * Settings and Plan Controller
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Helpers\Auth;
use WorkShift\Models\User;
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
        $userId = (int)$user['id'];

        $name = trim($request->input('name', ''));
        if (empty($name)) {
            $this->flash('error', 'Name is required.');
            $this->redirect('/settings');
            return;
        }

        $this->userModel->update($userId, [
            'name' => $name,
            'company_name' => $request->input('company_name'),
            'scheduling_link' => $request->input('scheduling_link'),
            'hourly_rate' => (float)$request->input('hourly_rate', 500.00),
            'currency' => 'PHP',
        ]);

        Auth::refresh();
        $this->flash('success', 'Profile and settings updated.');
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

        $this->flash('success', "Plan switched to " . ucfirst($targetPlan) . " for testing.");
        $this->redirect('/settings');
    }
}
