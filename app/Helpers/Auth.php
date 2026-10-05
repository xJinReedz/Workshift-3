<?php
/**
 * Authentication Helper
 */

namespace WorkShift\Helpers;

use WorkShift\Core\Session;
use WorkShift\Models\User;

class Auth
{
    private static ?array $cachedUser = null;

    public static function check(): bool
    {
        return Session::has('_user_id') && Session::get('_user_id') > 0;
    }

    public static function id(): ?int
    {
        return Session::get('_user_id');
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        if (self::$cachedUser === null) {
            $userModel = new User();
            self::$cachedUser = $userModel->findById(self::id());
        }

        return self::$cachedUser;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('_user_id', (int)$user['id']);
        self::$cachedUser = $user;
    }

    public static function logout(): void
    {
        self::$cachedUser = null;
        Session::destroy();
    }

    public static function refresh(): void
    {
        self::$cachedUser = null;
    }
}
