<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Seed storage_settings dengan semua konfigurasi runtime yang sebelumnya
 * dibaca dari .env (storageRoot, bucket, S3, dll).
 *
 * Idempoten: hanya insert jika belum ada; update jika sudah ada.
 */
final class SeedStorageConfigSettings extends Migration
{
    public function up(): void
    {
        $db = $this->db;
        $now = date('Y-m-d H:i:s');

        $defaults = [
            /* ── Storage path ──────────────────────────────────── */
            'storage_driver'              => 'local',
            'storage_root'                => WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'fms-buckets',
            'bucket_name'                => 'fms-private',
            'object_key_prefix'           => 'fms-uploads',
            'create_bucket_automatically' => 'true',

            /* ── S3 ───────────────────────────────────────────── */
            's3_region'                  => '',
            's3_access_key_id'           => '',
            's3_secret_access_key'       => '',
            's3_endpoint_host'           => '',
            's3_path_style'              => 'true',
            's3_use_tls'                 => 'true',

            /* ── Presigned URL (HMAC) ─────────────────────────── */
            'local_download_endpoint'     => '',   /* diisi otomatis dari site_url() bila kosong */
            'local_hmac_key'             => bin2hex(random_bytes(32)),
            'local_hmac_key_identifier'  => 'fms-download-' . substr(bin2hex(random_bytes(4)), 0, 8),
            'local_hmac_prev_key'        => '',
            'local_hmac_prev_key_identifier' => '',
            'presigned_ttl_seconds'       => '300',
            'presigned_max_ttl_seconds'  => '900',

            /* ── Image pipeline ────────────────────────────────── */
            'maximum_output_bytes'        => strval(8 * 1024 * 1024),    // 8 MB
            'maximum_width'              => '4096',
            'maximum_height'             => '4096',
            'maximum_pixels'             => strval(16 * 1024 * 1024),   // 16 Mpx
            'maximum_decoded_bytes'      => strval(128 * 1024 * 1024),  // 128 MB
            'webp_quality'               => '82',
            'maximum_files_per_request'  => '10',
        ];

        foreach ($defaults as $key => $defaultValue) {
            $existing = $db->table('storage_settings')
                ->where('setting_key', $key)
                ->get(1)->getRowArray();

            $row = [
                'setting_key'   => $key,
                'setting_value' => (string) $defaultValue,
                'updated_at'    => $now,
            ];

            if (is_array($existing)) {
                $row['created_at'] = $existing['created_at'];
                $db->table('storage_settings')->where('setting_key', $key)->update($row);
            } else {
                $row['created_at'] = $now;
                $db->table('storage_settings')->insert($row);
            }
        }
    }

    public function down(): void
    {
        $keys = [
            'storage_driver', 'storage_root', 'bucket_name', 'object_key_prefix',
            'create_bucket_automatically', 's3_region', 's3_access_key_id',
            's3_secret_access_key', 's3_endpoint_host', 's3_path_style', 's3_use_tls',
            'local_download_endpoint', 'local_hmac_key', 'local_hmac_key_identifier',
            'local_hmac_prev_key', 'local_hmac_prev_key_identifier',
            'presigned_ttl_seconds', 'presigned_max_ttl_seconds',
            'maximum_output_bytes', 'maximum_width', 'maximum_height',
            'maximum_pixels', 'maximum_decoded_bytes', 'webp_quality',
            'maximum_files_per_request',
        ];
        foreach ($keys as $key) {
            $this->db->table('storage_settings')->where('setting_key', $key)->delete();
        }
    }
}
