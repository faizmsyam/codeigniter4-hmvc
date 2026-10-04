<?php

namespace App\Modules\Privileges\Models;

use App\Core\FMSModel;

final class FMSPermissionModel extends FMSModel
{
    protected $table = 'c_permissions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'uuid',
        'permission_key',
        'module_name',
        'action_name',
        'description',
        'is_active',
        'is_system',
        'source_menu_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
