<?php

namespace App\Filters;

use App\Libraries\FMSJwtService;
use App\Modules\Authentication\Models\FMSUserModel;

/**
 * Resolves a bearer subject and enforces liveness: the referenced account must
 * still be active and the presented token version must match the stored one.
 */
final class FMSJwtAuthenticationVerifier implements FMSAuthenticationVerifierInterface
{
    public function __construct(
        private readonly FMSJwtService $jwtService = new FMSJwtService(),
        private readonly FMSUserModel $userModel = new FMSUserModel(),
    ) {
    }

    public function verify(string $presentedCredential): array
    {
        $verificationResult = $this->jwtService->verifyAccessToken($presentedCredential);
        if ($verificationResult['valid'] !== true || ! is_array($verificationResult['payload'])) {
            return [
                'valid' => $verificationResult['valid'],
                'reason' => $verificationResult['reason'],
                'subject' => $verificationResult['payload'],
            ];
        }

        $tokenPayload = $verificationResult['payload'];
        $storedUser = $this->resolveStoredUser($tokenPayload);
        if ($storedUser === null) {
            return ['valid' => false, 'reason' => 'JWT_TOKEN_REVOKED', 'subject' => null];
        }

        if ((string) ($storedUser['status'] ?? '') !== 'active' || ! empty($storedUser['deleted_at'])) {
            return ['valid' => false, 'reason' => 'JWT_ACCOUNT_DISABLED', 'subject' => null];
        }

        if ((int) ($tokenPayload['token_version'] ?? -1) !== (int) ($storedUser['token_version'] ?? -2)) {
            return ['valid' => false, 'reason' => 'JWT_TOKEN_REVOKED', 'subject' => null];
        }

        return ['valid' => true, 'reason' => null, 'subject' => $tokenPayload];
    }

    /** @param array<string, mixed> $tokenPayload @return array<string, mixed>|null */
    private function resolveStoredUser(array $tokenPayload): ?array
    {
        if (isset($tokenPayload['user_id'])) {
            $storedUser = $this->userModel->find((int) $tokenPayload['user_id']);

            return is_array($storedUser) ? $storedUser : null;
        }

        if (isset($tokenPayload['sub'])) {
            $storedUser = $this->userModel->where('uuid', (string) $tokenPayload['sub'])->first();

            return is_array($storedUser) ? $storedUser : null;
        }

        return null;
    }
}
