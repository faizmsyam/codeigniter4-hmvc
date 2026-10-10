<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Settings\Validation;

use InvalidArgumentException;

final class FMSSettingsValidation
{
    /**
     * @param array<string, mixed> $payload
     * @return array{
     *     public_registration_enabled: int,
     *     public_email_verification_required: int,
     *     admin_created_email_verification_required: int,
     *     admin_must_change_password: int,
     *     login_rate_limit_enabled: int,
     *     login_max_failures: int,
     *     login_failure_window_seconds: int,
     *     login_lockout_seconds: int,
     *     verification_ttl_minutes: int,
     *     resend_cooldown_seconds: int
     * }
     */
    public function validateAuthSettings(array $payload): array
    {
        $errors = [];

        $publicRegistration = filter_var($payload['public_registration_enabled'] ?? 0, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($publicRegistration === null) {
            $publicRegistration = ! empty($payload['public_registration_enabled']) ? 1 : 0;
        } else {
            $publicRegistration = $publicRegistration ? 1 : 0;
        }

        $publicEmailVerification = filter_var($payload['public_email_verification_required'] ?? 1, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($publicEmailVerification === null) {
            $publicEmailVerification = ! empty($payload['public_email_verification_required']) ? 1 : 0;
        } else {
            $publicEmailVerification = $publicEmailVerification ? 1 : 0;
        }

        $adminEmailVerification = filter_var($payload['admin_created_email_verification_required'] ?? 0, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($adminEmailVerification === null) {
            $adminEmailVerification = ! empty($payload['admin_created_email_verification_required']) ? 1 : 0;
        } else {
            $adminEmailVerification = $adminEmailVerification ? 1 : 0;
        }

        $adminMustChangePassword = filter_var($payload['admin_must_change_password'] ?? 0, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($adminMustChangePassword === null) {
            $adminMustChangePassword = ! empty($payload['admin_must_change_password']) ? 1 : 0;
        } else {
            $adminMustChangePassword = $adminMustChangePassword ? 1 : 0;
        }

        $loginRateLimitEnabled = filter_var($payload['login_rate_limit_enabled'] ?? 1, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($loginRateLimitEnabled === null) {
            $loginRateLimitEnabled = ! empty($payload['login_rate_limit_enabled']) ? 1 : 0;
        } else {
            $loginRateLimitEnabled = $loginRateLimitEnabled ? 1 : 0;
        }

        $loginMaxFailures = isset($payload['login_max_failures']) ? (int) $payload['login_max_failures'] : 5;
        if ($loginMaxFailures < 1 || $loginMaxFailures > 100) {
            $errors['login_max_failures'] = 'Batas kegagalan login harus antara 1 sampai 100 percobaan.';
        }

        $loginFailureWindow = isset($payload['login_failure_window_seconds']) ? (int) $payload['login_failure_window_seconds'] : 900;
        if ($loginFailureWindow < 1 || $loginFailureWindow > 86400) {
            $errors['login_failure_window_seconds'] = 'Jendela kegagalan login harus antara 1 sampai 86400 detik.';
        }

        $loginLockout = isset($payload['login_lockout_seconds']) ? (int) $payload['login_lockout_seconds'] : 900;
        if ($loginLockout < 1 || $loginLockout > 86400) {
            $errors['login_lockout_seconds'] = 'Durasi penguncian login harus antara 1 sampai 86400 detik.';
        }

        $verificationTtl = isset($payload['verification_ttl_minutes']) ? (int) $payload['verification_ttl_minutes'] : 1440;
        if ($verificationTtl < 5 || $verificationTtl > 100800) {
            $errors['verification_ttl_minutes'] = 'Masa berlaku token verifikasi harus antara 5 sampai 100.800 menit (70 hari).';
        }

        $resendCooldown = isset($payload['resend_cooldown_seconds']) ? (int) $payload['resend_cooldown_seconds'] : 120;
        if ($resendCooldown < 0 || $resendCooldown > 3600) {
            $errors['resend_cooldown_seconds'] = 'Jeda kirim ulang harus antara 0 sampai 3600 detik.';
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors));
        }

        return [
            'public_registration_enabled'               => $publicRegistration,
            'public_email_verification_required'        => $publicEmailVerification,
            'admin_created_email_verification_required' => $adminEmailVerification,
            'admin_must_change_password'                => $adminMustChangePassword,
            'login_rate_limit_enabled'                  => $loginRateLimitEnabled,
            'login_max_failures'                        => $loginMaxFailures,
            'login_failure_window_seconds'              => $loginFailureWindow,
            'login_lockout_seconds'                     => $loginLockout,
            'verification_ttl_minutes'                  => $verificationTtl,
            'resend_cooldown_seconds'                   => $resendCooldown,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function validateApiKey(array $payload, bool $isUpdate = false): array
    {
        $errors = [];

        $label = trim((string) ($payload['label'] ?? ''));
        if ($label === '' && ! $isUpdate) {
            $errors['label'] = 'Label API Key wajib diisi.';
        } elseif ($label !== '' && mb_strlen($label, 'UTF-8') > 100) {
            $errors['label'] = 'Label API Key maksimal 100 karakter.';
        }

        $environment = trim((string) ($payload['environment'] ?? ''));
        if ($environment === '' && ! $isUpdate) {
            $environment = ENVIRONMENT;
        } elseif ($environment !== '' && mb_strlen($environment, 'UTF-8') > 20) {
            $errors['environment'] = 'Nama environment maksimal 20 karakter.';
        }

        $scopes = $payload['scopes'] ?? ['*'];
        if (is_string($scopes)) {
            $decoded = json_decode($scopes, true);
            $scopes = is_array($decoded) ? $decoded : preg_split('/[\s,]+/', trim($scopes));
        }
        if (! is_array($scopes) || $scopes === []) {
            $scopes = ['*'];
        }
        $scopes = array_values(array_unique(array_filter(array_map('trim', $scopes))));

        $ipAllowlist = $payload['ip_allowlist'] ?? null;
        if (is_string($ipAllowlist)) {
            $lines = preg_split('/[\r\n,]+/', trim($ipAllowlist));
            $ipAllowlist = array_values(array_unique(array_filter(array_map('trim', (array) $lines))));
            if ($ipAllowlist === []) {
                $ipAllowlist = null;
            }
        } elseif (is_array($ipAllowlist)) {
            $ipAllowlist = array_values(array_unique(array_filter(array_map('trim', $ipAllowlist))));
            if ($ipAllowlist === []) {
                $ipAllowlist = null;
            }
        } else {
            $ipAllowlist = null;
        }

        $expiresAt = trim((string) ($payload['expires_at'] ?? ''));
        $expiresAtNormalized = null;
        if ($expiresAt !== '') {
            $timestamp = strtotime($expiresAt);
            if ($timestamp === false) {
                $errors['expires_at'] = 'Format tanggal kedaluwarsa tidak valid.';
            } else {
                $expiresAtNormalized = date('Y-m-d H:i:s', $timestamp);
            }
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors));
        }

        return [
            'label'             => $label,
            'environment'       => $environment !== '' ? $environment : ENVIRONMENT,
            'scopes_json'       => json_encode($scopes),
            'ip_allowlist_json' => $ipAllowlist !== null ? json_encode($ipAllowlist) : null,
            'expires_at'        => $expiresAtNormalized,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function validateBasicAuthClient(array $payload, bool $isUpdate = false): array
    {
        $errors = [];

        $username = strtolower(trim((string) ($payload['username'] ?? '')));
        if (! $isUpdate) {
            if ($username === '') {
                $errors['username'] = 'Username client wajib diisi.';
            } elseif (! preg_match('/^[a-z0-9_\-\.]{3,60}$/', $username)) {
                $errors['username'] = 'Username hanya boleh huruf kecil, angka, titik, strip, garis bawah (3-60 karakter).';
            }
        }

        $label = trim((string) ($payload['label'] ?? ''));
        if ($label === '' && ! $isUpdate) {
            $errors['label'] = 'Label client wajib diisi.';
        } elseif ($label !== '' && mb_strlen($label, 'UTF-8') > 100) {
            $errors['label'] = 'Label client maksimal 100 karakter.';
        }

        $environment = trim((string) ($payload['environment'] ?? ''));
        if ($environment === '' && ! $isUpdate) {
            $environment = ENVIRONMENT;
        } elseif ($environment !== '' && mb_strlen($environment, 'UTF-8') > 20) {
            $errors['environment'] = 'Nama environment maksimal 20 karakter.';
        }

        $scopes = $payload['scopes'] ?? ['*'];
        if (is_string($scopes)) {
            $decoded = json_decode($scopes, true);
            $scopes = is_array($decoded) ? $decoded : preg_split('/[\s,]+/', trim($scopes));
        }
        if (! is_array($scopes) || $scopes === []) {
            $scopes = ['*'];
        }
        $scopes = array_values(array_unique(array_filter(array_map('trim', $scopes))));

        $password = (string) ($payload['password'] ?? '');
        if ($password !== '' && strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter.';
        }

        $expiresAt = trim((string) ($payload['expires_at'] ?? ''));
        $expiresAtNormalized = null;
        if ($expiresAt !== '') {
            $timestamp = strtotime($expiresAt);
            if ($timestamp === false) {
                $errors['expires_at'] = 'Format tanggal kedaluwarsa tidak valid.';
            } else {
                $expiresAtNormalized = date('Y-m-d H:i:s', $timestamp);
            }
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors));
        }

        return [
            'username'    => $username,
            'label'       => $label,
            'environment' => $environment !== '' ? $environment : ENVIRONMENT,
            'scopes_json' => json_encode($scopes),
            'password'    => $password,
            'expires_at'  => $expiresAtNormalized,
        ];
    }
}
