<?php

namespace App\Modules\Authentication\Models;

use App\Core\FMSModel;

final class FMSBasicAuthClientModel extends FMSModel
{
    protected $table            = 'c_basic_auth_clients';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $allowedFields    = [
        'username',
        'username_normalized',
        'label',
        'password_hash',
        'scopes_json',
        'environment',
        'expires_at',
        'locked_until',
        'failed_attempts',
        'revoked_at',
        'last_used_at',
        'last_used_ip_hash',
        'created_by',
        'updated_by',
    ];
}
