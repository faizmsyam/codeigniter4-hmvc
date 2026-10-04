<?php

namespace App\Modules\Privileges\Services;

use App\Modules\Privileges\Validation\FMSPrivilegesValidation;
use InvalidArgumentException;

/**
 * Pure domain logic for the Privileges (RBAC) domain.
 *
 * This service never touches the database: it normalizes identifiers,
 * prepares allowlisted storage rows (mass-assignment guard), validates
 * expirations, and refuses privilege escalation before any persistence call.
 * The database repository owns transactions, guards, and auditing.
 */
final class FMSPrivilegesManagementService
{
    /**
     * @param array<int, mixed> $identifierValues
     * @return array<int, int>
     */
    public function normalizeIdentifierList(array $identifierValues, string $identifierLabel): array
    {
        $normalizedIdentifiers = [];
        foreach (array_values($identifierValues) as $identifierValue) {
            if (is_int($identifierValue)) {
                $normalizedIdentifier = $identifierValue;
            } elseif (is_string($identifierValue) && ctype_digit(trim($identifierValue))) {
                $normalizedIdentifier = (int) trim($identifierValue);
            } else {
                throw new InvalidArgumentException($identifierLabel . ' identifiers must be positive integers.');
            }

            if ($normalizedIdentifier <= 0) {
                throw new InvalidArgumentException($identifierLabel . ' identifiers must be positive integers.');
            }

            $normalizedIdentifiers[] = $normalizedIdentifier;
        }

        if ($normalizedIdentifiers === []) {
            throw new InvalidArgumentException($identifierLabel . ' identifiers must not be empty.');
        }

        if (count(array_unique($normalizedIdentifiers)) !== count($normalizedIdentifiers)) {
            throw new InvalidArgumentException($identifierLabel . ' identifiers must not contain duplicate identifiers.');
        }

        return array_values($normalizedIdentifiers);
    }

    /**
     * @param array<string, mixed> $groupPayload
     * @return array<string, mixed>
     */
    public function prepareGroupData(array $groupPayload, int $actorIdentifier, bool $isCreate): array
    {
        $this->requirePositiveActor($actorIdentifier);

        $validationErrors = $isCreate
            ? FMSPrivilegesValidation::validateGroupCreate($groupPayload)
            : FMSPrivilegesValidation::validateGroupUpdate($groupPayload);

        if ($validationErrors !== []) {
            throw new InvalidArgumentException('Group payload is invalid: ' . $this->firstValidationMessage($validationErrors));
        }

        if ($isCreate) {
            $preparedGroupData = [
                'uuid'        => $this->newUuid(),
                'code'        => mb_strtolower(trim((string) ($groupPayload['code'] ?? '')), 'UTF-8'),
                'name'        => trim((string) ($groupPayload['name'] ?? '')),
                'description' => $this->nullableText($groupPayload['description'] ?? null, 255),
                'is_active'   => $this->normalizeActiveFlag($groupPayload['is_active'] ?? 1),
                'created_by'  => $actorIdentifier,
                'updated_by'  => $actorIdentifier,
            ];

            return $preparedGroupData;
        }

        $preparedGroupData = ['updated_by' => $actorIdentifier];
        if (array_key_exists('code', $groupPayload)) {
            $preparedGroupData['code'] = mb_strtolower(trim((string) $groupPayload['code']), 'UTF-8');
        }

        if (array_key_exists('name', $groupPayload)) {
            $preparedGroupData['name'] = trim((string) $groupPayload['name']);
        }

        if (array_key_exists('description', $groupPayload)) {
            $preparedGroupData['description'] = $this->nullableText($groupPayload['description'], 255);
        }

        if (array_key_exists('is_active', $groupPayload)) {
            $preparedGroupData['is_active'] = $this->normalizeActiveFlag($groupPayload['is_active']);
        }

        return $preparedGroupData;
    }

    /**
     * @param array<string, mixed> $permissionPayload
     * @return array<string, mixed>
     */
    public function preparePermissionData(array $permissionPayload, int $actorIdentifier, bool $isCreate): array
    {
        $this->requirePositiveActor($actorIdentifier);

        $validationErrors = $isCreate
            ? FMSPrivilegesValidation::validatePermissionCreate($permissionPayload)
            : FMSPrivilegesValidation::validatePermissionUpdate($permissionPayload);

        if ($validationErrors !== []) {
            throw new InvalidArgumentException('Permission payload is invalid: ' . $this->firstValidationMessage($validationErrors));
        }

        if ($isCreate) {
            return [
                'uuid'           => $this->newUuid(),
                'permission_key' => mb_strtolower(trim((string) ($permissionPayload['permission_key'] ?? '')), 'UTF-8'),
                'module_name'    => mb_strtolower(trim((string) ($permissionPayload['module_name'] ?? '')), 'UTF-8'),
                'action_name'    => mb_strtolower(trim((string) ($permissionPayload['action_name'] ?? '')), 'UTF-8'),
                'description'    => $this->nullableText($permissionPayload['description'] ?? null, 255),
                'is_active'      => $this->normalizeActiveFlag($permissionPayload['is_active'] ?? 1),
                'created_by'     => $actorIdentifier,
                'updated_by'     => $actorIdentifier,
            ];
        }

        $preparedPermissionData = ['updated_by' => $actorIdentifier];
        foreach (['permission_key', 'module_name', 'action_name'] as $lowercasedFieldName) {
            if (array_key_exists($lowercasedFieldName, $permissionPayload)) {
                $preparedPermissionData[$lowercasedFieldName] = mb_strtolower(trim((string) $permissionPayload[$lowercasedFieldName]), 'UTF-8');
            }
        }

        if (array_key_exists('description', $permissionPayload)) {
            $preparedPermissionData['description'] = $this->nullableText($permissionPayload['description'], 255);
        }

        if (array_key_exists('is_active', $permissionPayload)) {
            $preparedPermissionData['is_active'] = $this->normalizeActiveFlag($permissionPayload['is_active']);
        }

        return $preparedPermissionData;
    }

