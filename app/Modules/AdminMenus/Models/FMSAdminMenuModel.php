<?php

namespace App\Modules\AdminMenus\Models;

use App\Core\FMSModel;
use App\Modules\AdminMenus\Contracts\FMSAdminMenuRowProviderInterface;

final class FMSAdminMenuModel extends FMSModel implements FMSAdminMenuRowProviderInterface
{
    protected $table = 'c_menus';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'id_parent',
        'name',
        'url',
        'icon',
        'position',
        'is_active',
        'target_blank',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAllOrdered(bool $activeOnly = false): array
    {
        if ($activeOnly) {
            $this->where('is_active', 1);
        }

        return $this
            ->orderBy('id_parent', 'ASC')
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
