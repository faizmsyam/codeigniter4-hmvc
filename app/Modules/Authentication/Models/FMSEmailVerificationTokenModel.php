<?php

namespace App\Modules\Authentication\Models;

use App\Core\FMSModel;

final class FMSEmailVerificationTokenModel extends FMSModel
{
    protected $table            = 't_email_verification_tokens';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'user_id',
        'selector',
        'validator_hash',
        'purpose',
        'expires_at',
        'used_at',
        'revoked_at',
        'created_at',
    ];
}
