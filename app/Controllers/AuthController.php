<?php
/**
 * Authentication Controller (Supabase Auth REST Integration)
 * Product: WorkShift
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Core\Session;
use WorkShift\Helpers\Auth;
use WorkShift\Models\User;
use WorkShift\Services\SupabaseAuth;

class AuthController extends Controller
{
    private User $userModel;
    private SupabaseAuth $supabaseAuth;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->supabaseAuth = new SupabaseAuth();
    }

    public function showLogin(Request $request): void
    {
        $this->requireGuest();
        $this->view('auth.login', [
            'pageTitle' => 'Log In — WorkShift',
            'email' => Session::flash('old_email') ?? '',
            'returnTo' => $request->input('return_to', ''),
        ], 'auth');
    }

    public function login(Request $request): void
    {
        $this->requireGuest();
        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');
        $returnTo = $request->input('return_to', '');

        if (empty($email) || empty($password)) {
            $this->flash('error', 'Please enter both email and password.');
            $this->redirect('/login');
            return;
        }

        try {
            $res = $this->supabaseAuth->signInWithPassword($email, $password);

            if (!$res['success']) {
                $errorMsg = $res['data']['error_description'] ?? $res['data']['msg'] ?? $res['data']['message'] ?? 'Invalid email or password.';
                
                if (str_contains(strtolower($errorMsg), 'email not confirmed')) {
                    Session::flash('old_email', $email);
                    Session::flash('unconfirmed_email', $email);
                    $this->flash('warning', 'Please confirm your email address before logging in.');
                } else {
                    Session::flash('old_email', $email);
                    $this->flash('error', 'Email or password is incorrect.');
                }
                $this->redirect('/login');
                return;
            }

            $tokens = $res['data'];
            $userData = $tokens['user'] ?? [];
            $userId = $userData['id'] ?? null;

            if (!$userId) {
                $this->flash('error', 'Authentication failed. Please try again.');
                $this->redirect('/login');
                return;
            }

            // Upsert profile in PostgreSQL
            $existing = $this->userModel->findById($userId);
            $meta = $userData['user_metadata'] ?? [];

            $profile = [
                'id' => $userId,
                'email' => $email,
                'full_name' => $existing['full_name'] ?? $meta['full_name'] ?? $meta['name'] ?? explode('@', $email)[0],
                'studio_name' => $existing['studio_name'] ?? $meta['studio_name'] ?? null,
                'default_hourly_rate' => $existing['default_hourly_rate'] ?? 600.00,
                'plan' => $existing['plan'] ?? 'basic',
                'is_freelancer' => isset($existing['is_freelancer']) ? (bool)$existing['is_freelancer'] : true,
            ];

            $this->userModel->upsertProfile($profile);
            $freshProfile = $this->userModel->findById($userId);

            Auth::login($freshProfile ?: $profile, $tokens);

            if (!empty($returnTo) && str_starts_with($returnTo, '/') && !str_starts_with($returnTo, '//')) {
                $this->redirect($returnTo);
                return;
            }

            if (Auth::isFreelancer()) {
                $this->flash('success', "Welcome back, " . ($freshProfile['full_name'] ?? 'Freelancer') . "!");
                $this->redirect('/dashboard');
            } else {
                $this->flash('success', "Welcome to your Client Workspace!");
                $this->redirect('/portal');
            }

        } catch (\Exception $e) {
            Session::flash('old_email', $email);
            $this->flash('error', 'Authentication service error: ' . $e->getMessage());
            $this->redirect('/login');
        }
    }

    public function showRegister(Request $request): void
    {
        $this->requireGuest();
        $this->view('auth.register', [
            'pageTitle' => 'Create Your WorkShift Account',
            'old' => Session::flash('old_input') ?? [],
        ], 'auth');
    }

    public function register(Request $request): void
    {
        $this->requireGuest();
        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));
        $companyName = trim($request->input('company_name', ''));
        $password = $request->input('password', '');
        $passwordConfirm = $request->input('password_confirmation', '');

        $errors = [];
        if (empty($name)) $errors[] = 'Full name is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters long.';
        if ($password !== $passwordConfirm) $errors[] = 'Password confirmation does not match.';

        if (!empty($errors)) {
            Session::flash('old_input', ['name' => $name, 'email' => $email, 'company_name' => $companyName]);
            $this->flash('error', implode('<br>', $errors));
            $this->redirect('/register');
            return;
        }

        try {
            $res = $this->supabaseAuth->signUp($email, $password, [
                'full_name' => $name,
                'studio_name' => $companyName,
            ]);

            if (!$res['success']) {
                $msg = $res['data']['message'] ?? $res['data']['error_description'] ?? 'Registration failed.';
                Session::flash('old_input', ['name' => $name, 'email' => $email, 'company_name' => $companyName]);
                $this->flash('error', $msg);
                $this->redirect('/register');
                return;
            }

            $userId = $res['data']['id'] ?? $res['data']['user']['id'] ?? null;
            if ($userId) {
                $this->userModel->upsertProfile([
                    'id' => $userId,
                    'email' => $email,
                    'full_name' => $name,
                    'studio_name' => $companyName ?: ($name . ' Studio'),
                    'default_hourly_rate' => 600.00,
                    'plan' => 'basic',
                    'is_freelancer' => true,
                ]);
            }

            Session::flash('info', 'Check your email to confirm your account before logging in.');
            $this->redirect('/login');

        } catch (\Exception $e) {
            Session::flash('old_input', ['name' => $name, 'email' => $email, 'company_name' => $companyName]);
            $this->flash('error', 'Registration error: ' . $e->getMessage());
            $this->redirect('/register');
        }
    }

    public function confirm(Request $request): void
    {
        $tokenHash = $request->input('token_hash', '');
        $type = $request->input('type', 'email');

        if (empty($tokenHash)) {
            $this->flash('error', 'Invalid or missing confirmation link.');
            $this->redirect('/login');
            return;
        }

        try {
            $res = $this->supabaseAuth->verifyTokenHash($tokenHash, $type);
            if ($res['success']) {
                $this->flash('success', 'Your email has been confirmed! You may now sign in.');
            } else {
                $this->flash('error', 'Confirmation link has expired or is invalid. Please sign in or request a new link.');
            }
        } catch (\Exception $e) {
            $this->flash('error', 'Verification error: ' . $e->getMessage());
        }

        $this->redirect('/login');
    }

    public function resendConfirmation(Request $request): void
    {
        $email = trim($request->input('email', ''));
        if (empty($email)) {
            $this->flash('error', 'Email address is required.');
            $this->redirect('/login');
            return;
        }

        try {
            $this->supabaseAuth->resendConfirmation($email);
            $this->flash('info', 'Confirmation email has been resent. Please check your inbox.');
        } catch (\Exception $e) {
            $this->flash('error', 'Failed to resend confirmation email.');
        }

        $this->redirect('/login');
    }

    public function showForgot(Request $request): void
    {
        $this->requireGuest();
        $this->view('auth.forgot', [
            'pageTitle' => 'Reset Your Password — WorkShift',
        ], 'auth');
    }

    public function forgot(Request $request): void
    {
        $this->requireGuest();
        $email = trim($request->input('email', ''));

        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $this->supabaseAuth->sendPasswordReset($email);
            } catch (\Exception $e) {
                // Ignore error for privacy
            }
        }

        $this->flash('info', 'If that email exists in our system, we have sent password reset instructions.');
        $this->redirect('/login');
    }

    public function showReset(Request $request): void
    {
        $tokenHash = $request->input('token_hash', '');
        $type = $request->input('type', 'recovery');

        if (!empty($tokenHash)) {
            try {
                $res = $this->supabaseAuth->verifyTokenHash($tokenHash, $type);
                if ($res['success'] && !empty($res['data']['access_token'])) {
                    Session::set('_recovery_access_token', $res['data']['access_token']);
                } else {
                    $this->flash('error', 'This password reset link is invalid or has expired.');
                    $this->redirect('/forgot-password');
                    return;
                }
            } catch (\Exception $e) {
                $this->flash('error', 'Invalid password reset link.');
                $this->redirect('/forgot-password');
                return;
            }
        }

        if (!Session::has('_recovery_access_token')) {
            $this->flash('error', 'Your password reset session has expired.');
            $this->redirect('/forgot-password');
            return;
        }

        $this->view('auth.reset', [
            'pageTitle' => 'Set New Password — WorkShift',
        ], 'auth');
    }

    public function reset(Request $request): void
    {
        $recoveryToken = Session::get('_recovery_access_token');
        if (!$recoveryToken) {
            $this->flash('error', 'Password reset session expired. Please request a new link.');
            $this->redirect('/forgot-password');
            return;
        }

        $password = $request->input('password', '');
        $passwordConfirm = $request->input('password_confirmation', '');

        if (strlen($password) < 8 || $password !== $passwordConfirm) {
            $this->flash('error', 'Password must be at least 8 characters and match confirmation.');
            $this->redirect('/reset-password');
            return;
        }

        try {
            $res = $this->supabaseAuth->updateUserPassword($recoveryToken, $password);
            Session::remove('_recovery_access_token');

            if ($res['success']) {
                $this->flash('success', 'Your password has been updated successfully. You can now log in.');
                $this->redirect('/login');
                return;
            } else {
                $msg = $res['data']['message'] ?? 'Failed to update password.';
                $this->flash('error', $msg);
                $this->redirect('/forgot-password');
                return;
            }
        } catch (\Exception $e) {
            $this->flash('error', 'Password reset error: ' . $e->getMessage());
            $this->redirect('/forgot-password');
        }
    }

    public function showLogoutConfirm(Request $request): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $this->view('auth.logout_confirm', [
            'pageTitle' => 'Log Out of WorkShift?',
        ], 'auth');
    }

    public function logout(Request $request): void
    {
        Auth::logout();

        if ($request->isAjax()) {
            json_success(['redirect' => base_url('/')]);
            return;
        }

        Session::flash('info', 'You have been logged out of WorkShift.');
        $this->redirect(base_url('/'));
    }

    public function logoutAll(Request $request): void
    {
        Auth::logoutAllDevices();

        if ($request->isAjax()) {
            json_success(['redirect' => base_url('/')]);
            return;
        }

        Session::flash('info', 'You have been logged out of all devices.');
        $this->redirect(base_url('/'));
    }
}
