<?php

namespace App\Modules\ActivityLogs\Services;

final class FMSActivityLogRedactionService
{
    public const REDACTED_PLACEHOLDER = '[REDACTED]';

    public const MAX_EXPORT_ROW_COUNT = 1000;

    public const MAX_EXPORT_RANGE_DAYS = 31;

    /**
     * @var list<string>
     */
    private array $sensitiveKeyFragments = [
        'password',
        'passwd',
        'passcode',
        'access_token',
        'refresh_token',
        'id_token',
        'api_key',
        'apikey',
        'client_secret',
        'secret',
        'otp',
        'one_time',
        'onetime',
        'authorization',
        'cookie',
        'set_cookie',
        'session_token',
        'credit_card',
        'card_number',
        'cvv',
        'cvc',
        'pin_code',
        'private_key',
        'bearer',
    ];

    /**
     * @param array<string, mixed> $payloadData
     * @return array<string, mixed>
     */
    public function redactPayload(array $payloadData): array
    {
        $redactedPayload = [];

        foreach ($payloadData as $payloadKey => $payloadValue) {
            $normalizedKey = strtolower((string) $payloadKey);

            if ($this->isSensitiveKey($normalizedKey)) {
                $redactedPayload[$payloadKey] = self::REDACTED_PLACEHOLDER;
                continue;
            }

            if (is_array($payloadValue)) {
                $redactedPayload[$payloadKey] = $this->redactPayload($payloadValue);
                continue;
            }

            $redactedPayload[$payloadKey] = $payloadValue;
        }

        return $redactedPayload;
    }

    public function isSensitiveKey(string $normalizedKey): bool
    {
        foreach ($this->sensitiveKeyFragments as $sensitiveFragment) {
            if (str_contains($normalizedKey, $sensitiveFragment)) {
                return true;
            }
        }

        return false;
    }

    public function hashNetworkIdentifier(?string $rawIdentifierValue): ?string
    {
        if ($rawIdentifierValue === null || trim($rawIdentifierValue) === '') {
            return null;
        }

        return hash('sha256', trim($rawIdentifierValue));
    }

    /**
     * @param array<string, mixed> $canonicalEntryData
     */
    public function computeEntryHash(array $canonicalEntryData, ?string $previousEntryHash): string
    {
        $hashInput = $this->canonicalize($canonicalEntryData) . '|' . ($previousEntryHash ?? 'GENESIS');

        return hash('sha256', $hashInput);
    }

    /**
     * @param array<int, array<string, mixed>> $orderedActivityLogRows
     */
    public function verifyHashChain(array $orderedActivityLogRows): bool
    {
        $expectedPreviousHash = null;

        foreach ($orderedActivityLogRows as $activityLogRow) {
            $storedPreviousHash = $activityLogRow['previous_hash'] ?? null;
            $storedEntryHash = $activityLogRow['entry_hash'] ?? null;

            if (! is_string($storedEntryHash) || $storedEntryHash === '') {
                return false;
            }

            if ($storedPreviousHash !== $expectedPreviousHash) {
                if (! ($storedPreviousHash === null && $expectedPreviousHash === null)) {
                    return false;
                }
            }

            $canonicalEntryData = $activityLogRow;
            unset(
                $canonicalEntryData['entry_hash'],
                $canonicalEntryData['previous_hash'],
                $canonicalEntryData['id'],
                $canonicalEntryData['ip_address'],
                $canonicalEntryData['user_agent'],
                $canonicalEntryData['device_label'],
            );

            $recomputedEntryHash = $this->computeEntryHash($canonicalEntryData, $storedPreviousHash);

            if (! hash_equals($storedEntryHash, $recomputedEntryHash)) {
                return false;
            }

            $expectedPreviousHash = $storedEntryHash;
        }

        return true;
    }

    public function neutralizeCsvCell(string $cellValue): string
    {
        if ($cellValue === '') {
            return $cellValue;
        }

        $firstCharacter = $cellValue[0];

        if ($firstCharacter === '=' || $firstCharacter === '+' || $firstCharacter === '-' || $firstCharacter === '@' || $firstCharacter === "\t" || $firstCharacter === "\r") {
            return "'" . $cellValue;
        }

        return $cellValue;
    }

    /**
     * @param array<string, mixed> $valueData
     */
    private function canonicalize(array $valueData): string
    {
        $sortedData = $this->sortRecursively($valueData);

        $encodedJson = json_encode($sortedData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($encodedJson) ? $encodedJson : '{}';
    }

    /**
     * @param array<string, mixed> $valueData
     * @return array<string, mixed>
     */
    private function sortRecursively(array $valueData): array
    {
        ksort($valueData, SORT_STRING);

        foreach ($valueData as $payloadKey => $payloadValue) {
            if (is_array($payloadValue)) {
                $valueData[$payloadKey] = $this->sortRecursively($payloadValue);
            }
        }

        return $valueData;
    }
}
