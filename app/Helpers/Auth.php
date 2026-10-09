<?php
/**
 * Authentication Helper with Supabase Auth Session Management
 * Product: WorkShift
 */

namespace WorkShift\Helpers;

use WorkShift\Core\Session;
use WorkShift\Models\User;
use WorkShift\Services\SupabaseAuth;

class Auth
{
    private static ?array $cachedUser = null;

    public static function check(): bool
    {
        self::verifyAndRefreshToken();
        $id = Session::get('_user_id') ?? Session::get('user_id');
        if (empty($id) || !is_valid_uuid((string)$id)) {
            if (!empty($id)) {
                Session::destroy();
            }
            return false;
        }
        return true;
    }

    public static function id(): ?string
    {
        if (!self::check()) {
            return null;
        }
        $id = Session::get('_user_id') ?? Session::get('user_id');
        return $id ? (string)$id : null;
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

    public static function isFreelancer(): bool
    {
        $user = self::user();
        return $user ? !empty($user['is_freelancer']) : false;
    }

    public static function isClient(): bool
    {
        $user = self::user();
        return $user ? empty($user['is_freelancer']) : false;
    }

    public static function login(array $profile, array $tokens = []): void
    {
        Session::regenerate();
        Session::set('_user_id', (string)$profile['id']);
        Session::set('_email', (string)$profile['email']);
        Session::set('_is_freelancer', !empty($profile['is_freelancer']));

        if (!empty($tokens['access_token'])) {
            Session::set('_access_token', $tokens['access_token']);
            Session::set('_refresh_token', $tokens['refresh_token'] ?? null);
            Session::set('_expires_at', time() + ($tokens['expires_in'] ?? 3600));
        }

        self::$cachedUser = $profile;
    }

    public static function logout(): void
    {
        $accessToken = Session::get('_access_token');
        if ($accessToken) {
            try {
                $supabaseAuth = new SupabaseAuth();
                $supabaseAuth->signOut($accessToken);
            } catch (\Exception $e) {
                // Ignore API logout error and clear session locally
            }
        }

        self::$cachedUser = null;
        Session::destroy();
    }

    public static function logoutAllDevices(): void
    {
        $accessToken = Session::get('_access_token');
        if ($accessToken) {
            try {
                $supabaseAuth = new SupabaseAuth();
                $supabaseAuth->signOut($accessToken, true);
            } catch (\Exception $e) {
                // Ignore API error
            }
        }

        self::$cachedUser = null;
        Session::destroy();
    }

    private static function verifyAndRefreshToken(): void
    {
        if (!Session::has('_access_token')) {
            return;
        }

        $expiresAt = (int)Session::get('_expires_at', 0);
        $refreshToken = Session::get('_refresh_token');

        // If access token expires within 2 minutes, refresh it automatically
        if ($expiresAt > 0 && (time() + 120) >= $expiresAt && !empty($refreshToken)) {
            try {
                $supabaseAuth = new SupabaseAuth();
                $res = $supabaseAuth->refreshToken($refreshToken);
                if ($res['success'] && !empty($res['data']['access_token'])) {
                    Session::set('_access_token', $res['data']['access_token']);
                    Session::set('_refresh_token', $res['data']['refresh_token'] ?? $refreshToken);
                    Session::set('_expires_at', time() + ($res['data']['expires_in'] ?? 3600));
                } else {
                    // Token refresh failed, clear session
                    self::$cachedUser = null;
                    Session::destroy();
                }
            } catch (\Exception $e) {
                self::$cachedUser = null;
                Session::destroy();
            }
        }
    }

    public static function refresh(): void
    {
        self::$cachedUser = null;
    }
}
