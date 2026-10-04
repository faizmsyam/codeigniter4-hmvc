<?php

namespace App\Modules\Authentication\Contracts;

interface FMSBasicAuthClientRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findByNormalizedUsername(string $normalizedUsername): ?array;

    /** @param array<string, mixed> $attributes */
    public function update(int $clientIdentifier, array $attributes): bool;
}
