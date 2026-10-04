<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Privileges\Services;

use App\Modules\Privileges\Contracts\FMSPrivilegesPermissionRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;

/**
 * Effective-permission decision engine for the Privileges (RBAC) domain.
 *
 * Contract enforced here:
 *  - deny by default: an unlisted permission code is never granted;
 *  - explicit deny precedence: a deny row always beats an allow row for the
 *    same permission code, including for a super administrator;
 *  - expiration: a grant whose `expires_at` is at or before the reference time
 *    is ignored, and an expired group membership contributes no grants;
 *  - inactive rows never grant anything;
 *  - super administrator is derived from an effective `is_super_admin` grant,
 *    and expands to every permission code that is not explicitly denied;
 *  - the returned permission codes are unique and deterministic (sorted), so
 *    they can be hashed into a cache-entry version.
 *
 * @phpstan-type PermissionGrantRow array{
 *     permission_code: string,
 *     effect: string,
 *     expires_at?: string|null,
 *     is_active?: int|string|null,
 *     is_super_admin?: int|string|null,
 *     group_id?: int|string|null,
 *     group_expires_at?: string|null
 * }
 */
final class FMSPrivilegesEffectivePermissionService
{
    public const EFFECT_ALLOW = 'allow';
    public const EFFECT_DENY = 'deny';

    private const PERMISSION_WILDCARD = '*';
    private const REFERENCE_TIME_ZONE = 'UTC';

    public function __construct(
        private readonly ?FMSPrivilegesPermissionRepositoryInterface $permissionRepository = null,
    ) {
    }

    /**
     * Resolve the effective permission codes from already-loaded grant rows.
     *
     * @param array<int, PermissionGrantRow> $permissionGrantRows
     * @return array<int, string>
     */
    public function resolveEffectivePermissionCodes(
        array $permissionGrantRows,
        ?DateTimeImmutable $referenceTime = null,
    ): array {
        $referenceTime ??= $this->currentReferenceTime();
        $allowedPermissionCodes = [];
        $deniedPermissionCodes = [];
        $grantedSuperAdminAccess = false;
        $deniedSuperAdminAccess = false;

        foreach ($permissionGrantRows as $permissionGrantRow) {
            if (! is_array($permissionGrantRow) || ! isset($permissionGrantRow['permission_code'])) {
                continue;
            }

            if (! $this->isRowActive($permissionGrantRow)) {
                continue;
            }

            if ($this->isExpiredRow($permissionGrantRow, $referenceTime)) {
                continue;
            }

            $permissionCode = trim((string) $permissionGrantRow['permission_code']);
            if ($permissionCode === '') {
                continue;
            }

            $permissionEffect = $this->resolveEffect($permissionGrantRow);
            $isSuperAdminGrant = (int) ($permissionGrantRow['is_super_admin'] ?? 0) === 1;

            if ($permissionEffect === self::EFFECT_DENY) {
                if ($isSuperAdminGrant) {
                    $deniedSuperAdminAccess = true;
                }

                $deniedPermissionCodes[$permissionCode] = true;
                continue;
            }

            if ($isSuperAdminGrant) {
                $grantedSuperAdminAccess = true;
            }

            $allowedPermissionCodes[$permissionCode] = true;
        }

        if ($grantedSuperAdminAccess && ! $deniedSuperAdminAccess) {
            $allowedPermissionCodes[self::PERMISSION_WILDCARD] = true;
        }

        $effectivePermissionCodes = [];
        foreach (array_keys($allowedPermissionCodes) as $permissionCode) {
            if (isset($deniedPermissionCodes[$permissionCode])) {
                continue;
            }

            $effectivePermissionCodes[] = $permissionCode;
        }

        sort($effectivePermissionCodes, SORT_STRING);

        return $effectivePermissionCodes;
    }

    /**
     * Decide whether one permission code is effectively granted.
     *
     * @param array<int, PermissionGrantRow> $permissionGrantRows
     */
    public function hasEffectivePermission(
        string $permissionCode,
        array $permissionGrantRows,
        ?DateTimeImmutable $referenceTime = null,
    ): bool {
        $normalizedPermissionCode = trim($permissionCode);
        if ($normalizedPermissionCode === '') {
            throw new InvalidArgumentException('Permission code must not be empty.');
        }

        $effectivePermissionCodes = $this->resolveEffectivePermissionCodes($permissionGrantRows, $referenceTime);

        if (in_array($normalizedPermissionCode, $effectivePermissionCodes, true)) {
            return true;
        }

        return in_array(self::PERMISSION_WILDCARD, $effectivePermissionCodes, true);
    }

