<?php

namespace App\Modules\Authentication\Services;

use App\Libraries\FMSAuditLogger;
use App\Libraries\FMSPermissionClaimService;
use App\Modules\Authentication\Repositories\FMSDatabaseAuthenticationRepository;
use App\Modules\Authentication\Repositories\FMSDatabaseRefreshTokenRepository;

/**
 * Single authentication processor shared by the login API.
 *
 * Browser and API callers use the same credential policy and token services.
 * This class owns the backend session, the JWT pair, and the login audit
 * trail so view controllers never duplicate that responsibility.
 */
final class FMSBackendAuthenticationService
{
    /** @return array{authenticated: bool, status_code: int, message: string, session: array<string, mixed>, tokens: array<string, mixed>} */
    public function authenticate(
        string $identifier,
        string $password,
        string $clientIpAddress,
        string $userAgent,
        string $deviceLabel = '',
    ): array {
        $currentTimestamp = date('Y-m-d H:i:s');
        $loginResult = (new FMSLoginAttemptService(new FMSDatabaseAuthenticationRepository()))->attemptLogin(
            $identifier,
            $password,
            $clientIpAddress,
            $this->authenticationPolicy(),
            $currentTimestamp,
        );

        if ($loginResult['status'] !== FMSLoginAttemptService::STATUS_AUTHENTICATED
            || ! is_array($loginResult['user'] ?? null)) {
            $statusCode = $loginResult['status'] === FMSLoginAttemptService::STATUS_RATE_LIMITED ? 429 : 401;

            FMSAuditLogger::record(
                event: 'auth.login.failed',
                module: 'auth',
                actorId: null,
                entityType: 'user',
                entityId: $identifier,
                description: 'Login gagal: ' . FMSAuthenticationFailureMessage::forStatus(
                    $loginResult['status'],
                    isset($loginResult['reason']) && is_string($loginResult['reason']) ? $loginResult['reason'] : null,
                ) . ' (Identifier: ' . $identifier . ')',
                before: ['identifier' => $identifier, 'ip_hash' => hash('sha256', $clientIpAddress)],
                httpMethod: 'POST',
                statusCode: 401,
            );

            return [
                'authenticated' => false,
                'status_code' => $statusCode,
                'message' => FMSAuthenticationFailureMessage::forStatus(
                    $loginResult['status'],
                    isset($loginResult['reason']) && is_string($loginResult['reason']) ? $loginResult['reason'] : null,
                ),
                'session' => [],
                'tokens' => [],
            ];
        }

        $userRecord = $loginResult['user'];

        /* Ambil group pertama yang dimiliki user untuk dijadikan active group saat login */
        $firstGroupId = 0;
        try {
            $firstGroupRow = db_connect()
                ->table('t_user_groups')
                ->select('group_id')
                ->where('user_id', (int) $userRecord['id'])
                ->orderBy('id', 'ASC')
                ->get(1)
                ->getRowArray();
            if (is_array($firstGroupRow) && isset($firstGroupRow['group_id'])) {
                $firstGroupId = (int) $firstGroupRow['group_id'];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Auth: load first group failed: {msg}', ['msg' => $e->getMessage()]);
        }

        /* Hak akses dihitung hanya dari kelompok aktif agar ganti role konsisten */
        $permissionCodes = [];
        if ($firstGroupId > 0) {
            $permissionCodes = (new \App\Modules\Privileges\Services\FMSPrivilegesEffectivePermissionService(
                new \App\Modules\Privileges\Repositories\FMSDatabasePrivilegesPermissionRepository(),
            ))->resolveEffectivePermissionCodesForGroup($firstGroupId);
        } else {
            $permissionCodes = $this->effectivePermissionCodes((int) $userRecord['id']);
        }

        return [
            'authenticated' => true,
            'status_code' => 200,
            'message' => 'Login berhasil.',
            'session' => [
                'fms_backend_authenticated' => true,
                'fms_backend_user_id' => (int) $userRecord['id'],
                'fms_backend_user_uuid' => (string) $userRecord['uuid'],
                'fms_backend_username' => (string) $userRecord['username'],
                'fms_backend_email' => (string) $userRecord['email'],
                'fms_backend_full_name' => (string) ($userRecord['full_name'] ?: $userRecord['username']),
                'fms_backend_avatar' => (string) ($userRecord['avatar'] ?? ''),
                'fms_backend_active_group_id' => $firstGroupId,
                'fms_backend_permissions' => $permissionCodes,
                'fms_backend_logged_in_at' => time(),
                'fms_backend_ip_hash' => hash('sha256', $clientIpAddress),
                'fms_backend_user_agent_hash' => hash('sha256', $userAgent),
            ],
            'tokens' => $this->issueAuthenticationTokensForSubject((int) $userRecord['id'], (string) $userRecord['uuid'], (int) ($userRecord['token_version'] ?? 1), $permissionCodes, $clientIpAddress, $userAgent, $deviceLabel, $currentTimestamp),
            'user' => [
                'id' => (int) $userRecord['id'],
                'uuid' => (string) $userRecord['uuid'],
                'username' => (string) $userRecord['username'],
                'email' => (string) $userRecord['email'],
                'status' => (string) ($userRecord['status'] ?? 'active'),
            ],
        ];
    }

    /** @return array<int, string> */
    private function effectivePermissionCodes(int $userIdentifier): array
    {
        return (new \App\Modules\Privileges\Services\FMSPrivilegesEffectivePermissionService(
            new \App\Modules\Privileges\Repositories\FMSDatabasePrivilegesPermissionRepository(),
        ))->resolveEffectivePermissionCodesForUser($userIdentifier);
    }

    /** @return array{access_token: string, token_type: string, expires_in: int, presented_refresh_token: string} */
    private function issueAuthenticationTokens(int $userIdentifier, string $userUuid, int $tokenVersion, string $clientIpAddress, string $userAgent, string $deviceLabel, string $currentTimestamp): array
    {
        return $this->issueAuthenticationTokensForSubject($userIdentifier, $userUuid, $tokenVersion, [], $clientIpAddress, $userAgent, $deviceLabel, $currentTimestamp);
    }

    /** @param array<int, string> $permissionCodes @return array{access_token: string, token_type: string, expires_in: int, presented_refresh_token: string} */
    private function issueAuthenticationTokensForSubject(int $userIdentifier, string $userUuid, int $tokenVersion, array $permissionCodes, string $clientIpAddress, string $userAgent, string $deviceLabel, string $currentTimestamp): array
    {
        $jwtService = new \App\Libraries\FMSJwtService();
        $refreshTokenService = new FMSRefreshTokenService(
            new FMSDatabaseRefreshTokenRepository(),
            config(\App\Config\FMSJwt::class)->refreshTokenTtlSeconds,
        );

        $refreshToken = $refreshTokenService->issueToken(
            $userIdentifier,
            $currentTimestamp,
            substr($deviceLabel, 0, 191),
            hash('sha256', $clientIpAddress),
            hash('sha256', $userAgent),
        );

        FMSAuditLogger::record(
            event: 'auth.login.success',
            module: 'auth',
            actorId: $userIdentifier,
            entityType: 'user',
            entityId: (string) $userIdentifier,
            description: 'Login berhasil untuk akun ID: ' . $userIdentifier,
            after: ['ip_hash' => hash('sha256', $clientIpAddress)],
            httpMethod: 'POST',
            statusCode: 200,
        );

        return [
            'access_token' => $jwtService->issueAccessToken(
                $userUuid,
                $tokenVersion,
                (new FMSPermissionClaimService())->claims($userIdentifier, $permissionCodes),
            ),
            'token_type' => 'Bearer',
            'expires_in' => config(\App\Config\FMSJwt::class)->accessTokenTtlSeconds,
            'presented_refresh_token' => $refreshToken['presented'],
        ];
    }

    /** @return array{admin_created_email_verification_required: bool, public_email_verification_required: bool} */
    private function authenticationPolicy(): array
    {
        $settings = db_connect()->table('c_auth_settings')->orderBy('id', 'DESC')->get(1)->getRowArray() ?? [];

        return [
            'admin_created_email_verification_required' => (bool) ($settings['admin_created_email_verification_required'] ?? true),
            'public_email_verification_required' => (bool) ($settings['public_email_verification_required'] ?? true),
        ];
    }
}
