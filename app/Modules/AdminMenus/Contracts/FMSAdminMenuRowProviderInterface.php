<?php

namespace App\Modules\AdminMenus\Contracts;

/**
 * Menu-row source for sidebar composition.
 *
 * The access service only needs the ordered, active-filtered menu rows, so it
 * depends on this contract instead of the concrete model. Storage stays behind
 * the implementation and tests can supply rows without a database.
 */
interface FMSAdminMenuRowProviderInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAllOrdered(bool $activeOnly = false): array;
}
