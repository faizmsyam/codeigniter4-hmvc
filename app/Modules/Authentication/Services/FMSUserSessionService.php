<?php

namespace App\Modules\Authentication\Services;

use App\Modules\Authentication\Models\FMSUserSessionModel;

/** Keeps the browser/API login session in sync with its refresh-token family. */
final class FMSUserSessionService
{
    public function __construct(
        private readonly FMSUserSessionModel $sessionModel = new FMSUserSessionModel(),
    ) {
    }

    public function create(
        int $userIdentifier,
        string $tokenFamilyIdentifier,
        string $deviceLabel,
        string $ipHash,
        string $userAgentHash,
        string $currentTimestamp,
        string $expiresAt,
    ): string {
        $sessionUuid = $this->uuidV4();
        $this->sessionModel->insert([
            'session_uuid'     => $sessionUuid,
            'user_id'          => $userIdentifier,
            'token_family_id'  => $tokenFamilyIdentifier,
            'device_label'     => substr($deviceLabel, 0, 100),
            'ip_hash'          => $ipHash,
            'user_agent_hash'  => $userAgentHash,
            'last_activity_at' => $currentTimestamp,
            'expires_at'       => $expiresAt,
            'revoked_at'       => null,
            'created_at'       => $currentTimestamp,
        ]);

        return $sessionUuid;
    }

    public function touchOrCreate(
        int $userIdentifier,
        string $tokenFamilyIdentifier,
        string $deviceLabel,
        string $ipHash,
        string $userAgentHash,
        string $currentTimestamp,
        string $expiresAt,
    ): string {
        $existingSession = $this->sessionModel
            ->where('user_id', $userIdentifier)
            ->where('token_family_id', $tokenFamilyIdentifier)
            ->where('revoked_at', null)
            ->first();

        if (! is_array($existingSession)) {
            return $this->create(
                $userIdentifier,
                $tokenFamilyIdentifier,
                $deviceLabel,
                $ipHash,
                $userAgentHash,
                $currentTimestamp,
                $expiresAt,
            );
        }

        $sessionUuid = (string) $existingSession['session_uuid'];
        $this->sessionModel->update((int) $existingSession['id'], [
            'device_label'     => substr($deviceLabel, 0, 100),
            'ip_hash'          => $ipHash,
            'user_agent_hash'  => $userAgentHash,
            'last_activity_at' => $currentTimestamp,
            'expires_at'       => $expiresAt,
        ]);

        return $sessionUuid;
    }

    public function touchFamily(string $tokenFamilyIdentifier, string $currentTimestamp, string $expiresAt): void
    {
        $this->sessionModel
            ->where('token_family_id', $tokenFamilyIdentifier)
            ->where('revoked_at', null)
            ->set([
                'last_activity_at' => $currentTimestamp,
                'expires_at'       => $expiresAt,
            ])
            ->update();
    }

    public function revokeFamily(string $tokenFamilyIdentifier, string $revokedAt): void
    {
        $this->sessionModel
            ->where('token_family_id', $tokenFamilyIdentifier)
            ->where('revoked_at', null)
            ->set('revoked_at', $revokedAt)
            ->update();
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20),
        );
    }
}
