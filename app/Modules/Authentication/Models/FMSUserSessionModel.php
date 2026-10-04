<?php

namespace App\Modules\Authentication\Models;

use App\Core\FMSModel;

final class FMSUserSessionModel extends FMSModel
{
    protected $table            = 't_user_sessions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'session_uuid',
        'user_id',
        'token_family_id',
        'device_label',
        'ip_hash',
        'user_agent_hash',
        'last_activity_at',
        'expires_at',
        'revoked_at',
        'created_at',
    ];
}
