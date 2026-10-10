<?php
namespace App\Modules\Storage\Models;

use App\Core\FMSModel;

final class FMSStorageUserModel extends FMSModel
{
    protected $table = 'storage_users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $allowedFields = [
        'uuid','source_backend_user_id','username','email','password_hash','full_name',
        'status','is_super_admin','failed_login_count','locked_until','last_login_at',
        'last_login_ip_hash','password_changed_at','created_at','updated_at',
    ];

    public function findByIdentifier(string $identifier): ?array
    {
        $normalized = strtolower(trim($identifier));
        $row = $this->groupStart()
            ->where('LOWER(username)', $normalized)
            ->orWhere('LOWER(email)', $normalized)
            ->groupEnd()->where('status', 'active')->first();
        return is_array($row) ? $row : null;
    }
}
