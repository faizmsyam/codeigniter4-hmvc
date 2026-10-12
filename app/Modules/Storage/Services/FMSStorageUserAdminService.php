<?php
namespace App\Modules\Storage\Services;

use App\Modules\Storage\Models\FMSStorageUserModel;
use InvalidArgumentException;

final class FMSStorageUserAdminService
{
    public function users(): array
    {
        $db = db_connect();
        $rows = $db->table('storage_users')->orderBy('username', 'ASC')->get()->getResultArray();
        $assignments = $db->table('storage_user_roles')->get()->getResultArray();
        $roles = $this->roles();

        $rolesById = [];
        foreach ($roles as $role) {
            $rolesById[(int) $role['id']] = $role;
        }

        $map = [];
        foreach ($assignments as $assignment) {
            $role = $rolesById[(int) $assignment['role_id']] ?? null;
            if ($role === null) continue;
            $map[(int) $assignment['user_id']][] = [
                'id' => (int) $role['id'],
                'name' => (string) $role['name'],
            ];
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => (int) $row['id'],
                'uuid' => (string) $row['uuid'],
                'username' => (string) $row['username'],
                'email' => $row['email'] !== null ? (string) $row['email'] : null,
                'full_name' => $row['full_name'] !== null ? (string) $row['full_name'] : null,
                'status' => (string) $row['status'],
                'is_super_admin' => (int) $row['is_super_admin'] === 1,
                'linked_backend_user' => $row['source_backend_user_id'] !== null,
                'last_login_at' => $row['last_login_at'],
                'roles' => $map[(int) $row['id']] ?? [],
            ];
        }

