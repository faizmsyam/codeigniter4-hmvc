<?php

namespace App\Modules\Privileges\Validation;

use InvalidArgumentException;

/**
 * Allowlisted payload validation for the Privileges (RBAC) domain.
 *
 * Every mutating endpoint prepares its storage row only from explicit fields;
 * anything else in the request payload is discarded before it reaches the
 * database layer (mass-assignment guard), and identifiers are validated here.
 */
final class FMSPrivilegesValidation
{
    private const GROUP_UUID_PATTERN = '/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i';

    private const GROUP_CODE_PATTERN = '/\A[a-z0-9][a-z0-9_.-]{1,58}[a-z0-9]\z/';

    private const PERMISSION_KEY_PATTERN = '/\A[a-z0-9][a-z0-9_.-]{1,118}[a-z0-9.*]\z/';

    private const NAME_PATTERN = '/\A[a-z0-9][a-z0-9_. -]{1,98}[a-z0-9)\]]\z/i';

    private const ALLOWED_EFFECTS = ['allow', 'deny'];

    /**
     * @param array<string, mixed> $groupPayload
     * @return array<string, array<int, string>>
     */
    public static function validateGroupCreate(array $groupPayload): array
    {
        return self::validateGroupPayload($groupPayload, true);
    }

    /**
     * @param array<string, mixed> $groupPayload
     * @return array<string, array<int, string>>
     */
    public static function validateGroupUpdate(array $groupPayload): array
    {
        return self::validateGroupPayload($groupPayload, false);
    }

    /**
     * @param array<string, mixed> $permissionPayload
     * @return array<string, array<int, string>>
     */
    public static function validatePermissionCreate(array $permissionPayload): array
    {
        return self::validatePermissionPayload($permissionPayload, true);
    }

    /**
     * @param array<string, mixed> $permissionPayload
     * @return array<string, array<int, string>>
     */
    public static function validatePermissionUpdate(array $permissionPayload): array
    {
        return self::validatePermissionPayload($permissionPayload, false);
    }

    public static function isValidUuid(string $groupUuid): bool
    {
        return preg_match(self::GROUP_UUID_PATTERN, $groupUuid) === 1;
    }

    /**
     * @param mixed $identifierList
     * @return array<string, array<int, string>>
     */
    public static function validateIdentifierList(mixed $identifierList, string $fieldName): array
    {
        if (! is_array($identifierList) || $identifierList === []) {
            return [$fieldName => ['Daftar identifier tidak boleh kosong.']];
        }

        $normalizedIdentifiers = [];
        foreach (array_values($identifierList) as $identifierValue) {
            if (is_int($identifierValue) || (is_string($identifierValue) && ctype_digit(trim($identifierValue)))) {
                $normalizedIdentifiers[] = (int) $identifierValue;
                continue;
            }

            return [$fieldName => ['Setiap identifier harus berupa integer positif.']];
        }

        foreach ($normalizedIdentifiers as $normalizedIdentifier) {
            if ($normalizedIdentifier <= 0) {
                return [$fieldName => ['Setiap identifier harus berupa integer positif.']];
            }
        }

        if (count(array_unique($normalizedIdentifiers)) !== count($normalizedIdentifiers)) {
            return [$fieldName => ['Daftar identifier tidak boleh mengandung duplikat.']];
        }

        return [];
    }

    public static function isValidEffect(string $grantEffect): bool
    {
        return in_array(mb_strtolower(trim($grantEffect), 'UTF-8'), self::ALLOWED_EFFECTS, true);
    }

