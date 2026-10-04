<?php

namespace App\Modules\Authentication\Contracts;

interface FMSApiKeyRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): int;

    /** @return array<string, mixed>|null */
    public function findByKeyIdentifier(string $keyIdentifier): ?array;

    /** @param array<string, mixed> $attributes */
    public function update(int $apiKeyIdentifier, array $attributes): bool;
}
