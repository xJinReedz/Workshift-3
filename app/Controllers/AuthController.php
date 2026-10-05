<?php
/**
 * Authentication Controller
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Core\Session;
use WorkShift\Helpers\Auth;
use WorkShift\Models\User;
use WorkShift\Services\Mailer;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    public function showLogin(Request $request): void
    {
        $this->requireGuest();
        $this->view('auth.login', [
            'pageTitle' => 'Log In — WorkShift',
            'email' => Session::flash('old_email') ?? '',
        ], 'auth');
    }

    public function login(Request $request): void
    {
        $this->requireGuest();
        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');
        $ip = $request->ip();

        if (empty($email) || empty($password)) {
            $this->flash('error', 'Please enter both email and password.');
            $this->redirect('/login');
            return;
        }

        // Rate limit check: 5 attempts per 15 mins
        if ($this->userModel->isRateLimited($ip, $email)) {
            $this->flash('error', 'Too many failed login attempts. Please wait 15 minutes before trying again.');
            $this->redirect('/login');
            return;
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->userModel->recordLoginAttempt($ip, $email);
            Session::flash('old_email', $email);
            $this->flash('error', 'Invalid email or password.');
            $this->redirect('/login');
            return;
        }

        // Login success
        $this->userModel->clearLoginAttempts($ip, $email);
        Auth::login($user);

        $this->flash('success', "Welcome back, {$user['name']}!");
        $this->redirect('/dashboard');
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

        // Check if email already registered
        if ($this->userModel->findByEmail($email)) {
            Session::flash('old_input', ['name' => $name, 'email' => $email, 'company_name' => $companyName]);
            $this->flash('error', 'That email address is already in use.');
            $this->redirect('/register');
            return;
        }

        $userId = $this->userModel->create([
            'name' => $name,
            'email' => $email,
            'company_name' => $companyName ?: ($name . ' Studio'),
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'plan' => 'basic',
            'hourly_rate' => 600.00,
            'currency' => 'PHP',
        ]);

        $user = $this->userModel->findById($userId);
        Auth::login($user);

        $this->flash('success', "Account created! Welcome to WorkShift.");
        $this->redirect('/dashboard');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        $this->flash('info', 'You have been logged out.');
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
            $user = $this->userModel->findByEmail($email);
            if ($user) {
                $token = $this->userModel->createPasswordResetToken($email);
                $config = require dirname(__DIR__, 2) . '/config/config.php';
                $appUrl = rtrim($config['app']['url'] ?? 'http://localhost:8000', '/');
                $resetUrl = "{$appUrl}/reset-password?token={$token}";

                $mailer = new Mailer();
                $subject = "Reset your WorkShift password";
                $html = "<p>Hi {$user['name']},</p><p>Click below to reset your password:</p><p><a href='{$resetUrl}'>{$resetUrl}</a></p><p>This link expires in 1 hour.</p>";
                $mailer->send($email, $subject, $html);
            }
        }

        $this->flash('info', 'If that email exists in our system, we have sent password reset instructions.');
        $this->redirect('/login');
    }

    public function showReset(Request $request): void
    {
        $this->requireGuest();
        $token = $request->input('token', '');
        $reset = $this->userModel->verifyPasswordResetToken($token);

        if (!$reset) {
            $this->flash('error', 'This password reset link is invalid or has expired.');
            $this->redirect('/forgot-password');
            return;
        }

        $this->view('auth.reset', [
            'pageTitle' => 'Set New Password — WorkShift',
            'token' => $token,
        ], 'auth');
    }

    public function reset(Request $request): void
    {
        $this->requireGuest();
        $token = $request->input('token', '');
        $password = $request->input('password', '');
        $passwordConfirm = $request->input('password_confirmation', '');

        $reset = $this->userModel->verifyPasswordResetToken($token);
        if (!$reset) {
            $this->flash('error', 'This password reset link is invalid or has expired.');
            $this->redirect('/forgot-password');
            return;
        }

        if (strlen($password) < 8 || $password !== $passwordConfirm) {
            $this->flash('error', 'Password must be at least 8 characters and match confirmation.');
            $this->redirect("/reset-password?token={$token}");
            return;
        }

        $user = $this->userModel->findByEmail($reset['email']);
        if ($user) {
            $this->userModel->updatePassword((int)$user['id'], password_hash($password, PASSWORD_BCRYPT));
            $this->userModel->deletePasswordResetToken($token);
            $this->flash('success', 'Your password has been reset. You can now log in.');
            $this->redirect('/login');
            return;
        }

        $this->redirect('/login');
    }
}
