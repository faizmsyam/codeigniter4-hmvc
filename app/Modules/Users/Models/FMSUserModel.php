<?php

namespace App\Modules\Users\Models;

use App\Core\FMSModel;

final class FMSUserModel extends FMSModel
{
    protected $table = 'm_users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
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
        'updated_by',
        'deleted_by',
    ];

    public function tableName(): string
    {
        return $this->table;
    }

    /** @return list<string> */
    public function allowedFieldNames(): array
    {
        return $this->allowedFields;
    }

    /** @return array<string, mixed>|null */
    public function findByUuid(string $userUuid, bool $includeDeleted = false): ?array
    {
        $model = $includeDeleted ? $this->withDeleted() : $this;
        $user = $model->where('uuid', $userUuid)->first();

        return is_array($user) ? $user : null;
    }
}