    /**
     * @param array<string, mixed> $mappingPayload
     * @return array<string, array<int, string>>
     */
    public static function validateGroupPermissionMapping(array $mappingPayload): array
    {
        $validationErrors = [];
        $entries = $mappingPayload['permissions'] ?? $mappingPayload['mappings'] ?? null;
        if (! is_array($entries) || $entries === []) {
            return ['permissions' => ['Daftar permission mapping tidak boleh kosong.']];
        }

        $seenPermissionIdentifiers = [];
        foreach (array_values($entries) as $entryIndex => $mappingEntry) {
            if (! is_array($mappingEntry)) {
                $validationErrors['permissions.' . $entryIndex] = ['Setiap mapping harus berupa objek.'];

                continue;
            }

            $permissionIdentifier = $mappingEntry['permission_id'] ?? $mappingEntry['permissionId'] ?? null;
            if (! is_int($permissionIdentifier) && ! (is_string($permissionIdentifier) && ctype_digit(trim((string) $permissionIdentifier)))) {
                $validationErrors['permissions.' . $entryIndex] = ['permission_id harus berupa integer positif.'];

                continue;
            }

            $permissionIdentifier = (int) $permissionIdentifier;
            if ($permissionIdentifier <= 0) {
                $validationErrors['permissions.' . $entryIndex] = ['permission_id harus berupa integer positif.'];

                continue;
            }

            if (isset($seenPermissionIdentifiers[$permissionIdentifier])) {
                $validationErrors['permissions.' . $entryIndex] = ['permission_id tidak boleh duplikat.'];

                continue;
            }

            $seenPermissionIdentifiers[$permissionIdentifier] = true;
            $grantEffect = mb_strtolower(trim((string) ($mappingEntry['effect'] ?? 'allow')), 'UTF-8');
            if (! self::isValidEffect($grantEffect)) {
                $validationErrors['permissions.' . $entryIndex] = ['effect harus bernilai allow atau deny.'];
            }
        }

        return $validationErrors;
    }

    /**
     * @param array<string, mixed> $mappingPayload
     * @return array<string, array<int, string>>
     */
    public static function validateMenuPermissionMapping(array $mappingPayload): array
    {
        $entries = $mappingPayload['permissions'] ?? $mappingPayload['mappings'] ?? $mappingPayload['permission_ids'] ?? null;
        if (! is_array($entries) || $entries === []) {
            return ['permissions' => ['Daftar permission mapping tidak boleh kosong.']];
        }

        $firstEntry = reset($entries);
        if (is_array($firstEntry)) {
            return self::validateGroupPermissionMapping(['permissions' => $entries]);
        }

        return self::validateIdentifierList($entries, 'permissions');
    }

    /**
     * @param array<string, mixed> $assignmentPayload
     * @return array<string, array<int, string>>
     */
    public static function validateUserGroupAssignment(array $assignmentPayload): array
    {
        $entries = $assignmentPayload['groups'] ?? $assignmentPayload['group_ids'] ?? null;
        if (! is_array($entries) || $entries === []) {
            return ['groups' => ['Daftar group tidak boleh kosong.']];
        }

        $firstEntry = reset($entries);
        if (is_array($firstEntry)) {
            $validationErrors = [];
            $seenGroupIdentifiers = [];
            foreach (array_values($entries) as $entryIndex => $assignmentEntry) {
                if (! is_array($assignmentEntry)) {
                    $validationErrors['groups.' . $entryIndex] = ['Setiap assignment harus berupa objek.'];

                    continue;
                }

                $groupIdentifier = $assignmentEntry['group_id'] ?? null;
                if (! is_int($groupIdentifier) && ! (is_string($groupIdentifier) && ctype_digit(trim((string) $groupIdentifier)))) {
                    $validationErrors['groups.' . $entryIndex] = ['group_id harus berupa integer positif.'];

                    continue;
                }

                $groupIdentifier = (int) $groupIdentifier;
                if ($groupIdentifier <= 0) {
                    $validationErrors['groups.' . $entryIndex] = ['group_id harus berupa integer positif.'];

                    continue;
                }

                if (isset($seenGroupIdentifiers[$groupIdentifier])) {
                    $validationErrors['groups.' . $entryIndex] = ['group_id tidak boleh duplikat.'];

                    continue;
                }

                $seenGroupIdentifiers[$groupIdentifier] = true;
                $expirationTimestamp = $assignmentEntry['expires_at'] ?? null;
                if ($expirationTimestamp !== null && trim((string) $expirationTimestamp) !== '') {
                    try {
                        self::assertFutureTimestamp((string) $expirationTimestamp);
                    } catch (InvalidArgumentException $invalidArgumentException) {
                        $validationErrors['groups.' . $entryIndex] = [$invalidArgumentException->getMessage()];
                    }
                }
            }

            return $validationErrors;
        }

        return self::validateIdentifierList($entries, 'groups');
    }

