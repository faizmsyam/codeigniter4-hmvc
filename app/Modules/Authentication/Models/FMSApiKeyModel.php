<?php

namespace App\Modules\Authentication\Models;

use App\Core\FMSModel;

final class FMSApiKeyModel extends FMSModel
{
    protected $table            = 'c_api_keys';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $allowedFields    = [
        'key_id',
        'label',
        'secret_hash',
        'scopes_json',
        'environment',
        'expires_at',
        'revoked_at',
        'last_used_at',
        'last_used_ip_hash',
        'created_by',
        'updated_by',
    ];
}
