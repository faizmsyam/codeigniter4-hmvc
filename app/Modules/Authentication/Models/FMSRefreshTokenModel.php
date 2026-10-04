<?php

namespace App\Modules\Authentication\Models;

use App\Core\FMSModel;

final class FMSRefreshTokenModel extends FMSModel
{
    protected $table            = 't_api_refresh_tokens';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'user_id',
        'token_family_id',
        'selector',
        'validator_hash',
        'issued_at',
        'expires_at',
        'used_at',
        'revoked_at',
        'replaced_by_id',
        'device_label',
        'ip_hash',
        'user_agent_hash',
        'created_at',
    ];
}
