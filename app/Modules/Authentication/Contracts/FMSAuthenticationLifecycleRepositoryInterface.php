<?php

namespace App\Modules\Authentication\Contracts;

interface FMSAuthenticationLifecycleRepositoryInterface
{
    /** @return array<string, mixed> */
    public function authenticationSettings(): array;

    /** @return array<string, mixed>|null */
    public function findUserByIdentity(string $normalizedUsername, string $normalizedEmail): ?array;

    /** @return array<string, mixed>|null */
    public function findUserByNormalizedIdentifier(string $normalizedIdentifier): ?array;

    /** @param array<string, mixed> $attributes */
    public function createUser(array $attributes): int;

    /** @return array<string, mixed>|null */
    public function findVerificationTokenForUpdate(string $selector): ?array;

    /** @return array<string, mixed>|null */
    public function findUserById(int $userIdentifier): ?array;

    /** @param array<string, mixed> $attributes */
    public function createVerificationToken(array $attributes): int;

    /** @param array<string, mixed> $attributes */
    public function updateVerificationToken(int $tokenIdentifier, array $attributes): bool;

    public function revokeUnusedVerificationTokens(int $userIdentifier, string $revokedAt): void;

    public function latestVerificationTokenCreatedAt(int $userIdentifier): ?string;

    /** @param array<string, mixed> $attributes */
    public function updateUser(int $userIdentifier, array $attributes): bool;

    public function beginTransaction(): void;

    public function commitTransaction(): void;

    public function rollbackTransaction(): void;
}
