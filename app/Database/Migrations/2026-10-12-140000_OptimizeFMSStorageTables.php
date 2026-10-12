<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Optimasi tabel FMS Storage:
 *   1. Foreign key + ON DELETE yang sesuai untuk relasi storage_*.
 *   2. Index tambahan untuk query yang sering dipakai (audit log, session, share).
 *   3. Kolom baru: storage_audit_logs.user_agent_hash (pelacakan sesi lebih baik).
 *
 * Semua ALTER memakai prefix DB (fms_) lewat $this->db->prefixTable().
 * Idempoten: setiap operasi dibungkus try/catch agar aman diulang.
 */
final class OptimizeFMSStorageTables extends Migration
{
    public function up(): void
    {
        $this->addForeignKeys();
        $this->addIndexes();
        $this->addColumns();
    }

    public function down(): void
    {
        $this->dropForeignKeys();
        $this->dropIndexes();
        $this->dropColumns();
    }

    /* ── Foreign keys ─────────────────────────────────────────── */

    private function addForeignKeys(): void
    {
        /* storage_user_roles -> storage_users / storage_roles (cascade) */
        $this->safeFk('storage_user_roles', 'fk_sur_user', 'user_id', 'storage_users', 'id', 'CASCADE');
        $this->safeFk('storage_user_roles', 'fk_sur_role', 'role_id', 'storage_roles', 'id', 'CASCADE');

        /* storage_sessions -> storage_users (cascade: hapus user = hapus sesi) */
        $this->safeFk('storage_sessions', 'fk_sses_user', 'user_id', 'storage_users', 'id', 'CASCADE');

        /* storage_shares -> storage_users (SET NULL: share tetap ada, created_by jadi null) */
        $this->safeFk('storage_shares', 'fk_sshare_user', 'created_by', 'storage_users', 'id', 'SET NULL');

        /* storage_audit_logs -> storage_users (SET NULL: log audit tidak boleh hilang) */
        $this->safeFk('storage_audit_logs', 'fk_saudit_user', 'user_id', 'storage_users', 'id', 'SET NULL');
    }

    private function dropForeignKeys(): void
    {
        $map = [
            'storage_user_roles'  => ['fk_sur_user', 'fk_sur_role'],
            'storage_sessions'    => ['fk_sses_user'],
            'storage_shares'      => ['fk_sshare_user'],
            'storage_audit_logs'  => ['fk_saudit_user'],
        ];
        foreach ($map as $table => $names) {
            foreach ($names as $name) {
                $this->safeQuery("ALTER TABLE `{$this->t($table)}` DROP FOREIGN KEY `{$name}`");
            }
        }
    }

    /* ── Index tambahan ───────────────────────────────────────── */

    private function addIndexes(): void
    {
        /* Audit log: filter per user & urut waktu */
        $this->safeQuery("ALTER TABLE `{$this->t('storage_audit_logs')}` ADD INDEX `idx_saudit_user_created` (`user_id`, `created_at`)");

        /* Audit log: filter per event */
        $this->safeQuery("ALTER TABLE `{$this->t('storage_audit_logs')}` ADD INDEX `idx_saudit_event` (`event`)");

        /* Audit log: cari per object (prefix index — varchar(1024) terlalu panjang utk key penuh) */
        $this->safeQuery("ALTER TABLE `{$this->t('storage_audit_logs')}` ADD INDEX `idx_saudit_object` (`object_key`(191))");

        /* Users: lookup by status (list user aktif) */
        $this->safeQuery("ALTER TABLE `{$this->t('storage_users')}` ADD INDEX `idx_susers_status` (`status`)");

        /* Sessions: cleanup yang expired/revoked sekaligus */
        $this->safeQuery("ALTER TABLE `{$this->t('storage_sessions')}` ADD INDEX `idx_sses_expires_revoked` (`expires_at`, `revoked_at`)");
    }

    private function dropIndexes(): void
    {
        $map = [
            'storage_audit_logs' => ['idx_saudit_user_created', 'idx_saudit_event', 'idx_saudit_object'],
            'storage_users'      => ['idx_susers_status'],
            'storage_sessions'   => ['idx_sses_expires_revoked'],
        ];
        foreach ($map as $table => $names) {
            foreach ($names as $name) {
                $this->safeQuery("ALTER TABLE `{$this->t($table)}` DROP INDEX `{$name}`");
            }
        }
    }

    /* ── Kolom baru ───────────────────────────────────────────── */

    private function addColumns(): void
    {
        $this->safeQuery("ALTER TABLE `{$this->t('storage_audit_logs')}` ADD COLUMN `user_agent_hash` CHAR(64) NULL AFTER `ip_hash`");
    }

    private function dropColumns(): void
    {
        $this->safeQuery("ALTER TABLE `{$this->t('storage_audit_logs')}` DROP COLUMN `user_agent_hash`");
    }

    /* ── Helper ───────────────────────────────────────────────── */

    /** Nama tabel lengkap dengan prefix DB (fms_). */
    private function t(string $table): string
    {
        return $this->db->DBPrefix . $table;
    }

    private function safeFk(string $table, string $name, string $column, string $refTable, string $refColumn, string $onDelete): void
    {
        $this->safeQuery(
            "ALTER TABLE `{$this->t($table)}` ADD CONSTRAINT `{$name}` FOREIGN KEY (`{$column}`) "
            . "REFERENCES `{$this->t($refTable)}` (`{$refColumn}`) ON DELETE {$onDelete} ON UPDATE CASCADE"
        );
    }

    private function safeQuery(string $sql): void
    {
        try {
            $this->db->query($sql);
        } catch (\Throwable $e) {
            /* sudah ada / tidak ada — abaikan agar idempoten */
        }
    }
}
