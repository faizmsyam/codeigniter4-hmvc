<?php
namespace App\Modules\Storage\Services;

final class FMSStorageAuthorizationService
{
    public const READ = 'storage.read';
    public const UPLOAD = 'storage.upload';
    public const EDIT = 'storage.edit';
    public const DELETE = 'storage.delete';
    public const SHARE = 'storage.share';
    public const USERS = 'storage.users';

    public function allows(?array $user, string $permission): bool
    {
        if (!is_array($user)) return false;
        if ((int)($user['is_super_admin'] ?? 0) === 1) return true;
        
        $db = db_connect();
        $userRoles = $db->table('storage_user_roles')
            ->where('user_id', (int)($user['id'] ?? 0))
            ->get()->getResultArray();
        
        if (empty($userRoles)) return false;
        
        $roleIds = array_column($userRoles, 'role_id');
        $roles = $db->table('storage_roles')
            ->whereIn('id', $roleIds)
            ->get()->getResultArray();
        
        foreach ($roles as $role) {
            $permissions = json_decode((string)($role['permissions_json'] ?? '[]'), true);
            if (is_array($permissions) && in_array($permission, $permissions, true)) {
                return true;
            }
        }
        
        return false;
    }
}
