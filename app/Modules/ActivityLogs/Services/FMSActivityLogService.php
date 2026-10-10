<?php

namespace App\Modules\ActivityLogs\Services;

use App\Modules\ActivityLogs\Contracts\FMSActivityLogRepositoryInterface;
use RuntimeException;

final class FMSActivityLogService
{
    public const DEFAULT_PAGE_SIZE = 25;

    public const MAXIMUM_PAGE_SIZE = 100;

    public function __construct(
        private readonly FMSActivityLogRepositoryInterface $activityLogRepository,
        private readonly FMSActivityLogRedactionService $redactionService = new FMSActivityLogRedactionService(),
    ) {
    }

    /**
     * Appends one immutable audit entry.
     *
     * The service never exposes update or delete behaviour: audit rows are
     * append-only by construction, and every stored payload is redacted before
     * it leaves the request boundary.
     *
     * @param array<string, mixed> $activityLogEntryData
     * @return array<string, mixed>
     */
    public function recordActivity(array $activityLogEntryData): array
    {
        $actorUserIdentifier = $this->normalizeOptionalInteger($activityLogEntryData['actor_user_id'] ?? null);
        $activityLogUuid = $this->normalizeNullableString($activityLogEntryData['uuid'] ?? null) ?? $this->generateUuidV4();
        $eventName = $this->normalizeRequiredString($activityLogEntryData['event'] ?? null, 'event');
        $moduleName = $this->normalizeRequiredString($activityLogEntryData['module'] ?? null, 'module');
        $entityTypeName = $this->normalizeNullableString($activityLogEntryData['entity_type'] ?? null);
        $entityIdentifier = $this->normalizeNullableString($activityLogEntryData['entity_id'] ?? null);
        $descriptionText = $this->normalizeNullableString($activityLogEntryData['description'] ?? null);
        $requestIdentifier = $this->normalizeNullableString($activityLogEntryData['request_id'] ?? null);
        $httpMethodName = $this->normalizeNullableString($activityLogEntryData['http_method'] ?? null);
        $routeName = $this->normalizeNullableString($activityLogEntryData['route_name'] ?? null);
        $statusCodeValue = $this->normalizeOptionalInteger($activityLogEntryData['status_code'] ?? null);
        $rawIpAddress = $this->normalizeNullableString($activityLogEntryData['ip_address'] ?? null);
        $rawUserAgent = $this->normalizeNullableString($activityLogEntryData['user_agent'] ?? null);
        $rawDeviceLabel = $this->normalizeNullableString($activityLogEntryData['device_label'] ?? null);

        if (($rawIpAddress === null || $rawUserAgent === null) && function_exists('service')) {
            try {
                $incomingRequest = service('request', null, false);

                if ($rawIpAddress === null && $incomingRequest !== null && method_exists($incomingRequest, 'getIPAddress')) {
                    $rawIpAddress = $this->normalizeNullableString($incomingRequest->getIPAddress());
                }

                if ($rawUserAgent === null && $incomingRequest !== null && method_exists($incomingRequest, 'getUserAgent')) {
                    $rawUserAgent = $this->normalizeNullableString($incomingRequest->getUserAgent()->getAgentString());
                }
            } catch (\Throwable $networkContextException) {
                $rawIpAddress = $rawIpAddress ?? $this->normalizeNullableString($activityLogEntryData['ip_address'] ?? null);
            }
        }

        if ($rawDeviceLabel === null && $rawUserAgent !== null && $rawUserAgent !== '') {
            $rawDeviceLabel = $this->buildDeviceLabel($rawUserAgent);
        }

        $beforePayload = $this->redactionService->redactPayload($this->normalizePayloadMap($activityLogEntryData['before'] ?? []));
        $afterPayload = $this->redactionService->redactPayload($this->normalizePayloadMap($activityLogEntryData['after'] ?? []));
        $metadataPayload = $this->redactionService->redactPayload($this->normalizePayloadMap($activityLogEntryData['metadata'] ?? []));

        $createdAtTimestamp = $this->normalizeNullableString($activityLogEntryData['created_at'] ?? null) ?? gmdate('Y-m-d H:i:s');
        $previousEntryHash = $this->activityLogRepository->latestEntryHash();

        $canonicalEntryData = [
            'uuid'           => $activityLogUuid,
            'request_id'     => $requestIdentifier,
            'actor_user_id'  => $actorUserIdentifier,
            'event'          => $eventName,
            'module'         => $moduleName,
            'entity_type'    => $entityTypeName,
            'entity_id'      => $entityIdentifier,
            'description'    => $descriptionText,
            'before_json'    => $this->encodePayload($beforePayload),
            'after_json'     => $this->encodePayload($afterPayload),
            'metadata_json'  => $this->encodePayload($metadataPayload),
            'ip_address'     => $rawIpAddress,
            'user_agent'     => $rawUserAgent,
            'device_label'   => $rawDeviceLabel,
            'ip_hash'        => $this->redactionService->hashNetworkIdentifier($rawIpAddress),
            'user_agent_hash' => $this->redactionService->hashNetworkIdentifier($rawUserAgent),
            'http_method'    => $httpMethodName,
            'route_name'     => $routeName,
            'status_code'    => $statusCodeValue,
            'created_at'     => $createdAtTimestamp,
        ];

        $hashableEntryData = $canonicalEntryData;
        unset($hashableEntryData['ip_address'], $hashableEntryData['user_agent'], $hashableEntryData['device_label']);

        $entryHash = $this->redactionService->computeEntryHash($hashableEntryData, $previousEntryHash);

        $storedActivityLogIdentifier = $this->activityLogRepository->append(array_merge(
            $canonicalEntryData,
            [
                'previous_hash' => $previousEntryHash,
                'entry_hash'    => $entryHash,
            ],
        ));

        return array_merge(
            ['id' => $storedActivityLogIdentifier],
            $canonicalEntryData,
            ['previous_hash' => $previousEntryHash, 'entry_hash' => $entryHash],
        );
    }

