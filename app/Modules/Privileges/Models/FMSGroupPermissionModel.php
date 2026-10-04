<?php

namespace App\Modules\Privileges\Models;

use App\Core\FMSModel;

final class FMSGroupPermissionModel extends FMSModel
{
    protected $table = 't_group_permissions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
    protected $allowedFields = ['group_id', 'permission_id', 'effect', 'created_by', 'created_at'];
}
