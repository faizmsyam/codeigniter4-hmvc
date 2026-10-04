<?php

namespace App\Modules\ActivityLogs\Contracts;

interface FMSActivityLogRepositoryInterface
{
    /**
     * @param array<string, mixed> $activityLogData
     */
    public function append(array $activityLogData): int;

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function search(array $filters, int $limit, int $offset): array;

    /**
     * @param array<string, mixed> $filters
     */
    public function countSearch(array $filters): int;

    /**
     * @return array<string, mixed>|null
     */
    public function findByUuid(string $activityLogUuid): ?array;

    public function latestEntryHash(): ?string;
}
