<?php

namespace App\Modules\Privileges\Models;

use App\Core\FMSModel;

final class FMSMenuPermissionModel extends FMSModel
{
    protected $table = 't_menu_permissions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
    protected $allowedFields = ['menu_id', 'permission_id', 'created_by', 'created_at'];
}
