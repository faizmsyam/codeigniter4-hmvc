<?php

namespace App\Modules\Authentication\Repositories;

use App\Modules\Authentication\Contracts\FMSRefreshTokenRepositoryInterface;
use App\Modules\Authentication\Models\FMSRefreshTokenModel;
use App\Modules\Authentication\Models\FMSUserModel;

final class FMSDatabaseRefreshTokenRepository implements FMSRefreshTokenRepositoryInterface
{
    public function __construct(
        private readonly FMSRefreshTokenModel $refreshTokenModel = new FMSRefreshTokenModel(),
        private readonly FMSUserModel $userModel = new FMSUserModel(),
    ) {
    }

    public function findBySelectorForUpdate(string $selector): ?array
    {
        $tokenRow = $this->refreshTokenModel
            ->where('selector', $selector)
            ->first();

        return is_array($tokenRow) ? $tokenRow : null;
    }

    public function beginTransaction(): void
    {
        $this->refreshTokenModel->db->transBegin();
    }

    public function commitTransaction(): void
    {
        $this->refreshTokenModel->db->transCommit();
    }

    public function rollbackTransaction(): void
    {
        $this->refreshTokenModel->db->transRollback();
    }

    public function insertToken(array $attributes): int
    {
        return (int) $this->refreshTokenModel->insert($attributes, true);
    }

    public function updateToken(int $tokenIdentifier, array $attributes): bool
    {
        return (bool) $this->refreshTokenModel->update($tokenIdentifier, $attributes);
    }

    public function revokeFamily(string $tokenFamilyIdentifier, string $revokedAt): void
    {
        $this->refreshTokenModel
            ->where('token_family_id', $tokenFamilyIdentifier)
            ->where('revoked_at', null)
            ->set('revoked_at', $revokedAt)
            ->update();
    }

    public function revokeUserTokens(int $userIdentifier, string $currentTimestamp): void
    {
        $this->refreshTokenModel
            ->where('user_id', $userIdentifier)
            ->where('revoked_at', null)
            ->set('revoked_at', $currentTimestamp)
            ->update();
    }

    public function findActiveUser(int $userIdentifier): ?array
    {
        $userRow = $this->userModel->find($userIdentifier);

        if (!is_array($userRow)) {
            return null;
        }

        if ((string) ($userRow['status'] ?? '') !== 'active') {
            return null;
        }

        if (!empty($userRow['deleted_at'])) {
            return null;
        }

        return $userRow;
    }
}
