<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

final class FMSProgrammerSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $database = $this->db;
        $current_timestamp = date('Y-m-d H:i:s');
        $user_table = $database->table('m_users');
        $group_table = $database->table('c_group_users');
        $permission_table = $database->table('c_permissions');

        $database->transStart();

        $group_row = $group_table
            ->where('id', 1)
            ->get()
            ->getRowArray();

        if ($group_row === null) {
            $group_table->insert([
                'name' => 'Super Administrator',
                'is_active' => 1,
                'created_by' => 1,
                'created_at' => $current_timestamp,
            ]);
            $group_id = (int) $database->insertID();
        } else {
            $group_id = (int) $group_row['id'];
            $group_table->where('id', $group_id)->update([
                'name' => 'Super Administrator',
                'is_active' => 1,
                'deleted_by' => null,
                'deleted_at' => null,
            ]);
        }

        $user_row = $user_table
            ->groupStart()
                ->where('username_normalized', 'faizmsyam')
                ->orWhere('email_normalized', 'faizmsyam@gmail.com')
            ->groupEnd()
            ->get()
            ->getRowArray();

        $user_data = [
            'username' => 'faizmsyam',
            'username_normalized' => 'faizmsyam',
            'email' => 'faizmsyam@gmail.com',
            'email_normalized' => 'faizmsyam@gmail.com',
            'password_hash' => password_hash('1234567890', PASSWORD_DEFAULT),
            'full_name' => 'Faiz Muhammad Syam, S.Kom., M.TI., CH',
            'status' => 'active',
            'registration_source' => 'admin',
            'email_verified_at' => $current_timestamp,
            'failed_login_count' => 0,
            'locked_until' => null,
            'must_change_password' => 0,
            'token_version' => 1,
            'session_version' => 1,
            'deleted_by' => null,
            'deleted_at' => null,
        ];

        if ($user_row === null) {
            $user_data['uuid'] = $this->generateUuidV4();
            $user_data['created_at'] = $current_timestamp;
            $user_table->insert($user_data);
            $user_id = (int) $database->insertID();
            $user_table->where('id', $user_id)->update(['created_by' => $user_id]);
        } else {
            $user_id = (int) $user_row['id'];
            $user_data['updated_by'] = $user_id;
            $user_data['updated_at'] = $current_timestamp;
            $user_table->where('id', $user_id)->update($user_data);
        }

        $permission_row = $permission_table
            ->where('permission_key', '*')
            ->get()
            ->getRowArray();

        if ($permission_row === null) {
            $permission_table->insert([
                'uuid' => $this->generateUuidV4(),
                'permission_key' => '*',
                'module_name' => 'system',
                'action_name' => 'all',
                'description' => 'Akses penuh seluruh modul FMS.',
                'is_active' => 1,
                'created_by' => $user_id,
                'created_at' => $current_timestamp,
            ]);
            $permission_id = (int) $database->insertID();
        } else {
            $permission_id = (int) $permission_row['id'];
            $permission_table->where('id', $permission_id)->update([
                'is_active' => 1,
                'updated_by' => $user_id,
                'updated_at' => $current_timestamp,
                'deleted_by' => null,
                'deleted_at' => null,
            ]);
        }

        $user_group_table = $database->table('t_user_groups');
        $user_group_row = $user_group_table
            ->where('user_id', $user_id)
            ->where('group_id', $group_id)
            ->get()
            ->getRowArray();

        if ($user_group_row === null) {
            $user_group_table->insert([
                'user_id' => $user_id,
                'group_id' => $group_id,
                'assigned_by' => $user_id,
                'expires_at' => null,
                'created_at' => $current_timestamp,
            ]);
        } else {
            $user_group_table
                ->where('id', (int) $user_group_row['id'])
                ->update([
                    'assigned_by' => $user_id,
                    'expires_at' => null,
                ]);
        }

        $group_permission_table = $database->table('t_group_permissions');
        $group_permission_row = $group_permission_table
            ->where('group_id', $group_id)
            ->where('permission_id', $permission_id)
            ->get()
            ->getRowArray();

        if ($group_permission_row === null) {
            $group_permission_table->insert([
                'group_id' => $group_id,
                'permission_id' => $permission_id,
                'effect' => 'allow',
                'created_by' => $user_id,
                'created_at' => $current_timestamp,
            ]);
        } else {
            $group_permission_table
                ->where('id', (int) $group_permission_row['id'])
                ->update([
                    'effect' => 'allow',
                    'created_by' => $user_id,
                ]);
        }

        $database->transComplete();

        if (! $database->transStatus()) {
            throw new RuntimeException('Gagal membuat programmer super administrator.');
        }
    }

    private function generateUuidV4(): string
    {
        $random_bytes = random_bytes(16);
        $random_bytes[6] = chr((ord($random_bytes[6]) & 0x0f) | 0x40);
        $random_bytes[8] = chr((ord($random_bytes[8]) & 0x3f) | 0x80);
        $hexadecimal_uuid = bin2hex($random_bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hexadecimal_uuid, 0, 8),
            substr($hexadecimal_uuid, 8, 4),
            substr($hexadecimal_uuid, 12, 4),
            substr($hexadecimal_uuid, 16, 4),
            substr($hexadecimal_uuid, 20, 12),
        );
    }
}