    /**
     * Decide whether the supplied grant rows make the user a super administrator.
     *
     * @param array<int, PermissionGrantRow> $permissionGrantRows
     */
    public function isSuperAdministrator(
        array $permissionGrantRows,
        ?DateTimeImmutable $referenceTime = null,
    ): bool {
        foreach ($permissionGrantRows as $permissionGrantRow) {
            if (! is_array($permissionGrantRow)) {
                continue;
            }

            if ((int) ($permissionGrantRow['is_super_admin'] ?? 0) !== 1) {
                continue;
            }

            if (! $this->isRowActive($permissionGrantRow)) {
                continue;
            }

            if ($this->isExpiredRow($permissionGrantRow, $referenceTime ?? $this->currentReferenceTime())) {
                continue;
            }

            if ($this->resolveEffect($permissionGrantRow) !== self::EFFECT_DENY) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve effective permission codes for one user through storage.
     *
     * @return array<int, string>
     */
    public function resolveEffectivePermissionCodesForUser(
        int $userIdentifier,
        ?DateTimeImmutable $referenceTime = null,
    ): array {
        if ($userIdentifier <= 0) {
            throw new InvalidArgumentException('User identifier must be a positive integer.');
        }

        return $this->resolveEffectivePermissionCodes(
            $this->loadPermissionGrantRowsForUser($userIdentifier, $referenceTime),
            $referenceTime,
        );
    }

    /**
     * Resolve effective permission codes hanya untuk satu kelompok aktif.
     *
     * @return array<int, string>
     */
    public function resolveEffectivePermissionCodesForGroup(
        int $groupIdentifier,
        ?DateTimeImmutable $referenceTime = null,
    ): array {
        if ($groupIdentifier <= 0) {
            throw new InvalidArgumentException('Group identifier must be a positive integer.');
        }

        return $this->resolveEffectivePermissionCodes(
            $this->permissionRepository()->loadGroupPermissionRows([$groupIdentifier]),
            $referenceTime,
        );
    }

    /**
     * Load direct and group grants for one user, tagging each row with its source.
     * Group memberships that already expired contribute no grants.
     *
     * @return array<int, PermissionGrantRow>
     */
    public function loadPermissionGrantRowsForUser(
        int $userIdentifier,
        ?DateTimeImmutable $referenceTime = null,
    ): array {
        $repository = $this->permissionRepository();
        $referenceTime ??= $this->currentReferenceTime();
        $permissionGrantRows = [];

        foreach ($repository->loadDirectUserPermissionRows($userIdentifier) as $directPermissionGrantRow) {
            $permissionGrantRows[] = $directPermissionGrantRow + ['source' => 'direct'];
        }

        $activeGroupIdentifiers = [];
        foreach ($repository->loadUserGroupIdentifiers($userIdentifier) as $groupIdentifier) {
            $normalizedGroupIdentifier = (int) $groupIdentifier;
            if ($normalizedGroupIdentifier > 0) {
                $activeGroupIdentifiers[] = $normalizedGroupIdentifier;
            }
        }

        if ($activeGroupIdentifiers !== []) {
            foreach ($repository->loadGroupPermissionRows($activeGroupIdentifiers) as $groupPermissionGrantRow) {
                $permissionGrantRows[] = $groupPermissionGrantRow + ['source' => 'group'];
            }
        }

        return array_values(array_filter(
            $permissionGrantRows,
            fn (array $permissionGrantRow): bool => ! $this->isExpiredRow($permissionGrantRow, $referenceTime),
        ));
    }

    /**
     * Resolve all menu-permission rows in one repository call, preventing a
     * query per menu while the sidebar is composed.
     *
     * @param array<int, int> $menuIdentifiers
     * @return array<int, array{menu_id: int, permission_code: string}>
     */
    public function menuPermissionRows(array $menuIdentifiers): array
    {
        return $this->permissionRepository()->loadMenuPermissionRows($menuIdentifiers);
    }

    /**
     * Build a deterministic cache key for resolved permissions. Changing any
     * resolved code or the caller-supplied version (bumped on every RBAC
     * mutation) produces a different key, so stale cache entries are never hit.
     *
     * @param array<int, string> $effectivePermissionCodes
     */
    public function resolvePermissionCacheKey(
        int $userIdentifier,
        string $permissionVersion,
        array $effectivePermissionCodes,
    ): string {
        if ($userIdentifier <= 0) {
            throw new InvalidArgumentException('User identifier must be a positive integer.');
        }

        if (trim($permissionVersion) === '') {
            throw new InvalidArgumentException('Permission version must not be empty.');
        }

        $sortedPermissionCodes = $effectivePermissionCodes;
        sort($sortedPermissionCodes, SORT_STRING);

        return 'fms-privileges:' . hash(
            'sha256',
            $userIdentifier . '|' . trim($permissionVersion) . '|' . implode(',', $sortedPermissionCodes),
        );
    }

    /**
     * @param PermissionGrantRow $permissionGrantRow
     */
    private function isRowActive(array $permissionGrantRow): bool
    {
        if (! array_key_exists('is_active', $permissionGrantRow)) {
            return true;
        }

        return (int) $permissionGrantRow['is_active'] === 1;
    }

    /**
     * @param PermissionGrantRow $permissionGrantRow
     */
    private function isExpiredRow(array $permissionGrantRow, DateTimeImmutable $referenceTime): bool
    {
        foreach (['expires_at', 'group_expires_at'] as $expirationField) {
            $expirationValue = $permissionGrantRow[$expirationField] ?? null;
            if ($expirationValue === null || $expirationValue === '') {
                continue;
            }

            $expirationTime = new DateTimeImmutable((string) $expirationValue, new DateTimeZone(self::REFERENCE_TIME_ZONE));
            if ($expirationTime <= $referenceTime) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param PermissionGrantRow $permissionGrantRow
     */
    private function resolveEffect(array $permissionGrantRow): string
    {
        $permissionEffect = strtolower(trim((string) ($permissionGrantRow['effect'] ?? self::EFFECT_ALLOW)));

        return $permissionEffect === self::EFFECT_DENY ? self::EFFECT_DENY : self::EFFECT_ALLOW;
    }

    private function currentReferenceTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone(self::REFERENCE_TIME_ZONE));
    }

    private function permissionRepository(): FMSPrivilegesPermissionRepositoryInterface
    {
        if ($this->permissionRepository === null) {
            throw new RuntimeException('A permission repository is required to resolve permissions from storage.');
        }

        return $this->permissionRepository;
    }
}
