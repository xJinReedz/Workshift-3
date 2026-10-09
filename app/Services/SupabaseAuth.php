<?php
/**
 * Supabase Auth REST Client Service (Email & Password Only)
 * Product: WorkShift
 */

namespace WorkShift\Services;

use Exception;

class SupabaseAuth
{
    private string $baseUrl;
    private string $publishableKey;
    private string $secretKey;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $url = !empty($config['supabase']['url']) ? $config['supabase']['url'] : ($_ENV['SUPABASE_URL'] ?? $_SERVER['SUPABASE_URL'] ?? getenv('SUPABASE_URL') ?: '');
        $this->baseUrl = rtrim((string)$url, '/');

        $pub = !empty($config['supabase']['publishable_key']) ? $config['supabase']['publishable_key'] : ($_ENV['SUPABASE_PUBLISHABLE_KEY'] ?? $_SERVER['SUPABASE_PUBLISHABLE_KEY'] ?? getenv('SUPABASE_PUBLISHABLE_KEY') ?: '');
        $this->publishableKey = (string)$pub;

        $sec = !empty($config['supabase']['secret_key']) ? $config['supabase']['secret_key'] : ($_ENV['SUPABASE_SECRET_KEY'] ?? $_SERVER['SUPABASE_SECRET_KEY'] ?? getenv('SUPABASE_SECRET_KEY') ?: '');
        $this->secretKey = (string)$sec;
    }

    private function request(string $endpoint, string $method = 'POST', ?array $body = null, ?string $bearerToken = null): array
    {
        if (empty($this->baseUrl) || str_contains($this->baseUrl, 'placeholder')) {
            throw new Exception("Supabase Auth API URL is not configured. Please set SUPABASE_URL in .env.");
        }

        $url = $this->baseUrl . '/auth/v1/' . ltrim($endpoint, '/');
        $ch = curl_init($url);

        $headers = [
            'apikey: ' . $this->publishableKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($bearerToken) {
            $headers[] = 'Authorization: Bearer ' . $bearerToken;
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Supabase Auth cURL Network Error: " . $error);
        }

        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded)) {
            $decoded = ['message' => $response];
        }

        return [
            'status' => $httpCode,
            'data' => $decoded,
            'success' => $httpCode >= 200 && $httpCode < 300,
        ];
    }

    public function signUp(string $email, string $password, array $data = []): array
    {
        return $this->request('signup', 'POST', [
            'email' => $email,
            'password' => $password,
            'data' => $data,
        ]);
    }

    public function signInWithPassword(string $email, string $password): array
    {
        return $this->request('token?grant_type=password', 'POST', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function refreshToken(string $refreshToken): array
    {
        return $this->request('token?grant_type=refresh_token', 'POST', [
            'refresh_token' => $refreshToken,
        ]);
    }

    public function verifyTokenHash(string $tokenHash, string $type): array
    {
        return $this->request('verify', 'POST', [
            'token_hash' => $tokenHash,
            'type' => $type, // 'email' or 'recovery'
        ]);
    }

    public function sendPasswordReset(string $email): array
    {
        return $this->request('recover', 'POST', [
            'email' => $email,
        ]);
    }

    public function resendConfirmation(string $email): array
    {
        return $this->request('resend', 'POST', [
            'email' => $email,
            'type' => 'signup',
        ]);
    }

    public function updateUserPassword(string $accessToken, string $newPassword): array
    {
        return $this->request('user', 'PUT', [
            'password' => $newPassword,
        ], $accessToken);
    }

    public function adminCreateUser(string $email, string $password, array $data = []): array
    {
        if (empty($this->secretKey) || str_contains($this->secretKey, 'placeholder')) {
            // Fallback to normal signup if secret key is not set
            return $this->signUp($email, $password, $data);
        }

        $url = $this->baseUrl . '/auth/v1/admin/users';
        $ch = curl_init($url);

        $headers = [
            'apikey: ' . $this->secretKey,
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $body = [
            'email' => $email,
            'password' => $password,
            'email_confirm' => true,
            'user_metadata' => $data,
        ];

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode((string)$response, true);
        return [
            'status' => $httpCode,
            'data' => is_array($decoded) ? $decoded : ['message' => $response],
            'success' => $httpCode >= 200 && $httpCode < 300,
        ];
    }

    public function signOut(string $accessToken, bool $global = false): array
    {
        $endpoint = $global ? 'logout?scope=global' : 'logout';
        return $this->request($endpoint, 'POST', null, $accessToken);
    }
}
