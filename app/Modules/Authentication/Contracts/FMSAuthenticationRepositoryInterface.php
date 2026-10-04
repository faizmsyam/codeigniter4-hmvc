<?php

namespace App\Modules\Authentication\Contracts;

interface FMSAuthenticationRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findUserByNormalizedIdentifier(string $normalizedIdentifier): ?array;

    /** @param array<string, mixed> $attributes */
    public function updateUser(int $userIdentifier, array $attributes): bool;

    public function recordAttempt(string $identifierHash, string $ipHash, string $resultCode, string $occurredAt): void;

    public function countRecentFailures(string $identifierHash, string $ipHash, string $since): int;

    public function isSuperAdministrator(int $userIdentifier): bool;
}
