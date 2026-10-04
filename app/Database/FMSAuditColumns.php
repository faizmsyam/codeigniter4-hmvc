<?php

namespace App\Database;

use CodeIgniter\Database\RawSql;

/**
 * Kolom audit standar FMS agar semua tabel domain konsisten.
 */
trait FMSAuditColumns
{
    /**
     * @return array<string, array<string, mixed>>
     */
    protected function fmsAuditColumnDefinitions(): array
    {
        return [
            'created_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'deleted_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'deleted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function fmsSoftDeleteColumnDefinitions(): array
    {
        return [
            'deleted_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'deleted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ];
    }
}
