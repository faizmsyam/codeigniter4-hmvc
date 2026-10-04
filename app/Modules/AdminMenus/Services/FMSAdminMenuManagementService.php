<?php

namespace App\Modules\AdminMenus\Services;

use App\Modules\AdminMenus\Models\FMSAdminMenuModel;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Validates and persists AdminMenus lifecycle mutations.
 */
final class FMSAdminMenuManagementService
{
    private FMSAdminMenuModel $adminMenuModel;

    public function __construct(?FMSAdminMenuModel $adminMenuModel = null)
    {
        $this->adminMenuModel = $adminMenuModel ?? new FMSAdminMenuModel();
    }

    /** @param array<string, mixed> $menuData @return array<string, mixed> */
    public function prepareMenuData(array $menuData, bool $partial = false): array
    {
        $allowedFields = ['id_parent', 'name', 'url', 'icon', 'position', 'is_active', 'target_blank'];
        $preparedData = [];

        foreach ($allowedFields as $allowedField) {
            if (array_key_exists($allowedField, $menuData)) {
                $preparedData[$allowedField] = $menuData[$allowedField];
            }
        }

        if (! $partial || array_key_exists('name', $preparedData)) {
            $menuName = trim((string) ($preparedData['name'] ?? ''));
            if ($menuName === '' || mb_strlen($menuName, 'UTF-8') > 100) {
                throw new InvalidArgumentException('Menu name is required and must not exceed 100 characters.');
            }
            $preparedData['name'] = $menuName;
        }

        if (array_key_exists('id_parent', $preparedData)) {
            $parentValue = $preparedData['id_parent'];
            if ($parentValue === null || $parentValue === '') {
                $preparedData['id_parent'] = null;
            } elseif (! $this->isPositiveInteger($parentValue)) {
                throw new InvalidArgumentException('Parent menu identifier must be a positive integer.');
            } else {
                $preparedData['id_parent'] = (int) $parentValue;
            }
        }

        if (array_key_exists('position', $preparedData)) {
            if (! $this->isNonNegativeInteger($preparedData['position'])) {
                throw new InvalidArgumentException('Menu position must be zero or greater.');
            }
            $preparedData['position'] = (int) $preparedData['position'];
        } elseif (! $partial) {
            $preparedData['position'] = 0;
        }

        foreach (['is_active' => 1, 'target_blank' => 0] as $booleanField => $defaultValue) {
            if (array_key_exists($booleanField, $preparedData)) {
                if (! in_array((string) $preparedData[$booleanField], ['0', '1'], true)) {
                    throw new InvalidArgumentException(sprintf('%s must be either 0 or 1.', $booleanField));
                }
                $preparedData[$booleanField] = (int) $preparedData[$booleanField];
            } elseif (! $partial) {
                $preparedData[$booleanField] = $defaultValue;
            }
        }

        if (array_key_exists('url', $preparedData)) {
            $preparedData['url'] = $this->normalizeUrl($preparedData['url'], (int) ($preparedData['target_blank'] ?? 0));
        }

        if (array_key_exists('icon', $preparedData)) {
            $preparedData['icon'] = $this->normalizeIcon($preparedData['icon']);
        }

        return $preparedData;
    }

    /** @return array<int, array<string, mixed>> */
    public function listMenus(): array
    {
        return $this->model()->findAllOrdered();
    }

    /** @return array<string, mixed>|null */
    public function findMenu(int $menuIdentifier): ?array
    {
        if ($menuIdentifier <= 0) {
            return null;
        }

        $menuRow = $this->model()->find($menuIdentifier);

        return is_array($menuRow) ? $menuRow : null;
    }

    /** @param array<string, mixed> $menuData */
    public function createMenu(array $menuData, int $actorIdentifier): int
    {
        $this->assertActorIdentifier($actorIdentifier);
        $preparedData = $this->prepareMenuData($menuData);
        $this->assertValidStoredParent(0, $preparedData['id_parent'] ?? null);
        $preparedData['created_by'] = $actorIdentifier;
        $preparedData['updated_by'] = $actorIdentifier;

        $insertedIdentifier = $this->model()->insert($preparedData, true);
        if ($insertedIdentifier === false) {
            throw new RuntimeException('Failed to create the menu.');
        }

        return (int) $insertedIdentifier;
    }

