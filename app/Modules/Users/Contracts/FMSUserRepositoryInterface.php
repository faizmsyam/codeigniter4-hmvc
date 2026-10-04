<?php

namespace App\Modules\Users\Contracts;

interface FMSUserRepositoryInterface
{
    /** @return array{users: list<array<string, mixed>>, total: int, page: int, per_page: int} */
    public function paginate(array $filters, int $page, int $perPage): array;

    /** @return array<string, mixed>|null */
    public function findById(int $userIdentifier, bool $includeDeleted = false): ?array;

    /** @return array<string, mixed>|null */
    public function findByUuid(string $userUuid, bool $includeDeleted = false): ?array;

    /** @return array<string, mixed>|null */
    public function findByIdentity(string $normalizedUsername, string $normalizedEmail, ?int $excludeUserIdentifier = null): ?array;

    /** @param array<string, mixed> $userData */
    public function create(array $userData): int;

    /** @param array<string, mixed> $userData */
    public function update(int $userIdentifier, array $userData): bool;

    public function delete(int $userIdentifier, int $actorIdentifier): bool;

    public function restore(int $userIdentifier, int $actorIdentifier): bool;

    /** @return list<array<string, mixed>> */
    public function sessions(int $userIdentifier): array;

    public function revokeSessions(int $userIdentifier, ?string $sessionUuid = null): int;

    /** @return list<int> */
    public function groupIdentifiers(int $userIdentifier): array;

    /** @param list<int> $groupIdentifiers */
    public function replaceGroups(int $userIdentifier, array $groupIdentifiers, int $actorIdentifier): void;
}
