<?php

namespace Tests\Unit\Modules\ActivityLogs;

use App\Modules\ActivityLogs\Contracts\FMSActivityLogRepositoryInterface;
use App\Modules\ActivityLogs\Services\FMSActivityLogRedactionService;
use App\Modules\ActivityLogs\Services\FMSActivityLogService;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

/**
 * In-memory append-only repository used to exercise the audit service without a database.
 */
final class FMSInMemoryActivityLogRepository implements FMSActivityLogRepositoryInterface
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $storedRows = [];

    public bool $appendWasCalled = false;

    /**
     * @param array<string, mixed> $activityLogData
     */
    public function append(array $activityLogData): int
    {
        $this->appendWasCalled = true;

        $storedRow = array_merge(['id' => count($this->storedRows) + 1], $activityLogData);
        $storedRow['id'] = count($this->storedRows) + 1;
        $this->storedRows[] = $storedRow;

        return (int) $storedRow['id'];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function search(array $filters, int $limit, int $offset): array
    {
        $matchingRows = $this->matchingRows($filters);

        return array_slice($matchingRows, $offset, $limit);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countSearch(array $filters): int
    {
        return count($this->matchingRows($filters));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUuid(string $activityLogUuid): ?array
    {
        foreach ($this->storedRows as $storedRow) {
            if (hash_equals((string) ($storedRow['uuid'] ?? ''), $activityLogUuid)) {
                return $storedRow;
            }
        }

        return null;
    }

    public function latestEntryHash(): ?string
    {
        if ($this->storedRows === []) {
            return null;
        }

        $latestStoredRow = end($this->storedRows);

        return isset($latestStoredRow['entry_hash']) ? (string) $latestStoredRow['entry_hash'] : null;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    private function matchingRows(array $filters): array
    {
        $matchingRows = [];

        foreach ($this->storedRows as $storedRow) {
            if (! $this->rowMatchesFilters($storedRow, $filters)) {
                continue;
            }

            $matchingRows[] = $storedRow;
        }

        return array_reverse($matchingRows);
    }

    /**
     * @param array<string, mixed> $storedRow
     * @param array<string, mixed> $filters
     */
    private function rowMatchesFilters(array $storedRow, array $filters): bool
    {
        foreach (['actor_user_id', 'event', 'module', 'entity_type', 'entity_id', 'http_method', 'status_code', 'request_id'] as $filterKey) {
            $filterValue = $filters[$filterKey] ?? null;

            if ($filterValue === null || $filterValue === '') {
                continue;
            }

            if ((string) ($storedRow[$filterKey] ?? '') !== (string) $filterValue) {
                return false;
            }
        }

        if (isset($filters['date_from']) && (string) $storedRow['created_at'] < (string) $filters['date_from']) {
            return false;
        }

        if (isset($filters['date_to']) && (string) $storedRow['created_at'] > (string) $filters['date_to']) {
            return false;
        }

        if (isset($filters['search_keyword']) && $filters['search_keyword'] !== null && $filters['search_keyword'] !== '') {
            $searchKeyword = (string) $filters['search_keyword'];
            $searchHaystack = (string) ($storedRow['event'] ?? '') . ' ' . (string) ($storedRow['description'] ?? '') . ' ' . (string) ($storedRow['entity_id'] ?? '');

            if (stripos($searchHaystack, $searchKeyword) === false) {
                return false;
            }
        }

        return true;
    }
}

final class FMSActivityLogServiceTest extends CIUnitTestCase
{
    private FMSInMemoryActivityLogRepository $inMemoryRepository;

    private FMSActivityLogService $activityLogService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inMemoryRepository = new FMSInMemoryActivityLogRepository();
        $this->activityLogService = new FMSActivityLogService(
            $this->inMemoryRepository,
            new FMSActivityLogRedactionService(),
        );
    }

    public function testRecordedActivityIsAppendedWithRedactedPayloadsAndHashedNetworkIdentifiers(): void
    {
        $recordedActivity = $this->activityLogService->recordActivity([
            'event'        => 'user.password.changed',
            'module'       => 'Authentication',
            'actor_user_id' => 42,
            'entity_type'  => 'user',
            'entity_id'    => 'user-0001',
            'description'  => 'Pengguna mengganti password.',
            'before'       => ['password' => 'old-plaintext'],
            'after'        => ['password' => 'new-plaintext'],
            'metadata'     => ['api_key' => 'raw-api-key', 'ip_label' => 'kantor'],
            'ip_address'   => '203.0.113.7',
            'user_agent'   => 'UnitTestAgent/1.0',
            'created_at'   => '2026-09-29 01:00:00',
        ]);

        $this->assertSame(1, $recordedActivity['id']);
        $this->assertSame(hash('sha256', '203.0.113.7'), $recordedActivity['ip_hash']);
        $this->assertSame(hash('sha256', 'UnitTestAgent/1.0'), $recordedActivity['user_agent_hash']);
        $this->assertStringNotContainsString('old-plaintext', (string) $recordedActivity['before_json']);
        $this->assertStringNotContainsString('new-plaintext', (string) $recordedActivity['after_json']);
        $this->assertStringNotContainsString('raw-api-key', (string) $recordedActivity['metadata_json']);
        $this->assertStringContainsString(FMSActivityLogRedactionService::REDACTED_PLACEHOLDER, (string) $recordedActivity['before_json']);

        $presentedActivity = $this->activityLogService->findActivity((string) $recordedActivity['uuid']);
        $this->assertNotNull($presentedActivity);
        $this->assertSame(FMSActivityLogRedactionService::REDACTED_PLACEHOLDER, $presentedActivity['before']['password']);
        $this->assertSame('kantor', $presentedActivity['metadata']['ip_label']);
    }

    public function testSequentialEntriesFormAContinuousHashChain(): void
    {
        $firstEntry = $this->activityLogService->recordActivity([
            'event'      => 'role.assigned',
            'module'     => 'Privileges',
            'created_at' => '2026-09-29 01:00:00',
        ]);
        $secondEntry = $this->activityLogService->recordActivity([
            'event'      => 'role.revoked',
            'module'     => 'Privileges',
            'created_at' => '2026-09-29 01:05:00',
        ]);

        $this->assertNull($firstEntry['previous_hash']);
        $this->assertNotNull($firstEntry['entry_hash']);
        $this->assertSame($firstEntry['entry_hash'], $secondEntry['previous_hash']);

        $chronologicalRows = $this->inMemoryRepository->storedRows;
        $this->assertTrue((new FMSActivityLogRedactionService())->verifyHashChain($chronologicalRows));
    }

    public function testSearchReturnsPaginatedTotalCountAndNewestRowsFirst(): void
    {
        foreach (['a', 'b', 'c', 'd', 'e'] as $activitySequence) {
            $this->activityLogService->recordActivity([
                'event'      => 'module.entry.' . $activitySequence,
                'module'     => 'Dashboard',
                'created_at' => '2026-09-29 02:0' . $activitySequence . ':00',
            ]);
        }

        $searchResult = $this->activityLogService->searchActivity(['module' => 'Dashboard'], 2, 2);

        $this->assertSame(5, $searchResult['total']);
        $this->assertSame(2, $searchResult['page']);
        $this->assertSame(2, $searchResult['per_page']);
        $this->assertSame(3, $searchResult['total_pages']);
        $this->assertCount(2, $searchResult['items']);
        $this->assertSame('module.entry.c', $searchResult['items'][0]['event']);
        $this->assertSame('module.entry.b', $searchResult['items'][1]['event']);
    }

    public function testIdentifierRuleUsesRegisteredUuidValidation(): void
    {
        $rules = (new \App\Modules\ActivityLogs\Validation\FMSActivityLogValidation())->getIdentifierRules();
        $validation = \Config\Services::validation();
        $validation->setRules($rules);

        $this->assertTrue($validation->run(['uuid' => '11111111-1111-4111-8111-111111111111']));
        $this->assertFalse($validation->run(['uuid' => 'bukan-uuid']));
        $this->assertStringNotContainsString('valid_uuid', $rules['uuid']['rules']);
    }

    public function testMissingActivityDetailReturnsNull(): void
    {
        $this->assertNull($this->activityLogService->findActivity('11111111-1111-4111-8111-111111111111'));
    }

    public function testExportRequiresBoundedDateRangeAndNeutralizesFormulas(): void
    {
        $this->activityLogService->recordActivity([
            'event'      => '=cmd|calc!A0',
            'module'     => 'Dashboard',
            'created_at' => '2026-09-29 03:00:00',
        ]);

        $csvContents = $this->activityLogService->exportActivityAsCsv([
            'date_from' => '2026-09-29 00:00:00',
            'date_to'   => '2026-09-30 00:00:00',
        ]);

        $this->assertStringContainsString('uuid,created_at,event', $csvContents);
        $this->assertStringContainsString("'=cmd|calc!A0", $csvContents);
    }

    public function testExportWithoutDateRangeIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Rentang tanggal ekspor wajib diisi.');

        $this->activityLogService->exportActivityAsCsv(['module' => 'Dashboard']);
    }

    public function testExportRangeBeyondMaximumIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Rentang tanggal ekspor melebihi batas.');

        $this->activityLogService->exportActivityAsCsv([
            'date_from' => '2026-01-01 00:00:00',
            'date_to'   => '2026-09-30 00:00:00',
        ]);
    }

    public function testRecordedActivityRejectsEmptyRequiredFields(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kolom event wajib diisi.');

        $this->activityLogService->recordActivity(['module' => 'Dashboard']);
    }

    public function testRepositoryExposesNoUpdateOrDeleteBehaviour(): void
    {
        $repositoryReflection = new \ReflectionClass(FMSActivityLogRepositoryInterface::class);
        $repositoryMethodNames = array_map(
            static fn (\ReflectionMethod $repositoryMethod): string => strtolower($repositoryMethod->getName()),
            $repositoryReflection->getMethods(),
        );

        foreach ($repositoryMethodNames as $repositoryMethodName) {
            $this->assertStringNotContainsString('update', $repositoryMethodName);
            $this->assertStringNotContainsString('delete', $repositoryMethodName);
        }
    }
}
