<?php

namespace App\Modules\Authentication\Contracts;

interface FMSRefreshTokenRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findBySelectorForUpdate(string $selector): ?array;

    public function beginTransaction(): void;

    public function commitTransaction(): void;

    public function rollbackTransaction(): void;

    /** @param array<string, mixed> $attributes */
    public function insertToken(array $attributes): int;

    /** @param array<string, mixed> $attributes */
    public function updateToken(int $tokenIdentifier, array $attributes): bool;

    public function revokeFamily(string $tokenFamilyIdentifier, string $revokedAt): void;

    public function revokeUserTokens(int $userIdentifier, string $currentTimestamp): void;

    /** @return array<string, mixed>|null */
    public function findActiveUser(int $userIdentifier): ?array;
}
