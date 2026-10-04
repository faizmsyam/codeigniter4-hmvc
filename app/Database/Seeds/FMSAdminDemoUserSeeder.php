<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Menyiapkan akun demo admin (username: admin / password: App12345)
 * dan mengikatnya ke grup Administrator (id=2) supaya halaman backend
 * tidak 404 karena akun tanpa grup.
 *
 * Idempoten: aman dijalankan berulang.
 */
final class FMSAdminDemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $database = $this->db;
        $currentTimestamp = date('Y-m-d H:i:s');
        $userTable = $database->table('m_users');
        $groupTable = $database->table('c_group_users');

        $database->transStart();

        /* Pastikan grup Administrator (id=2) ada. */
        $groupRow = $groupTable->where('id', 2)->get()->getRowArray();
        if ($groupRow === null) {
            $groupTable->insert([
                'name'       => 'Administrator',
                'is_active'  => 1,
                'created_by' => 1,
                'created_at' => $currentTimestamp,
            ]);
            $groupId = (int) $database->insertID();
        } else {
            $groupId = (int) $groupRow['id'];
            $groupTable->where('id', $groupId)->update([
                'is_active'  => 1,
                'deleted_by' => null,
                'deleted_at' => null,
            ]);
        }

        /* Buat atau perbarui akun admin. */
        $userRow = $userTable
            ->groupStart()
                ->where('username_normalized', 'admin')
                ->orWhere('email_normalized', 'admin')
            ->groupEnd()
            ->get()
            ->getRowArray();

        $userData = [
            'username'          => 'admin',
            'username_normalized' => 'admin',
            'email'             => 'admin',
            'email_normalized'  => 'admin',
            'password_hash'     => password_hash('App12345', PASSWORD_DEFAULT),
            'full_name'         => 'Administrator Demo',
            'status'            => 'active',
            'registration_source' => 'admin',
            'email_verified_at' => $currentTimestamp,
            'failed_login_count' => 0,
            'locked_until'      => null,
            'must_change_password' => 0,
            'token_version'     => 1,
            'session_version'   => 1,
            'deleted_by'        => null,
            'deleted_at'        => null,
        ];

        if ($userRow === null) {
            $userData['uuid'] = $this->generateUuidV4();
            $userData['created_at'] = $currentTimestamp;
            $userTable->insert($userData);
            $userId = (int) $database->insertID();
            $userTable->where('id', $userId)->update(['created_by' => $userId]);
        } else {
            $userId = (int) $userRow['id'];
            $userData['updated_by'] = $userId;
            $userData['updated_at'] = $currentTimestamp;
            $userTable->where('id', $userId)->update($userData);
        }

        /* Ikat admin ke grup Administrator. */
        $userGroupTable = $database->table('t_user_groups');
        $membershipRow = $userGroupTable
            ->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->get()
            ->getRowArray();

        if ($membershipRow === null) {
            $userGroupTable->insert([
                'user_id'     => $userId,
                'group_id'    => $groupId,
                'assigned_by' => $userId,
                'expires_at'  => null,
                'created_at'  => $currentTimestamp,
            ]);
        } else {
            $userGroupTable
                ->where('id', (int) $membershipRow['id'])
                ->update([
                    'assigned_by' => $userId,
                    'expires_at'  => null,
                ]);
        }

        $database->transComplete();

        if (! $database->transStatus()) {
            throw new RuntimeException('Gagal menyiapkan akun admin demo.');
        }
    }

    private function generateUuidV4(): string
    {
        $randomBytes = random_bytes(16);
        $randomBytes[6] = chr((ord($randomBytes[6]) & 0x0f) | 0x40);
        $randomBytes[8] = chr((ord($randomBytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($randomBytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}