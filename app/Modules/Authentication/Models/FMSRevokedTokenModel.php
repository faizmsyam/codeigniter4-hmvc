<?php

namespace App\Modules\Authentication\Models;

use App\Core\FMSModel;

final class FMSRevokedTokenModel extends FMSModel
{
    protected $table            = 't_api_revoked_tokens';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'jti_hash',
        'expires_at',
        'revoked_at',
        'created_at',
    ];
}
