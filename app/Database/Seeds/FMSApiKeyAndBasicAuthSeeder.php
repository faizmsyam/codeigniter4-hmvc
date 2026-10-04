<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Menyiapkan data awal untuk Basic Auth Client dan API Key default.
 * Idempoten: aman dijalankan berulang kali tanpa merusak data lama.
 */
final class FMSApiKeyAndBasicAuthSeeder extends Seeder
{
    public function run(): void
    {
        $db = $this->db;
        $ts = date('Y-m-d H:i:s');

        $db->transStart();

        /* ── 1. Default Basic Auth Client ───────────────────────── */
        $basicTable = $db->table('fms_c_basic_auth_clients');
        $defaultClient = 'fms_default_client';

        if ($basicTable->where('username', $defaultClient)->countAllResults() === 0) {
            $basicTable->insert([
                'username'        => $defaultClient,
                'label'           => 'FMS Default System Client',
                'password_hash'   => password_hash('FmsBasicAuthSecret@2026!', PASSWORD_DEFAULT),
                'scopes_json'     => json_encode(['*']),
                'environment'     => ENVIRONMENT,
                'failed_attempts' => 0,
                'created_by'      => 1,
                'created_at'      => $ts,
            ]);
        }

        /* ── 2. Default API Key ─────────────────────────────────── */
        $apiTable = $db->table('fms_c_api_keys');
        $defaultKeyId = 'fms_default_key_01';

        if ($apiTable->where('key_id', $defaultKeyId)->countAllResults() === 0) {
            /* Pepper HMAC standar dari .env atau default */
            $pepper = (string) (getenv('fms.api_key_pepper') ?: getenv('API_KEY_PEPPER') ?: 'fms-starter-secret-pepper-32bytes-key');
            $secretRaw = 'fms-default-secret-token-key-2026';
            $secretEncoded = rtrim(strtr(base64_encode($secretRaw), '+/', '-_'), '=');
            $secretHash = hash_hmac('sha256', $secretEncoded, $pepper);

            $apiTable->insert([
                'key_id'            => $defaultKeyId,
                'label'             => 'FMS Default API Key (Development/System)',
                'secret_hash'       => $secretHash,
                'scopes_json'       => json_encode(['*']),
                'environment'       => ENVIRONMENT,
                'ip_allowlist_json' => null,
                'created_by'        => 1,
                'created_at'        => $ts,
            ]);
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            throw new RuntimeException('FMSApiKeyAndBasicAuthSeeder gagal di-commit.');
        }
    }
}
