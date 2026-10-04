<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Backfill akun sumber admin agar langsung terverifikasi.
 * Akun registrasi publik tidak disentuh.
 */
final class BackfillAdminCreatedEmailVerification extends Migration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('c_auth_settings')->update([
            'admin_created_email_verification_required' => 0,
            'updated_at' => $now,
        ]);

        $this->db->table('m_users')
            ->where('registration_source', 'admin')
            ->where('email_verified_at IS NULL', null, false)
            ->update(['email_verified_at' => $now]);
    }

    public function down(): void
    {
        /* Backfill bersifat satu arah: tidak menghapus timestamp verifikasi. */
    }
}