    public function normalizeExpirationTimestamp(mixed $expirationTimestamp): ?string
    {
        if ($expirationTimestamp === null || trim((string) $expirationTimestamp) === '') {
            return null;
        }

        FMSPrivilegesValidation::assertFutureTimestamp((string) $expirationTimestamp);

        $parsedTimestamp = strtotime((string) $expirationTimestamp);
        if ($parsedTimestamp === false) {
            throw new InvalidArgumentException('Expiration must be a valid datetime.');
        }

        return gmdate('Y-m-d H:i:s', $parsedTimestamp);
    }

    /**
     * Refuse to grant a permission the acting subject does not own.
     *
     * Only `allow` entries confer power, so only they are checked; `deny`
     * entries can never escalate anyone. The wildcard `*` always requires a
     * super administrator actor, even when it appears with `deny` effect.
     *
     * @param array<int, array<string, mixed>> $grantedPermissionEntries
     * @param array<int, string> $actorPermissionKeys
     */
    public function assertNoPrivilegeEscalation(
        array $grantedPermissionEntries,
        array $actorPermissionKeys,
        bool $actorIsSuperAdmin,
    ): void {
        if ($actorIsSuperAdmin) {
            return;
        }

        $normalizedActorPermissions = [];
        foreach ($actorPermissionKeys as $actorPermissionKey) {
            if (! is_string($actorPermissionKey)) {
                continue;
            }

            $normalizedActorPermission = mb_strtolower(trim($actorPermissionKey), 'UTF-8');
            if ($normalizedActorPermission !== '') {
                $normalizedActorPermissions[$normalizedActorPermission] = true;
            }
        }

        foreach ($grantedPermissionEntries as $grantedPermissionEntry) {
            if (! is_array($grantedPermissionEntry)) {
                continue;
            }

            $grantedPermissionKey = mb_strtolower(trim((string) ($grantedPermissionEntry['permission_key'] ?? '')), 'UTF-8');
            if ($grantedPermissionKey === '') {
                continue;
            }

            $grantedEffect = mb_strtolower(trim((string) ($grantedPermissionEntry['effect'] ?? 'allow')), 'UTF-8');
            if ($grantedEffect !== 'allow' && $grantedPermissionKey !== '*') {
                continue;
            }

            if ($grantedPermissionKey === '*') {
                throw new InvalidArgumentException('Privilege escalation was refused: wildcard permission requires a super administrator actor.');
            }

            if (! isset($normalizedActorPermissions[$grantedPermissionKey])) {
                throw new InvalidArgumentException('Privilege escalation was refused: actor does not own permission ' . $grantedPermissionKey . '.');
            }
        }
    }

    private function requirePositiveActor(int $actorIdentifier): void
    {
        if ($actorIdentifier <= 0) {
            throw new InvalidArgumentException('Actor identifier must be a positive integer.');
        }
    }

    private function normalizeActiveFlag(mixed $activeFlag): int
    {
        if (is_bool($activeFlag)) {
            return $activeFlag ? 1 : 0;
        }

        if (is_int($activeFlag) || (is_string($activeFlag) && ctype_digit(trim($activeFlag)))) {
            return ((int) $activeFlag) === 1 ? 1 : 0;
        }

        if (is_string($activeFlag)) {
            $normalizedFlag = mb_strtolower(trim($activeFlag), 'UTF-8');
            if (in_array($normalizedFlag, ['1', 'true', 'yes', 'active'], true)) {
                return 1;
            }

            if (in_array($normalizedFlag, ['0', 'false', 'no', 'inactive'], true)) {
                return 0;
            }
        }

        return 1;
    }

    private function nullableText(mixed $textValue, int $maximumLength): ?string
    {
        $normalizedText = trim((string) ($textValue ?? ''));
        if ($normalizedText === '') {
            return null;
        }

        return mb_substr($normalizedText, 0, $maximumLength, 'UTF-8');
    }

    /**
     * @param array<string, array<int, string>> $validationErrors
     */
    private function firstValidationMessage(array $validationErrors): string
    {
        foreach ($validationErrors as $fieldErrors) {
            if (is_array($fieldErrors) && isset($fieldErrors[0]) && is_string($fieldErrors[0])) {
                return $fieldErrors[0];
            }
        }

        return 'payload is invalid.';
    }

    private function newUuid(): string
    {
        $randomBytes = random_bytes(16);
        $randomBytes[6] = chr((ord($randomBytes[6]) & 0x0f) | 0x40);
        $randomBytes[8] = chr((ord($randomBytes[8]) & 0x3f) | 0x80);
        $hexadecimal = bin2hex($randomBytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hexadecimal, 0, 8),
            substr($hexadecimal, 8, 4),
            substr($hexadecimal, 12, 4),
            substr($hexadecimal, 16, 4),
            substr($hexadecimal, 20),
        );
    }
}