    public static function assertFutureTimestamp(string $expirationTimestamp): void
    {
        $normalizedTimestamp = trim($expirationTimestamp);
        if ($normalizedTimestamp === '') {
            throw new InvalidArgumentException('Expiration must be a valid datetime.');
        }

        if (strtotime($normalizedTimestamp) === false) {
            throw new InvalidArgumentException('Expiration must be a valid datetime.');
        }

        if (strtotime($normalizedTimestamp) <= time()) {
            throw new InvalidArgumentException('Expiration must be in the future.');
        }
    }

    /**
     * @param array<string, mixed> $groupPayload
     * @return array<string, array<int, string>>
     */
    private static function validateGroupPayload(array $groupPayload, bool $isCreate): array
    {
        $validationErrors = [];
        $groupCode = trim((string) ($groupPayload['code'] ?? ''));
        $groupName = trim((string) ($groupPayload['name'] ?? ''));

        if ($isCreate || $groupCode !== '') {
            if ($groupCode === '' || mb_strlen($groupCode) > 60) {
                $validationErrors['code'] = ['Kode group wajib diisi maksimal 60 karakter.'];
            } elseif (preg_match(self::GROUP_CODE_PATTERN, mb_strtolower($groupCode, 'UTF-8')) !== 1) {
                $validationErrors['code'] = ['Kode group hanya boleh huruf kecil, angka, titik, strip, atau underscore.'];
            }
        }

        if ($isCreate || $groupName !== '') {
            if ($groupName === '' || mb_strlen($groupName) > 100) {
                $validationErrors['name'] = ['Nama group wajib diisi maksimal 100 karakter.'];
            }
        }

        $groupDescription = $groupPayload['description'] ?? null;
        if ($groupDescription !== null && mb_strlen(trim((string) $groupDescription)) > 255) {
            $validationErrors['description'] = ['Deskripsi group maksimal 255 karakter.'];
        }

        return $validationErrors;
    }

    /**
     * @param array<string, mixed> $permissionPayload
     * @return array<string, array<int, string>>
     */
    private static function validatePermissionPayload(array $permissionPayload, bool $isCreate): array
    {
        $validationErrors = [];
        $permissionKey = trim((string) ($permissionPayload['permission_key'] ?? ''));
        $moduleName = trim((string) ($permissionPayload['module_name'] ?? ''));
        $actionName = trim((string) ($permissionPayload['action_name'] ?? ''));

        if ($isCreate || $permissionKey !== '') {
            if ($permissionKey === '' || mb_strlen($permissionKey) > 120) {
                $validationErrors['permission_key'] = ['Permission key wajib diisi maksimal 120 karakter.'];
            } elseif (preg_match(self::PERMISSION_KEY_PATTERN, mb_strtolower($permissionKey, 'UTF-8')) !== 1) {
                $validationErrors['permission_key'] = ['Permission key hanya boleh huruf kecil, angka, titik, strip, underscore, atau wildcard *.'];
            }
        }

        if ($isCreate || $moduleName !== '') {
            if ($moduleName === '' || mb_strlen($moduleName) > 60) {
                $validationErrors['module_name'] = ['Nama modul wajib diisi maksimal 60 karakter.'];
            }
        }

        if ($isCreate || $actionName !== '') {
            if ($actionName === '' || mb_strlen($actionName) > 60) {
                $validationErrors['action_name'] = ['Nama aksi wajib diisi maksimal 60 karakter.'];
            }
        }

        $permissionDescription = $permissionPayload['description'] ?? null;
        if ($permissionDescription !== null && mb_strlen(trim((string) $permissionDescription)) > 255) {
            $validationErrors['description'] = ['Deskripsi permission maksimal 255 karakter.'];
        }

        return $validationErrors;
    }
}