    /** @param array<string, mixed> $menuData */
    public function updateMenu(int $menuIdentifier, array $menuData, int $actorIdentifier): void
    {
        $this->assertActorIdentifier($actorIdentifier);
        $storedMenu = $this->findMenu($menuIdentifier);
        if ($storedMenu === null) {
            throw new InvalidArgumentException('Menu does not exist.');
        }

        $preparedData = $this->prepareMenuData($menuData, true);
        if ($preparedData === []) {
            throw new InvalidArgumentException('No editable menu fields were supplied.');
        }

        $parentIdentifier = array_key_exists('id_parent', $preparedData)
            ? $preparedData['id_parent']
            : ($storedMenu['id_parent'] === null ? null : (int) $storedMenu['id_parent']);
        $this->assertValidStoredParent($menuIdentifier, $parentIdentifier);
        $preparedData['updated_by'] = $actorIdentifier;

        if ($this->model()->update($menuIdentifier, $preparedData) === false) {
            throw new RuntimeException('Failed to update the menu.');
        }
    }

    public function deleteMenu(int $menuIdentifier, int $actorIdentifier): void
    {
        $this->assertActorIdentifier($actorIdentifier);
        if ($this->findMenu($menuIdentifier) === null) {
            throw new InvalidArgumentException('Menu does not exist.');
        }

        if ($this->model()->where('id_parent', $menuIdentifier)->countAllResults() > 0) {
            throw new InvalidArgumentException('A menu with child menus cannot be deleted.');
        }

        $this->model()->update($menuIdentifier, ['deleted_by' => $actorIdentifier]);
        if ($this->model()->delete($menuIdentifier) === false) {
            throw new RuntimeException('Failed to delete the menu.');
        }
    }

    /** @param array<int, array{menu_id: int|string, position: int|string}> $orderingPayload @return array<int, array{menu_id: int, position: int}> */
    public function validateReorderPayload(array $orderingPayload): array
    {
        if ($orderingPayload === []) {
            throw new InvalidArgumentException('Reorder payload must not be empty.');
        }

        $normalizedOrdering = [];
        $seenMenuIdentifiers = [];
        $seenPositions = [];
        foreach ($orderingPayload as $orderingEntry) {
            if (! is_array($orderingEntry)
                || ! array_key_exists('menu_id', $orderingEntry)
                || ! array_key_exists('position', $orderingEntry)
                || ! $this->isPositiveInteger($orderingEntry['menu_id'])
                || ! $this->isNonNegativeInteger($orderingEntry['position'])
            ) {
                throw new InvalidArgumentException('Each reorder entry requires a positive menu_id and a non-negative position.');
            }

            $menuIdentifier = (int) $orderingEntry['menu_id'];
            $menuPosition = (int) $orderingEntry['position'];
            if (isset($seenMenuIdentifiers[$menuIdentifier])) {
                throw new InvalidArgumentException('Duplicate menu_id detected in reorder payload.');
            }
            if (isset($seenPositions[$menuPosition])) {
                throw new InvalidArgumentException('Duplicate position detected in reorder payload.');
            }

            $seenMenuIdentifiers[$menuIdentifier] = true;
            $seenPositions[$menuPosition] = true;
            $normalizedOrdering[] = ['menu_id' => $menuIdentifier, 'position' => $menuPosition];
        }

        $storedMenus = $this->model()->whereIn('id', array_keys($seenMenuIdentifiers))->findAll();
        if (count($storedMenus) !== count($normalizedOrdering)) {
            throw new InvalidArgumentException('One or more menu identifiers do not exist.');
        }

        $parentKeys = [];
        foreach ($storedMenus as $storedMenu) {
            $parentKeys[$storedMenu['id_parent'] === null ? 'root' : 'parent-' . (int) $storedMenu['id_parent']] = true;
        }
        if (count($parentKeys) !== 1) {
            throw new InvalidArgumentException('Reorder payload must contain menus that share one parent.');
        }

        return $normalizedOrdering;
    }

