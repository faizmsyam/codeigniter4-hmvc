<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Libraries;

/**
 * Namespace-aware permission checker shared by every API domain.
 *
 * Authentication proves who is calling. Authorization decides whether the
 * verified subject may perform the requested action. Unknown permissions are
 * denied; only exact, namespace wildcard/manage, or global wildcard claims pass.
 */
class FMSPermissionAuthorizationService
{
    public const GLOBAL_WILDCARD = '*';

    private const MANAGE_ACTION = 'manage';

    /**
     * @param array<string, mixed> $authenticatedSubject
     */
    public function allows(
        array $authenticatedSubject,
        string $requiredPermission,
        ?string $targetUserUuid = null,
        bool $allowSelf = false,
    ): bool {
        $normalizedPermission = mb_strtolower(trim($requiredPermission), 'UTF-8');
        if ($normalizedPermission === '') {
            return false;
        }

        if ($allowSelf && $this->isSameUser($authenticatedSubject, $targetUserUuid)) {
            return true;
        }

        $permissionSet = $this->permissionSet($authenticatedSubject);
        if (isset($permissionSet[self::GLOBAL_WILDCARD]) || isset($permissionSet[$normalizedPermission])) {
            return true;
        }

        if (isset($permissionSet[$normalizedPermission . '.*'])) {
            return true;
        }

        $permissionNamespace = $this->permissionNamespace($normalizedPermission);
        if ($permissionNamespace === null) {
            return false;
        }

        return isset($permissionSet[$permissionNamespace . '.*'])
            || isset($permissionSet[$permissionNamespace . '.' . self::MANAGE_ACTION]);
    }

    /**
     * @param array<string, mixed> $authenticatedSubject
     */
    public function isSuperAdministrator(array $authenticatedSubject): bool
    {
        return isset($this->permissionSet($authenticatedSubject)[self::GLOBAL_WILDCARD]);
    }

    /**
     * @param array<string, mixed> $authenticatedSubject
     */
    public function authenticatedUserUuid(array $authenticatedSubject): ?string
    {
        $subjectUserUuid = trim((string) (
            $authenticatedSubject['sub']
            ?? $authenticatedSubject['user_uuid']
            ?? ''
        ));

        return $subjectUserUuid === '' ? null : $subjectUserUuid;
    }

    /**
     * @param array<string, mixed> $authenticatedSubject
     */
    public function authenticatedUserIdentifier(array $authenticatedSubject): int
    {
        return (int) ($authenticatedSubject['user_id'] ?? 0);
    }

    /**
     * @param array<string, mixed> $authenticatedSubject
     * @return array<string, true>
     */
    private function permissionSet(array $authenticatedSubject): array
    {
        $permissionValues = [];
        foreach (['permissions', 'scopes', 'scope'] as $claimName) {
            $claimValue = $authenticatedSubject[$claimName] ?? [];
            if (is_string($claimValue)) {
                $claimValue = preg_split('/[\s,]+/', trim($claimValue)) ?: [];
            }

            if (is_array($claimValue)) {
                $permissionValues = array_merge($permissionValues, $claimValue);
            }
        }

        $permissionSet = [];
        foreach ($permissionValues as $permissionValue) {
            if (! is_string($permissionValue)) {
                continue;
            }

            $normalizedPermission = mb_strtolower(trim($permissionValue), 'UTF-8');
            if ($normalizedPermission !== '') {
                $permissionSet[$normalizedPermission] = true;
            }
        }

        return $permissionSet;
    }

    private function permissionNamespace(string $normalizedPermission): ?string
    {
        $separatorPosition = strpos($normalizedPermission, '.');
        if ($separatorPosition === false || $separatorPosition === 0) {
            return null;
        }

        return substr($normalizedPermission, 0, $separatorPosition);
    }

    /**
     * @param array<string, mixed> $authenticatedSubject
     */
    private function isSameUser(array $authenticatedSubject, ?string $targetUserUuid): bool
    {
        if ($targetUserUuid === null || trim($targetUserUuid) === '') {
            return false;
        }

        $subjectUserUuid = $this->authenticatedUserUuid($authenticatedSubject);
        if ($subjectUserUuid === null) {
            return false;
        }

        return hash_equals(
            mb_strtolower(trim($targetUserUuid), 'UTF-8'),
            mb_strtolower($subjectUserUuid, 'UTF-8'),
        );
    }
}
