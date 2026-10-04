<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\AdminMenus\Services;

use InvalidArgumentException;

/**
 * Pure menu-tree composition for the AdminMenus domain.
 * Stateless and database-free: builds a tree from any row set, including rows
 * already filtered by the Privileges module for one authenticated request.
 *
 * @phpstan-type MenuRow array{id: int|string, id_parent: int|string|null, name: string, url: ?string, icon: ?string, position: int|string, is_active: int|string, target_blank: int|string}
 */
final class FMSAdminMenuTreeService
{
    public const MAXIMUM_TREE_DEPTH = 3;

    /**
     * Build a nested menu tree ordered by parent and position.
     *
     * @param array<int, MenuRow> $menuRows
     * @return array<int, array<string, mixed>>
     */
    public function buildMenuTree(array $menuRows): array
    {
        $menusByParent = [];
        foreach ($menuRows as $menuRow) {
            $parentIdentifier = $this->normalizeParentIdentifier($menuRow['id_parent'] ?? null);
            $menusByParent[$parentIdentifier ?? 0][] = $menuRow;
        }

        foreach ($menusByParent as $parentIdentifier => $siblingRows) {
            usort(
                $siblingRows,
                static function (array $firstMenuRow, array $secondMenuRow): int {
                    $positionComparison = ((int) $firstMenuRow['position']) <=> ((int) $secondMenuRow['position']);
                    if ($positionComparison !== 0) {
                        return $positionComparison;
                    }

                    return ((int) $firstMenuRow['id']) <=> ((int) $secondMenuRow['id']);
                },
            );
            $menusByParent[$parentIdentifier] = $siblingRows;
        }

        return $this->buildBranch($menusByParent, null, [], 1);
    }

    /**
     * Build the sidebar tree for one authenticated request.
     *
     * Permission filtering is injected as a callback so AdminMenus never
     * depends on RBAC storage. The callback receives the menu identifier and
     * the raw menu row, returning true only when the caller may see it.
     *
     * @param array<int, MenuRow> $menuRows
     * @param callable(int, array<string, mixed>): bool $permissionChecker
     * @return array<int, array<string, mixed>>
     */
    public function buildSidebarTree(array $menuRows, callable $permissionChecker): array
    {
        $activeMenuRows = [];
        $authorizedMenuIdentifiers = [];

        foreach ($menuRows as $menuRow) {
            if ((int) ($menuRow['is_active'] ?? 0) !== 1) {
                continue;
            }

            $menuIdentifier = (int) $menuRow['id'];
            $activeMenuRows[$menuIdentifier] = $menuRow;
            if ($permissionChecker($menuIdentifier, $menuRow) === true) {
                $authorizedMenuIdentifiers[$menuIdentifier] = true;
            }
        }

        // A visible descendant needs its active ancestor containers in the
        // response, even when those containers do not have their own grant.
        $visibleMenuIdentifiers = $authorizedMenuIdentifiers;
        foreach (array_keys($authorizedMenuIdentifiers) as $authorizedMenuIdentifier) {
            $ancestorIdentifier = $this->normalizeParentIdentifier(
                $activeMenuRows[$authorizedMenuIdentifier]['id_parent'] ?? null,
            );
            $visitedAncestors = [];

            while ($ancestorIdentifier !== null
                && isset($activeMenuRows[$ancestorIdentifier])
                && ! isset($visitedAncestors[$ancestorIdentifier])
            ) {
                $visibleMenuIdentifiers[$ancestorIdentifier] = true;
                $visitedAncestors[$ancestorIdentifier] = true;
                $ancestorIdentifier = $this->normalizeParentIdentifier(
                    $activeMenuRows[$ancestorIdentifier]['id_parent'] ?? null,
                );
            }
        }

        $visibleMenuRows = array_values(array_filter(
            $activeMenuRows,
            static fn (array $menuRow): bool => isset($visibleMenuIdentifiers[(int) $menuRow['id']]),
        ));

        return $this->buildMenuTree($visibleMenuRows);
    }

