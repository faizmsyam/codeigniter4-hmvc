<?php

namespace App\Modules\Authentication\Controllers\Api;

use App\Core\FMSApiController;
use App\Libraries\FMSAuditLogger;
use App\Libraries\FMSTotpService;
use App\Config\FMSApiSecurity;
use App\Modules\Authentication\Services\FMSBackendAuthenticationService;
use App\Modules\Authentication\Services\FMSUserSessionService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Endpoint TOTP: enroll (QR + aktivasi) dan challenge (verifikasi 6 digit).
 * Pending session disimpan di `fms_totp_pending` (BUKAN session penuh),
 * expire 10 menit, tanpa grup/permission.
 */
final class FMSTotpApiController extends FMSApiController
{
    private const PENDING_TTL_SECONDS = 600;
    private const MAX_ATTEMPTS = 5;

    /** Mulai enroll: simpan secret sementara, kembalikan QR data URI. */
    public function enrollBegin(): ResponseInterface
    {
        $pending = $this->pendingUserId();
        if ($pending <= 0) {
            return $this->respondError(401, 'Sesi TOTP tidak valid, ulangi login.', null, 401);
        }

        $userRow = db_connect()->table('m_users')->where('id', $pending)->get(1)->getRowArray();
        if (! is_array($userRow)) {
            return $this->respondError(401, 'Akun tidak ditemukan.', null, 401);
        }

        try {
            $totp = new FMSTotpService($this->issuer());
            $secret = $totp->generateSecret();
            $qr = $totp->qrDataUri((string) $userRow['username'], $secret);
        } catch (Throwable $e) {
            log_message('error', 'TOTP enroll begin failed: {msg}', ['msg' => $e->getMessage()]);

            return $this->respondError(500, 'Gagal menyiapkan 2FA.', null, 500);
        }

        $session = session();
        $session->set('fms_totp_enroll_secret', (new FMSTotpService())->encryptSecret($secret));

        return $this->respondSuccess(200, 'Pindai QR dengan Google Authenticator.', [
            'qr_data_uri' => $qr,
            'username'    => (string) $userRow['username'],
        ]);
    }

    /** Aktivasi enroll: verifikasi 6 digit, simpan secret + backup code. */
    public function enrollConfirm(): ResponseInterface
    {
        $pending = $this->pendingUserId();
        if ($pending <= 0) {
            return $this->respondError(401, 'Sesi TOTP tidak valid, ulangi login.', null, 401);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->respondValidationError(['code' => ['JSON tidak valid.']]);
        }
        $code = (string) ($payload['code'] ?? '');
        if (! $this->attemptAllowed()) {
            return $this->respondError(429, 'Terlalu banyak percobaan, coba lagi 15 menit.', null, 429);
        }

        $session = session();
        $stored = (string) $session->get('fms_totp_enroll_secret');
        if ($stored === '') {
            return $this->respondError(400, 'Enroll belum dimulai.', null, 400);
        }

        try {
            $totp = new FMSTotpService();
            $secret = $totp->decryptSecret($stored);
            $valid = $totp->verify($secret, $code);
        } catch (Throwable $e) {
            log_message('error', 'TOTP enroll confirm failed: {msg}', ['msg' => $e->getMessage()]);

            return $this->respondError(500, 'Gagal memverifikasi 2FA.', null, 500);
        }

        if (! $valid) {
            $this->bumpAttempts();

            return $this->respondError(400, 'Kode 2FA salah.', null, 400);
        }

        $now = date('Y-m-d H:i:s');
        $backupCodes = $totp->generateBackupCodes();
        $db = db_connect();
        $db->transStart();
        $db->table('m_users')->where('id', $pending)->update([
            'totp_secret'     => $totp->encryptSecret($secret),
            'totp_enabled_at' => $now,
            'totp_required'   => 1,
        ]);
        $db->table('t_user_totp_backup_codes')->where('user_id', $pending)->delete();
        foreach ($backupCodes as $backupCode) {
            $db->table('t_user_totp_backup_codes')->insert([
                'user_id'    => $pending,
                'code_hash'  => password_hash($backupCode, PASSWORD_DEFAULT),
                'created_at' => $now,
            ]);
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->respondError(500, 'Gagal menyimpan 2FA.', null, 500);
        }

        FMSAuditLogger::record(event: 'totp.enrolled', module: 'auth', actorId: $pending, entityType: 'user', entityId: (string) $pending, description: '2FA diaktifkan.', httpMethod: 'POST', statusCode: 200);

        // Secret sementara dibuang, langsung terbitkan session penuh.
        $session->remove('fms_totp_enroll_secret');

        return $this->issueFullSession($pending, $backupCodes, 'totp.enrolled');
    }

