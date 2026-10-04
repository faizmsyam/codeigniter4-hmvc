<?php

namespace App\Modules\Authentication\Controllers\Api;

use App\Config\FMSApiSecurity;
use App\Config\FMSJwt;
use App\Core\FMSApiController;
use App\Filters\FMSRequestContext;
use App\Libraries\FMSAuditLogger;
use App\Libraries\FMSJwtService;
use App\Libraries\FMSPermissionClaimService;
use App\Modules\Authentication\Models\FMSRefreshTokenModel;
use App\Modules\Authentication\Models\FMSUserModel;
use App\Modules\Authentication\Repositories\FMSDatabaseRefreshTokenRepository;
use App\Modules\Authentication\Services\FMSBackendAuthenticationService;
use App\Modules\Authentication\Services\FMSRefreshTokenService;
use App\Modules\Authentication\Services\FMSLoginAttemptService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class FMSAuthenticationApiController extends FMSApiController
{
    public function login(): ResponseInterface
    {
        if (! $this->request->is('json')) {
            return $this->respondError(415, 'Content-Type harus application/json.', null, 415);
        }

        $requestPayload = $this->request->getJSON(true);
        if (! is_array($requestPayload)) {
            return $this->respondValidationError(['payload' => ['JSON tidak valid.']]);
        }

        $identifier = trim((string) ($requestPayload['identifier'] ?? ''));
        $password = (string) ($requestPayload['password'] ?? '');
        if ($identifier === '' || $password === '' || strlen($identifier) > 190 || strlen($password) > 1024) {
            return $this->respondValidationError(['credentials' => ['Kredensial tidak valid.']]);
        }

        try {
            $authenticationResult = (new FMSBackendAuthenticationService())->authenticate(
                $identifier,
                $password,
                $this->request->getIPAddress(),
                $this->request->getUserAgent()->getAgentString(),
                substr((string) ($requestPayload['device_label'] ?? ''), 0, 191),
            );

            if ($authenticationResult['authenticated'] !== true) {
                return $this->respondError(400, $authenticationResult['message'], null, $authenticationResult['status_code']);
            }

            $backendSession = session();
            $backendSession->regenerate(true);
            $backendSession->set($authenticationResult['session']);
            $backendSession->remove('fms_backend_intended_url');

            $csrfToken = $this->issueRefreshCookie($authenticationResult['tokens']['presented_refresh_token']);

            return $this->respondSuccess(200, 'Autentikasi berhasil.', [
                'access_token' => $authenticationResult['tokens']['access_token'],
                'token_type' => $authenticationResult['tokens']['token_type'],
                'expires_in' => $authenticationResult['tokens']['expires_in'],
                'csrf_token' => $csrfToken,
                'redirect_url' => site_url(ROUTE_ADMIN . '/dashboard'),
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'Authentication login failed: {message}', ['message' => $exception->getMessage()]);

            return $this->respondError(500, 'Autentikasi gagal diproses.', null, 500);
        }
    }

    public function refresh(): ResponseInterface
    {
        $presentedRefreshToken = $this->resolvePresentedRefreshToken();
        if ($presentedRefreshToken === null) {
            return $this->respondValidationError(['refresh_token' => ['Refresh token wajib diisi.']]);
        }

        try {
            $refreshTokenService = new FMSRefreshTokenService(
                new FMSDatabaseRefreshTokenRepository(),
                config(FMSJwt::class)->refreshTokenTtlSeconds,
            );
            $rotationResult = $refreshTokenService->rotateToken(
                $presentedRefreshToken,
                date('Y-m-d H:i:s'),
                substr((string) $this->refreshDeviceLabel(), 0, 191),
                hash('sha256', $this->request->getIPAddress()),
                hash('sha256', $this->request->getUserAgent()->getAgentString()),
            );

            if ($rotationResult['status'] !== FMSRefreshTokenService::STATUS_ROTATED || ! is_array($rotationResult['user'] ?? null)) {
                $this->clearRefreshCookie();

                return $this->respondError(401, 'Refresh token tidak valid.', null, 401);
            }

            $userRecord = $rotationResult['user'];
            $permissionClaims = new FMSPermissionClaimService();
            $permissionCodes = $permissionClaims->forUser((int) $userRecord['id']);
            $accessToken = (new FMSJwtService())->issueAccessToken(
                (string) $userRecord['uuid'],
                (int) ($userRecord['token_version'] ?? 1),
                $permissionClaims->claims((int) $userRecord['id'], $permissionCodes),
            );
            $this->issueRefreshCookie($rotationResult['selector'] . '.' . $rotationResult['validator']);
            $csrfHeaderValue = trim((string) $this->request->getHeaderLine(
                $this->refreshCookieConfiguration()->csrfHeaderName,
            ));
            $csrfToken = $csrfHeaderValue !== '' ? $csrfHeaderValue : bin2hex(random_bytes(32));

            return $this->respondSuccess(200, 'Token berhasil diperbarui.', [
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => config(FMSJwt::class)->accessTokenTtlSeconds,
                'csrf_token' => $csrfToken,
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'Authentication refresh failed: {message}', ['message' => $exception->getMessage()]);

            return $this->respondError(500, 'Refresh token gagal diproses.', null, 500);
        }
    }

    public function logout(): ResponseInterface
    {
        $backendSession = session();
        $sessionUserId = $backendSession->get('fms_backend_user_id');
        $sessionUsername = $backendSession->get('fms_backend_username');

        $presentedRefreshToken = $this->resolvePresentedRefreshToken();
        if ($presentedRefreshToken !== null) {
            $parsedToken = (new FMSRefreshTokenService(new FMSDatabaseRefreshTokenRepository()))->parsePresentedToken($presentedRefreshToken);
            if (is_array($parsedToken)) {
                $refreshTokenModel = new FMSRefreshTokenModel();
                $storedToken = $refreshTokenModel->where('selector', $parsedToken['selector'])->first();
                if (is_array($storedToken)) {
                    (new FMSDatabaseRefreshTokenRepository())->revokeFamily((string) $storedToken['token_family_id'], date('Y-m-d H:i:s'));
                }
            }
        }

        FMSAuditLogger::record(
            event: 'auth.logout',
            module: 'auth',
            actorId: is_numeric($sessionUserId) ? (int) $sessionUserId : null,
            entityType: 'user',
            entityId: $sessionUserId !== null ? (string) $sessionUserId : null,
            description: 'Logout untuk akun: ' . ($sessionUsername ?: 'user #' . $sessionUserId),
            httpMethod: 'POST',
            statusCode: 200,
        );

        $this->clearRefreshCookie();

        foreach (array_keys($backendSession->get()) as $sessionKey) {
            if (str_starts_with((string) $sessionKey, 'fms_backend_')) {
                $backendSession->remove((string) $sessionKey);
            }
        }
        $backendSession->regenerate(true);

        return $this->respondSuccess(200, 'Sesi berhasil diakhiri.');
    }

    public function me(): ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }

        $userRecord = null;
        if (isset($authenticatedSubject['user_id'])) {
            $userRecord = (new FMSUserModel())->find((int) $authenticatedSubject['user_id']);
        }
        if (! is_array($userRecord) && isset($authenticatedSubject['sub'])) {
            $userRecord = (new FMSUserModel())->where('uuid', (string) $authenticatedSubject['sub'])->first();
        }
        if (! is_array($userRecord) || (string) ($userRecord['status'] ?? '') !== 'active') {
            return $this->respondError(401, 'Token akses tidak valid.', null, 401);
        }

        return $this->respondSuccess(200, 'Profil autentikasi berhasil dimuat.', [
            'uuid' => $userRecord['uuid'],
            'username' => $userRecord['username'],
            'email' => $userRecord['email'],
            'full_name' => $userRecord['full_name'],
            'status' => $userRecord['status'],
            'email_verified_at' => $userRecord['email_verified_at'],
        ]);
    }

    private function authenticationPolicy(): array
    {
        $settings = db_connect()->table('c_auth_settings')->orderBy('id', 'DESC')->get(1)->getRowArray() ?? [];

        return [
            'admin_created_email_verification_required' => (bool) ($settings['admin_created_email_verification_required'] ?? true),
            'public_email_verification_required' => (bool) ($settings['public_email_verification_required'] ?? true),
        ];
    }

    private function refreshCookieConfiguration(): FMSApiSecurity
    {
        return config(FMSApiSecurity::class);
    }

    private function refreshDeviceLabel(): string
    {
        $requestPayload = $this->request->getJSON(true);

        return is_array($requestPayload) ? (string) ($requestPayload['device_label'] ?? '') : '';
    }

    private function resolvePresentedRefreshToken(): ?string
    {
        $cookieName = $this->refreshCookieConfiguration()->refreshCookieName;
        $cookieToken = trim((string) $this->request->getCookie($cookieName));
        if ($cookieToken !== '') {
            $csrfHeader = trim((string) $this->request->getHeaderLine(
                $this->refreshCookieConfiguration()->csrfHeaderName,
            ));
            $csrfCookie = trim((string) $this->request->getCookie(
                $this->refreshCookieConfiguration()->csrfCookieName,
            ));
            if ($csrfHeader === '' || $csrfCookie === '' || ! hash_equals($csrfCookie, $csrfHeader)) {
                return null;
            }

            return $cookieToken;
        }

        return null;
    }

    private function issueRefreshCookie(string $presentedRefreshToken): string
    {
        $cookieConfiguration = $this->refreshCookieConfiguration();
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

    private function clearRefreshCookie(): void
    {
        $cookieConfiguration = $this->refreshCookieConfiguration();
        $this->response->deleteCookie($cookieConfiguration->refreshCookieName);
        $this->response->deleteCookie($cookieConfiguration->csrfCookieName);
    }
}
