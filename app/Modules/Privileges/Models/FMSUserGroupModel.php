<?php

namespace App\Modules\Privileges\Models;

use App\Core\FMSModel;

final class FMSUserGroupModel extends FMSModel
{
    protected $table = 't_user_groups';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
    protected $allowedFields = ['user_id', 'group_id', 'assigned_by', 'expires_at', 'created_at'];
}