    /**
     * Flatten a tree into identifier => depth pairs.
     *
     * @param array<int, array<string, mixed>> $menuTree
     * @return array<int, int>
     */
    public function flattenMenuTree(array $menuTree, int $treeDepth = 1): array
    {
        $flattenedMenus = [];

        foreach ($menuTree as $treeNode) {
            $flattenedMenus[(int) $treeNode['id']] = $treeDepth;

            if (isset($treeNode['children']) && is_array($treeNode['children'])) {
                $flattenedMenus += $this->flattenMenuTree($treeNode['children'], $treeDepth + 1);
            }
        }

        return $flattenedMenus;
    }

    /**
     * Reject a parent assignment that would create a cycle or exceed depth.
     * The ancestor chain is walked from stored rows, never from client input.
     *
     * @param array<int, MenuRow> $menuRows
     */
    public function assertValidParent(
        array $menuRows,
        int $menuIdentifier,
        ?int $parentIdentifier,
        int $maximumTreeDepth = self::MAXIMUM_TREE_DEPTH,
    ): void {
        if ($parentIdentifier === null) {
            return;
        }

        if ($menuIdentifier === $parentIdentifier) {
            throw new InvalidArgumentException('A menu cannot be its own parent.');
        }

        $menuRowsByIdentifier = [];
        foreach ($menuRows as $menuRow) {
            $menuRowsByIdentifier[(int) $menuRow['id']] = $menuRow;
        }

        if (! isset($menuRowsByIdentifier[$parentIdentifier])) {
            throw new InvalidArgumentException('Parent menu does not exist.');
        }

        $ancestorIdentifier = $parentIdentifier;
        $ancestorDepth = 1;

        while ($ancestorIdentifier !== null) {
            if ($ancestorIdentifier === $menuIdentifier) {
                throw new InvalidArgumentException('Parent assignment would create a cycle.');
            }

            $ancestorRow = $menuRowsByIdentifier[$ancestorIdentifier] ?? null;
            if ($ancestorRow === null) {
                break;
            }

            $ancestorDepth++;
            if ($ancestorDepth > $maximumTreeDepth) {
                throw new InvalidArgumentException('Parent assignment exceeds the maximum tree depth.');
            }

            $ancestorIdentifier = $this->normalizeParentIdentifier($ancestorRow['id_parent'] ?? null);
        }
    }

    /**
     * @param array<int|string, array<int, MenuRow>> $menusByParent
     * @param array<int, int> $ancestorIdentifiers
     * @return array<int, array<string, mixed>>
     */
    private function buildBranch(
        array $menusByParent,
        ?int $parentIdentifier,
        array $ancestorIdentifiers,
        int $treeDepth,
    ): array {
        if ($treeDepth > self::MAXIMUM_TREE_DEPTH) {
            return [];
        }

        $menuBranch = [];
        $parentKey = $parentIdentifier ?? 0;

        foreach ($menusByParent[$parentKey] ?? [] as $menuRow) {
            $menuIdentifier = (int) $menuRow['id'];
            if (in_array($menuIdentifier, $ancestorIdentifiers, true)) {
                continue;
            }

            $treeNode = [
                'id' => $menuIdentifier,
                'id_parent' => $this->normalizeParentIdentifier($menuRow['id_parent'] ?? null),
                'name' => (string) $menuRow['name'],
                'url' => ($menuRow['url'] ?? null) === null ? null : (string) $menuRow['url'],
                'icon' => ($menuRow['icon'] ?? null) === null ? null : (string) $menuRow['icon'],
                'position' => (int) $menuRow['position'],
                'is_active' => (int) $menuRow['is_active'],
                'target_blank' => (int) $menuRow['target_blank'],
            ];

            $childBranch = $this->buildBranch(
                $menusByParent,
                $menuIdentifier,
                [...$ancestorIdentifiers, $menuIdentifier],
                $treeDepth + 1,
            );

            if ($childBranch !== []) {
                $treeNode['children'] = $childBranch;
            }

            $menuBranch[] = $treeNode;
        }

        return $menuBranch;
    }

    private function normalizeParentIdentifier(mixed $parentIdentifier): ?int
    {
        if ($parentIdentifier === null || $parentIdentifier === '') {
            return null;
        }

        $normalizedParentIdentifier = (int) $parentIdentifier;

        return $normalizedParentIdentifier > 0 ? $normalizedParentIdentifier : null;
    }
}