        return $result;
    }

    public function roles(): array
    {
        $rows = db_connect()->table('storage_roles')->orderBy('name', 'ASC')->get()->getResultArray();
        $result = [];
        foreach ($rows as $row) {
            $permissions = json_decode((string) ($row['permissions_json'] ?? '[]'), true);
            $result[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'permissions' => is_array($permissions) ? array_values($permissions) : [],
            ];
        }
        return $result;
    }

    public function permissions(): array
    {
        $rows = db_connect()->table('storage_permissions')->orderBy('permission_key', 'ASC')->get()->getResultArray();
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'key' => (string) $row['permission_key'],
                'description' => $row['description'] !== null ? (string) $row['description'] : '',
            ];
        }
        return $result;
    }

    public function createUser(array $data): array
    {
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        if (preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username) !== 1) {
            throw new InvalidArgumentException('Username hanya boleh huruf, angka, titik, garis bawah, atau strip (3–100 karakter).');
        }
        if (strlen($password) < 12) {
            throw new InvalidArgumentException('Password minimal 12 karakter.');
        }

        $model = new FMSStorageUserModel();
        if ($model->where('LOWER(username)', strtolower($username))->countAllResults() > 0) {
            throw new InvalidArgumentException('Username sudah digunakan.');
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Format email tidak valid.');
        }
        if ($email !== '' && $model->where('LOWER(email)', strtolower($email))->countAllResults() > 0) {
            throw new InvalidArgumentException('Email sudah digunakan.');
        }

        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $db->table('storage_users')->insert([
            'uuid' => $this->uuidV4(),
            'username' => $username,
            'email' => $email !== '' ? $email : null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => trim((string) ($data['full_name'] ?? '')) ?: $username,
            'status' => in_array(($data['status'] ?? 'active'), ['active', 'suspended'], true) ? (string) $data['status'] : 'active',
            'is_super_admin' => 0,
            'password_changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = (int) $db->insertID();

        $this->syncRoles($userId, $data['role_ids'] ?? []);

        return ['id' => $userId, 'username' => $username];
    }

    public function updateUser(int $userId, array $data): array
    {
        $model = new FMSStorageUserModel();
        $existing = $model->find($userId);
        if (! is_array($existing)) {
            throw new InvalidArgumentException('User tidak ditemukan.');
        }

        $update = ['updated_at' => date('Y-m-d H:i:s')];

        if (array_key_exists('full_name', $data)) {
            $update['full_name'] = trim((string) $data['full_name']) ?: $existing['username'];
        }

        if (array_key_exists('email', $data)) {
            $email = trim((string) $data['email']);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException('Format email tidak valid.');
            }
            if ($email !== '' && $model->where('LOWER(email)', strtolower($email))->where('id !=', $userId)->countAllResults() > 0) {
                throw new InvalidArgumentException('Email sudah digunakan user lain.');
            }
            $update['email'] = $email !== '' ? $email : null;
        }

        if (array_key_exists('status', $data)) {
            $status = (string) $data['status'];
            if (! in_array($status, ['active', 'suspended'], true)) {
                throw new InvalidArgumentException('Status tidak valid.');
            }
            $update['status'] = $status;
        }

        if (! empty($data['password'])) {
            if (strlen((string) $data['password']) < 12) {
                throw new InvalidArgumentException('Password minimal 12 karakter.');
            }
            $update['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
            $update['password_changed_at'] = date('Y-m-d H:i:s');
        }

        $model->update($userId, $update);

        if (array_key_exists('role_ids', $data)) {
            $this->syncRoles($userId, $data['role_ids']);
        }

        return ['id' => $userId];
    }

    public function deleteUser(int $userId): void
    {
        $model = new FMSStorageUserModel();
        $existing = $model->find($userId);
        if (! is_array($existing)) {
            throw new InvalidArgumentException('User tidak ditemukan.');
        }
        if ((int) ($existing['is_super_admin'] ?? 0) === 1) {
            throw new InvalidArgumentException('Super administrator tidak dapat dihapus.');
        }

        $db = db_connect();
        $db->table('storage_user_roles')->where('user_id', $userId)->delete();
        $db->table('storage_sessions')->where('user_id', $userId)->delete();
        $model->delete($userId);
    }

    public function saveRole(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if (preg_match('/^[a-z0-9._-]{3,100}$/', $name) !== 1) {
            throw new InvalidArgumentException('Nama role harus huruf kecil, angka, titik, garis bawah, atau strip (3–100 karakter).');
        }

        $allowed = array_column($this->permissions(), 'key');
        $permissions = array_values(array_intersect(
            array_map('strval', (array) ($data['permissions'] ?? [])),
            $allowed
        ));

        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $existing = $db->table('storage_roles')->where('name', $name)->get(1)->getRowArray();

        if (is_array($existing)) {
            if ($name === 'super_admin') {
                $permissions = $allowed;
            }
            $db->table('storage_roles')->where('id', (int) $existing['id'])->update([
                'permissions_json' => json_encode($permissions, JSON_THROW_ON_ERROR),
                'updated_at' => $now,
            ]);
            return ['id' => (int) $existing['id'], 'name' => $name];
        }

        $db->table('storage_roles')->insert([
            'name' => $name,
            'permissions_json' => json_encode($permissions, JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ['id' => (int) $db->insertID(), 'name' => $name];
    }

    public function deleteRole(int $roleId): void
    {
        $db = db_connect();
        $role = $db->table('storage_roles')->where('id', $roleId)->get(1)->getRowArray();
        if (! is_array($role)) {
            throw new InvalidArgumentException('Role tidak ditemukan.');
        }
        if ((string) $role['name'] === 'super_admin') {
            throw new InvalidArgumentException('Role super_admin tidak dapat dihapus.');
        }

        $db->table('storage_user_roles')->where('role_id', $roleId)->delete();
        $db->table('storage_roles')->where('id', $roleId)->delete();
    }

    private function syncRoles(int $userId, array $roleIds): void
    {
        $db = db_connect();
        $valid = array_map(
            'intval',
            array_column($db->table('storage_roles')->select('id')->get()->getResultArray(), 'id')
        );

        $clean = [];
        foreach ($roleIds as $roleId) {
            $roleId = (int) $roleId;
            if (in_array($roleId, $valid, true) && ! in_array($roleId, $clean, true)) {
                $clean[] = $roleId;
            }
        }

        $db->table('storage_user_roles')->where('user_id', $userId)->delete();
        foreach ($clean as $roleId) {
            $db->table('storage_user_roles')->insert(['user_id' => $userId, 'role_id' => $roleId]);
        }
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);
        $hex = bin2hex($bytes);
        return implode('-', [
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        ]);
    }
}
