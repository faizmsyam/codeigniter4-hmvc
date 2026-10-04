<?php

namespace App\Modules\Authentication\Models;

use App\Core\FMSModel;

final class FMSAuthAttemptModel extends FMSModel
{
    protected $table            = 't_api_auth_attempts';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'identifier_hash',
        'ip_hash',
        'result_code',
        'created_at',
    ];
}
