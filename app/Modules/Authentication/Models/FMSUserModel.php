<?php

namespace App\Modules\Authentication\Models;

use App\Core\FMSModel;

final class FMSUserModel extends FMSModel
{
    protected $table          = 'm_users';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';
    protected $allowedFields  = [
        'uuid',
        'username',
        'username_normalized',
        'email',
        'email_normalized',
        'password_hash',
        'full_name',
        'phone',
        'avatar',
        'status',
        'registration_source',
        'email_verified_at',
        'password_changed_at',
        'failed_login_count',
        'locked_until',
        'last_login_at',
        'last_login_ip_hash',
        'must_change_password',
        'token_version',
        'session_version',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