    /**
     * @param array<string, mixed> $searchFilters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
     */
    public function searchActivity(array $searchFilters, int $pageNumber = 1, int $pageSize = self::DEFAULT_PAGE_SIZE): array
    {
        $normalizedPageNumber = max(1, $pageNumber);
        $normalizedPageSize = min(self::MAXIMUM_PAGE_SIZE, max(1, $pageSize));

        $normalizedFilters = $this->normalizeSearchFilters($searchFilters);
        $totalRowCount = $this->activityLogRepository->countSearch($normalizedFilters);
        $rowOffset = ($normalizedPageNumber - 1) * $normalizedPageSize;

        $activityLogRows = $this->activityLogRepository->search($normalizedFilters, $normalizedPageSize, $rowOffset);

        return [
            'items'       => array_map(fn (array $activityLogRow): array => $this->presentActivityLog($activityLogRow), $activityLogRows),
            'total'       => $totalRowCount,
            'page'        => $normalizedPageNumber,
            'per_page'    => $normalizedPageSize,
            'total_pages' => $totalRowCount === 0 ? 0 : (int) ceil($totalRowCount / $normalizedPageSize),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActivity(string $activityLogUuid): ?array
    {
        $normalizedUuid = trim($activityLogUuid);
        if ($normalizedUuid === '') {
            return null;
        }

        $activityLogRow = $this->activityLogRepository->findByUuid($normalizedUuid);

        if ($activityLogRow === null) {
            return null;
        }

        return $this->presentActivityLog($activityLogRow);
    }

    /**
     * Builds a CSV export as an in-memory string.
     *
     * The export is bounded by a maximum row count and a maximum date range so
     * that a single request cannot exhaust memory or the database connection.
     *
     * @param array<string, mixed> $searchFilters
     */
    public function exportActivityAsCsv(array $searchFilters): string
    {
        $normalizedFilters = $this->normalizeSearchFilters($searchFilters);

        if ($normalizedFilters['date_from'] === null || $normalizedFilters['date_to'] === null) {
            throw new RuntimeException('Rentang tanggal ekspor wajib diisi.');
        }

        $rangeStartTimestamp = strtotime($normalizedFilters['date_from']);
        $rangeEndTimestamp = strtotime($normalizedFilters['date_to']);

        if ($rangeStartTimestamp === false || $rangeEndTimestamp === false || $rangeEndTimestamp < $rangeStartTimestamp) {
            throw new RuntimeException('Rentang tanggal ekspor tidak valid.');
        }

        $rangeLengthInDays = (int) ceil(($rangeEndTimestamp - $rangeStartTimestamp) / 86400);

        if ($rangeLengthInDays > FMSActivityLogRedactionService::MAX_EXPORT_RANGE_DAYS) {
            throw new RuntimeException('Rentang tanggal ekspor melebihi batas.');
        }

        $activityLogRows = $this->activityLogRepository->search(
            $normalizedFilters,
            FMSActivityLogRedactionService::MAX_EXPORT_ROW_COUNT,
            0,
        );

        $csvOutputStream = fopen('php://temp', 'r+');

        if ($csvOutputStream === false) {
            throw new RuntimeException('Ekspor CSV tidak dapat dimulai.');
        }

        $columnHeaders = [
            'uuid',
            'created_at',
            'event',
            'module',
            'entity_type',
            'entity_id',
            'actor_user_id',
            'actor_name',
            'actor_username',
            'description',
            'ip_address',
            'device_label',
            'http_method',
            'route_name',
            'status_code',
            'request_id',
        ];

        fputcsv($csvOutputStream, $columnHeaders);

        foreach ($activityLogRows as $activityLogRow) {
            $exportableRow = $this->presentActivityLog($activityLogRow);

            $csvRowValues = [];
            foreach ($columnHeaders as $columnHeader) {
                $csvRowValues[] = $this->redactionService->neutralizeCsvCell((string) ($exportableRow[$columnHeader] ?? ''));
            }

            fputcsv($csvOutputStream, $csvRowValues);
        }

        rewind($csvOutputStream);
        $csvContents = stream_get_contents($csvOutputStream);
        fclose($csvOutputStream);

        return is_string($csvContents) ? $csvContents : '';
    }

    /**
     * @param array<string, mixed> $activityLogRow
     * @return array<string, mixed>
     */
    private function presentActivityLog(array $activityLogRow): array
    {
        $presentedActivityLog = [
            'uuid'            => $activityLogRow['uuid'] ?? null,
            'request_id'      => $activityLogRow['request_id'] ?? null,
            'actor_user_id'   => isset($activityLogRow['actor_user_id']) && $activityLogRow['actor_user_id'] !== null ? (int) $activityLogRow['actor_user_id'] : null,
            'actor_name'      => $activityLogRow['actor_name'] ?? null,
            'actor_username'  => $activityLogRow['actor_username'] ?? null,
            'event'           => (string) ($activityLogRow['event'] ?? ''),
            'module'          => (string) ($activityLogRow['module'] ?? ''),
            'entity_type'     => $activityLogRow['entity_type'] ?? null,
            'entity_id'       => $activityLogRow['entity_id'] ?? null,
            'description'     => $activityLogRow['description'] ?? null,
            'has_changes'     => isset($activityLogRow['has_changes'])
                ? (bool) $activityLogRow['has_changes']
                : $this->decodePayload($activityLogRow['before_json'] ?? null) !== []
                    || $this->decodePayload($activityLogRow['after_json'] ?? null) !== [],
            'before'          => $this->decodePayload($activityLogRow['before_json'] ?? null),
            'after'           => $this->decodePayload($activityLogRow['after_json'] ?? null),
            'metadata'        => $this->decodePayload($activityLogRow['metadata_json'] ?? null),
            'ip_address'      => $activityLogRow['ip_address'] ?? null,
            'user_agent'      => $activityLogRow['user_agent'] ?? null,
            'device_label'    => $activityLogRow['device_label'] ?? null,
            'ip_hash'         => $activityLogRow['ip_hash'] ?? null,
            'user_agent_hash' => $activityLogRow['user_agent_hash'] ?? null,
            'http_method'     => $activityLogRow['http_method'] ?? null,
            'route_name'      => $activityLogRow['route_name'] ?? null,
            'status_code'     => isset($activityLogRow['status_code']) && $activityLogRow['status_code'] !== null ? (int) $activityLogRow['status_code'] : null,
            'previous_hash'   => $activityLogRow['previous_hash'] ?? null,
            'entry_hash'      => $activityLogRow['entry_hash'] ?? null,
            'created_at'      => $activityLogRow['created_at'] ?? null,
        ];

        return $this->redactionService->redactPayload($presentedActivityLog);
    }

    /**
     * @param array<string, mixed> $searchFilters
     * @return array<string, mixed>
     */
    private function normalizeSearchFilters(array $searchFilters): array
    {
        return [
            'actor_user_id'  => $this->normalizeOptionalInteger($searchFilters['actor_user_id'] ?? null),
            'event'          => $this->normalizeNullableString($searchFilters['event'] ?? null),
            'module'         => $this->normalizeNullableString($searchFilters['module'] ?? null),
            'entity_type'    => $this->normalizeNullableString($searchFilters['entity_type'] ?? null),
            'entity_id'      => $this->normalizeNullableString($searchFilters['entity_id'] ?? null),
            'http_method'    => $this->normalizeNullableString($searchFilters['http_method'] ?? null),
            'status_code'    => $this->normalizeOptionalInteger($searchFilters['status_code'] ?? null),
            'request_id'     => $this->normalizeNullableString($searchFilters['request_id'] ?? null),
            'date_from'      => $this->normalizeNullableString($searchFilters['date_from'] ?? null),
            'date_to'        => $this->normalizeNullableString($searchFilters['date_to'] ?? null),
            'search_keyword' => $this->normalizeNullableString($searchFilters['search_keyword'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed> $payloadData
     * @return array<string, mixed>
     */
    private function normalizePayloadMap(mixed $payloadData): array
    {
        return is_array($payloadData) ? $payloadData : [];
    }

    /**
     * @param array<string, mixed> $payloadData
     */
    private function encodePayload(array $payloadData): string
    {
        if ($payloadData === []) {
            return '{}';
        }

        $encodedPayload = json_encode($payloadData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($encodedPayload) ? $encodedPayload : '{}';
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(mixed $encodedPayload): array
    {
        if (! is_string($encodedPayload) || $encodedPayload === '') {
            return [];
        }

        $decodedPayload = json_decode($encodedPayload, true);

        return is_array($decodedPayload) ? $decodedPayload : [];
    }

    /**
     * Membuat label perangkat/browser yang ringkas dari raw user agent.
     */
    private function buildDeviceLabel(string $rawUserAgent): string
    {
        $lowerUserAgent = strtolower($rawUserAgent);

        $browserName = 'Browser tidak dikenal';
        $browserVersion = '';

        if (preg_match('/edg(?:e|a|ios)?\/([0-9.]+)/', $lowerUserAgent, $browserMatch) === 1) {
            $browserName = 'Edge';
            $browserVersion = $browserMatch[1];
        } elseif (preg_match('/opr\/([0-9.]+)/', $lowerUserAgent, $browserMatch) === 1) {
            $browserName = 'Opera';
            $browserVersion = $browserMatch[1];
        } elseif (preg_match('/firefox\/([0-9.]+)/', $lowerUserAgent, $browserMatch) === 1) {
            $browserName = 'Firefox';
            $browserVersion = $browserMatch[1];
        } elseif (preg_match('/chrome\/([0-9.]+)/', $lowerUserAgent, $browserMatch) === 1) {
            $browserName = 'Chrome';
            $browserVersion = $browserMatch[1];
        } elseif (preg_match('/version\/([0-9.]+).*safari/', $lowerUserAgent, $browserMatch) === 1) {
            $browserName = 'Safari';
            $browserVersion = $browserMatch[1];
        } elseif (preg_match('/(bot|crawl|spider|slurp|mediapartners)/', $lowerUserAgent) === 1) {
            $browserName = 'Bot/Crawler';
        }

        $platformName = 'Perangkat tidak dikenal';
        if (str_contains($lowerUserAgent, 'android')) {
            $platformName = 'Android';
        } elseif (str_contains($lowerUserAgent, 'iphone') || str_contains($lowerUserAgent, 'ipad')) {
            $platformName = 'iOS';
        } elseif (str_contains($lowerUserAgent, 'windows')) {
            $platformName = 'Windows';
        } elseif (str_contains($lowerUserAgent, 'macintosh') || str_contains($lowerUserAgent, 'mac os')) {
            $platformName = 'macOS';
        } elseif (str_contains($lowerUserAgent, 'linux')) {
            $platformName = 'Linux';
        }

        $deviceName = $platformName;
        if (str_contains($lowerUserAgent, 'mobile') || str_contains($lowerUserAgent, 'iphone')) {
            $deviceName .= ' Mobile';
        }

        $browserLabel = $browserVersion !== '' ? $browserName . ' ' . $browserVersion : $browserName;

        return trim($deviceName . ' · ' . $browserLabel);
    }

    private function normalizeRequiredString(mixed $rawValue, string $fieldName): string
    {
        $normalizedValue = $this->normalizeNullableString($rawValue);

        if ($normalizedValue === null) {
            throw new RuntimeException('Kolom ' . $fieldName . ' wajib diisi.');
        }

        return $normalizedValue;
    }

    private function normalizeNullableString(mixed $rawValue): ?string
    {
        if (! is_string($rawValue)) {
            return null;
        }

        $trimmedValue = trim($rawValue);

        return $trimmedValue === '' ? null : $trimmedValue;
    }

    private function normalizeOptionalInteger(mixed $rawValue): ?int
    {
        if ($rawValue === null || $rawValue === '') {
            return null;
        }

        if (is_int($rawValue)) {
            return $rawValue;
        }

        if (is_string($rawValue) && ctype_digit($rawValue)) {
            return (int) $rawValue;
        }

        if (is_numeric($rawValue)) {
            return (int) $rawValue;
        }

        return null;
    }

    private function generateUuidV4(): string
    {
        $randomBytes = random_bytes(16);
        $randomBytes[6] = chr((ord($randomBytes[6]) & 0x0f) | 0x40);
        $randomBytes[8] = chr((ord($randomBytes[8]) & 0x3f) | 0x80);
        $hexadecimalUuid = bin2hex($randomBytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hexadecimalUuid, 0, 8),
            substr($hexadecimalUuid, 8, 4),
            substr($hexadecimalUuid, 12, 4),
            substr($hexadecimalUuid, 16, 4),
            substr($hexadecimalUuid, 20, 12),
        );
    }
}