    /** Challenge login biasa: kode TOTP atau salah satu backup code. */
    public function challenge(): ResponseInterface
    {
        $pending = $this->pendingUserId();
        if ($pending <= 0) {
            return $this->respondError(401, 'Sesi TOTP tidak valid, ulangi login.', null, 401);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->respondValidationError(['code' => ['JSON tidak valid.']]);
        }
        $code = trim((string) ($payload['code'] ?? ''));
        if ($code === '') {
            return $this->respondValidationError(['code' => ['Kode wajib diisi.']]);
        }
        if (! $this->attemptAllowed()) {
            return $this->respondError(429, 'Terlalu banyak percobaan, coba lagi 15 menit.', null, 429);
        }

        $userRow = db_connect()->table('m_users')->where('id', $pending)->get(1)->getRowArray();
        if (! is_array($userRow) || trim((string) ($userRow['totp_secret'] ?? '')) === '') {
            return $this->respondError(400, 'Belum enroll 2FA.', null, 400);
        }

        try {
            $totp = new FMSTotpService();
            $secret = $totp->decryptSecret((string) $userRow['totp_secret']);
            if ($totp->verify($secret, $code)) {
                FMSAuditLogger::record(event: 'totp.challenge.ok', module: 'auth', actorId: $pending, entityType: 'user', entityId: (string) $pending, description: 'Verifikasi 2FA berhasil.', httpMethod: 'POST', statusCode: 200);

                return $this->issueFullSession($pending, null, 'totp.challenge.ok');
            }
        } catch (Throwable $e) {
            log_message('error', 'TOTP challenge failed: {msg}', ['msg' => $e->getMessage()]);

            return $this->respondError(500, 'Gagal memverifikasi 2FA.', null, 500);
        }

        // Fallback: backup code (sekali pakai).
        if ($this->consumeBackupCode($pending, $code)) {
            FMSAuditLogger::record(event: 'totp.backup_used', module: 'auth', actorId: $pending, entityType: 'user', entityId: (string) $pending, description: 'Login via backup code 2FA.', httpMethod: 'POST', statusCode: 200);

            return $this->issueFullSession($pending, null, 'totp.backup_used');
        }

        $this->bumpAttempts();
        FMSAuditLogger::record(event: 'totp.challenge.fail', module: 'auth', actorId: $pending, entityType: 'user', entityId: (string) $pending, description: 'Kode 2FA salah.', httpMethod: 'POST', statusCode: 400);

        return $this->respondError(400, 'Kode 2FA salah.', null, 400);
    }

    /** Status pending untuk FE: enroll atau challenge. */
    public function status(): ResponseInterface
    {
        $pending = $this->pendingUserId();
        if ($pending <= 0) {
            return $this->respondError(401, 'Tidak ada sesi TOTP pending.', null, 401);
        }

        $userRow = db_connect()->table('m_users')->select('id, username, totp_secret')->where('id', $pending)->get(1)->getRowArray();
        $hasSecret = is_array($userRow) && trim((string) ($userRow['totp_secret'] ?? '')) !== '';

        return $this->respondSuccess(200, 'Status TOTP.', [
            'enroll'   => ! $hasSecret,
            'username' => is_array($userRow) ? (string) $userRow['username'] : '',
        ]);
    }

    private function pendingUserId(): int
    {
        $session = session();
        $pendingId = (int) $session->get('fms_totp_pending');
        $expiresAt = (int) $session->get('fms_totp_pending_exp');

        if ($pendingId <= 0 || $expiresAt <= time()) {
            $session->remove('fms_totp_pending');
            $session->remove('fms_totp_pending_exp');
            $session->remove('fms_totp_attempts');
            $session->remove('fms_totp_attempt_exp');

            return 0;
        }

        return $pendingId;
    }

    private function attemptAllowed(): bool
    {
        $session = session();
        $attempts = (int) $session->get('fms_totp_attempts');
        $exp = (int) $session->get('fms_totp_attempt_exp');

        if ($exp > 0 && $exp <= time()) {
            $session->remove('fms_totp_attempts');
            $session->remove('fms_totp_attempt_exp');

            return true;
        }

        return $attempts < self::MAX_ATTEMPTS;
    }

    private function bumpAttempts(): void
    {
        $session = session();
        $attempts = (int) $session->get('fms_totp_attempts') + 1;
        $session->set('fms_totp_attempts', $attempts);
        if ($attempts >= self::MAX_ATTEMPTS) {
            $session->set('fms_totp_attempt_exp', time() + 900);
            // Kunci juga di level akun (kolom yang sudah ada).
            $pending = (int) $session->get('fms_totp_pending');
            if ($pending > 0) {
                db_connect()->table('m_users')->where('id', $pending)->update([
                    'locked_until' => date('Y-m-d H:i:s', time() + 900),
                ]);
            }
        }
    }

    private function consumeBackupCode(int $userId, string $code): bool
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
        if ($code === '') {
            return false;
        }

