<?php

namespace App\Modules\Privileges\Models;

use App\Core\FMSModel;

final class FMSGroupUserModel extends FMSModel
{
    protected $table = 'c_group_users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'uuid',
        'code',
        'name',
        'description',
        'is_system',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