    /** @param array<int, array{menu_id: int|string, position: int|string}> $orderingPayload @return array<int, array{menu_id: int, position: int}> */
    public function reorder(array $orderingPayload, int $actorIdentifier): array
    {
        $this->assertActorIdentifier($actorIdentifier);
        $normalizedOrdering = $this->validateReorderPayload($orderingPayload);
        $databaseConnection = $this->model()->db();
        $databaseConnection->transBegin();

        try {
            foreach ($normalizedOrdering as $orderingEntry) {
                if ($this->model()->update($orderingEntry['menu_id'], [
                    'position' => $orderingEntry['position'],
                    'updated_by' => $actorIdentifier,
                ]) === false) {
                    throw new RuntimeException('Failed to persist menu ordering.');
                }
            }

            if ($databaseConnection->transStatus() === false) {
                throw new RuntimeException('Failed to persist menu ordering.');
            }
            $databaseConnection->transCommit();
        } catch (Throwable $transactionFailure) {
            $databaseConnection->transRollback();
            throw $transactionFailure;
        }

        return $normalizedOrdering;
    }

    private function assertValidStoredParent(int $menuIdentifier, ?int $parentIdentifier): void
    {
        (new FMSAdminMenuTreeService())->assertValidParent(
            $this->model()->findAllOrdered(),
            $menuIdentifier,
            $parentIdentifier,
        );
    }

    private function normalizeUrl(mixed $urlValue, int $targetBlank): ?string
    {
        $menuUrl = trim((string) $urlValue);
        if ($menuUrl === '') {
            return null;
        }
        if ($menuUrl === '#') {
            return '#';
        }
        if (mb_strlen($menuUrl, 'UTF-8') > 100 || preg_match('/[\x00-\x20<>"\']/', $menuUrl) === 1) {
            throw new InvalidArgumentException('Menu URL is invalid.');
        }
        if (str_starts_with($menuUrl, '//') || preg_match('/^(?:javascript|data|vbscript):/i', $menuUrl) === 1) {
            throw new InvalidArgumentException('Menu URL scheme is not allowed.');
        }
        if (preg_match('#^https?://#i', $menuUrl) === 1) {
            if ($targetBlank !== 1 || filter_var($menuUrl, FILTER_VALIDATE_URL) === false) {
                throw new InvalidArgumentException('External URL requires target_blank=1 and a valid HTTP(S) URL.');
            }
            return $menuUrl;
        }
        if (! preg_match('#^/?[A-Za-z0-9][A-Za-z0-9/_\-.?=&%]*$#', $menuUrl)) {
            throw new InvalidArgumentException('Menu URL is invalid.');
        }

        return ltrim($menuUrl, '/');
    }

    private function normalizeIcon(mixed $iconValue): ?string
    {
        $menuIcon = trim((string) $iconValue);
        if ($menuIcon === '') {
            return null;
        }
        if (mb_strlen($menuIcon, 'UTF-8') > 10000) {
            throw new InvalidArgumentException('Menu icon is too long.');
        }
        if (str_starts_with(strtolower($menuIcon), '<svg')) {
            /* Izinkan namespace w3.org standar untuk SVG duotone murni */
            $sanitizedForCheck = (string) preg_replace('#xmlns(?::[a-z0-9_-]+)?="https?://www\.w3\.org/[^"]*"#i', '', $menuIcon);

            if (! preg_match('/^<svg\b[^>]*>[\s\S]*<\/svg>$/i', $menuIcon)
                || preg_match('/<\s*(?:script|foreignObject|iframe|object|embed|style)\b/i', $menuIcon)
                || preg_match('/\son[a-z]+\s*=/i', $menuIcon)
                || preg_match('/(?:javascript:|data:|https?:\/\/)/i', $sanitizedForCheck)
            ) {
                throw new InvalidArgumentException('Menu icon SVG is not allowed.');
            }
            return $menuIcon;
        }
        if (! preg_match('/^[A-Za-z0-9_-]+(?:\s+[A-Za-z0-9_-]+)*$/', $menuIcon)) {
            throw new InvalidArgumentException('Menu icon class is invalid.');
        }

        return $menuIcon;
    }

    private function assertActorIdentifier(int $actorIdentifier): void
    {
        if ($actorIdentifier <= 0) {
            throw new InvalidArgumentException('Actor identifier must be a positive integer.');
        }
    }

    private function isPositiveInteger(mixed $value): bool
    {
        return (is_int($value) && $value > 0)
            || (is_string($value) && ctype_digit($value) && (int) $value > 0);
    }

    private function isNonNegativeInteger(mixed $value): bool
    {
        return (is_int($value) && $value >= 0)
            || (is_string($value) && ctype_digit($value));
    }

    private function model(): FMSAdminMenuModel
    {
        return $this->adminMenuModel;
    }
}
