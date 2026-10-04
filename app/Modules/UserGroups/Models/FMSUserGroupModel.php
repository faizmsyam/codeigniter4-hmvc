<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\UserGroups\Models;

use App\Core\FMSModel;

final class FMSUserGroupModel extends FMSModel
{
    protected $table          = 'c_group_users';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'name',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function tableName(): string
    {
        return $this->table;
    }
}
