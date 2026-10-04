<?php

namespace App\Modules\Privileges\Services;

/**
 * Route-level permission gate for the Privileges (RBAC) domain.
 *
 * Deny by default: only explicit `privileges.*` grants, `groups.*`, or `*`
 * pass. An explicit `denied_permissions` claim always wins over any grant,
 * including the wildcard, mirroring the effective-permission decision engine.
 */
final class FMSPrivilegesAuthorizationService
{
    /**
     * @param array<string, mixed> $authenticatedSubject
     */
    public function allows(array $authenticatedSubject, string $requiredPermission): bool
    {
        $requiredPermission = mb_strtolower(trim($requiredPermission), 'UTF-8');
        if ($requiredPermission === '') {
            return false;
        }

        $deniedPermissionSet = $this->permissionSet($authenticatedSubject, true);
        if (isset($deniedPermissionSet[$requiredPermission]) || isset($deniedPermissionSet['*'])) {
            return false;
        }

        $grantedPermissionSet = $this->permissionSet($authenticatedSubject, false);

        foreach ($this->grantedPermissionCandidates($requiredPermission) as $grantedPermissionCandidate) {
            if (isset($grantedPermissionSet[$grantedPermissionCandidate])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $authenticatedSubject
     * @return array<string, true>
     */
    private function permissionSet(array $authenticatedSubject, bool $deniedPermissions): array
    {
        $claimNames = $deniedPermissions
            ? ['denied_permissions', 'deniedPermissions', 'denied_scopes']
            : ['permissions', 'scopes', 'scope'];

        $permissionValues = [];
        foreach ($claimNames as $claimName) {
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

    /**
     * @return array<int, string>
     */
    private function grantedPermissionCandidates(string $requiredPermission): array
    {
        $permissionCandidates = ['*', $requiredPermission];

        $permissionSegments = explode('.', $requiredPermission);
        if (count($permissionSegments) >= 3 && $permissionSegments[0] === 'privileges') {
            $permissionCandidates[] = 'privileges.*';
            $permissionCandidates[] = $permissionSegments[0] . '.' . $permissionSegments[1] . '.*';
        } elseif (count($permissionSegments) === 2) {
            $permissionCandidates[] = $permissionSegments[0] . '.*';
        }

        return array_values(array_unique($permissionCandidates));
    }
}