        $rows = db_connect()->table('t_user_totp_backup_codes')
            ->where('user_id', $userId)->where('used_at', null)->get()->getResultArray();
        foreach ($rows as $row) {
            if (isset($row['code_hash']) && password_verify($code, (string) $row['code_hash'])) {
                db_connect()->table('t_user_totp_backup_codes')->where('id', (int) $row['id'])->update([
                    'used_at' => date('Y-m-d H:i:s'),
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string>|null $backupCodes
     */
    private function issueFullSession(int $userId, ?array $backupCodes, string $auditEvent): ResponseInterface
    {
        $authService = new FMSBackendAuthenticationService();
        $result = $authService->issueFullSessionForTotpUser(
            $userId,
            $this->request->getIPAddress(),
            $this->request->getUserAgent()->getAgentString(),
            substr((string) ($this->request->getJSON(true)['device_label'] ?? ''), 0, 191),
        );

        if ($result['authenticated'] !== true) {
            return $this->respondError(500, 'Gagal menerbitkan sesi.', null, 500);
        }

        $backendSession = session();
        $backendSession->regenerate(true);
        $sessionData = $result['session'];
        $sessionData['fms_backend_token_family_id'] = (string) ($result['tokens']['token_family_id'] ?? '');
        $backendSession->set($sessionData);
        $backendSession->remove('fms_backend_intended_url');
        // Bersihkan seluruh state pending TOTP.
        foreach (['fms_totp_pending', 'fms_totp_pending_exp', 'fms_totp_attempts', 'fms_totp_attempt_exp', 'fms_totp_enroll_secret'] as $key) {
            $backendSession->remove($key);
        }

        $currentTimestamp = date('Y-m-d H:i:s');
        (new FMSUserSessionService())->touchFamily(
            (string) ($result['tokens']['token_family_id'] ?? ''),
            $currentTimestamp,
            date('Y-m-d H:i:s', time() + config(\Config\Session::class)->expiration),
        );

        $csrfToken = $this->issueRefreshCookie($result['tokens']['presented_refresh_token']);

        // Gate must-change-password (sama seperti login biasa).
        $userRecord = $result['user'] ?? [];
        $authSettings = db_connect()->table('c_auth_settings')->orderBy('id', 'DESC')->get(1)->getRowArray() ?? [];
        $mustChangePassword = ! empty($userRecord['must_change_password'])
            && ! empty($authSettings['admin_must_change_password']);
        $redirectUrl = $mustChangePassword
            ? site_url(ROUTE_ADMIN . '/change-password')
            : site_url(ROUTE_ADMIN . '/dashboard');
        if ($mustChangePassword) {
            $backendSession->set('fms_backend_must_change_password', true);
        } else {
            $backendSession->remove('fms_backend_must_change_password');
        }

        $data = [
            'access_token' => $result['tokens']['access_token'],
            'token_type' => $result['tokens']['token_type'],
            'expires_in' => $result['tokens']['expires_in'],
            'csrf_token' => $csrfToken,
            'must_change_password' => $mustChangePassword,
            'redirect_url' => $redirectUrl,
        ];
        if ($backupCodes !== null) {
            $data['backup_codes'] = $backupCodes; // tampil SEKALI saja
        }

        return $this->respondSuccess(200, 'Autentikasi 2FA berhasil.', $data);
    }

    private function issuer(): ?string
    {
        try {
            $row = db_connect()->table('c_auth_settings')->select('totp_issuer')->orderBy('id', 'DESC')->get(1)->getRowArray();
            if (is_array($row) && trim((string) ($row['totp_issuer'] ?? '')) !== '') {
                return trim((string) $row['totp_issuer']);
            }
        } catch (Throwable) {
        }

        return null;
    }

    private function issueRefreshCookie(string $presentedRefreshToken): string
    {
        $cookieConfiguration = config(FMSApiSecurity::class);
        $csrfToken = bin2hex(random_bytes(32));
        $secureCookie = $cookieConfiguration->refreshCookieSecure && $this->request->isSecure();

        $this->response->setCookie([
            'name'     => $cookieConfiguration->refreshCookieName,
            'value'    => $presentedRefreshToken,
            'expire'   => $cookieConfiguration->refreshCookieEnabled ? $cookieConfiguration->refreshCookieTtlSeconds : 0,
            'path'     => '/',
            'secure'   => $secureCookie,
            'httponly' => $cookieConfiguration->refreshCookieHttpOnly,
            'samesite' => $cookieConfiguration->refreshCookieSameSite,
        ]);
        $this->response->setCookie([
            'name'     => $cookieConfiguration->csrfCookieName,
            'value'    => $csrfToken,
            'expire'   => $cookieConfiguration->refreshCookieTtlSeconds,
            'path'     => '/',
            'secure'   => $secureCookie,
            'httponly' => false,
            'samesite' => $cookieConfiguration->refreshCookieSameSite,
        ]);

        return $csrfToken;
    }
}
