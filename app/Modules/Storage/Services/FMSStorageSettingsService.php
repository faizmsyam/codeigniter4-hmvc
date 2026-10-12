<?php
namespace App\Modules\Storage\Services;

use App\Config\FMSUploads;

/**
 * Pengaturan Storage yang bisa diubah dari UI Storage Settings.
 *
 * Single source of truth untuk SEMUA konfigurasi runtime Storage:
 *   - MIME whitelist      (allowed_mime_types)
 *   - Storage driver     (local / s3)
 *   - Bucket & path      (storage_root, bucket_name, object_key_prefix)
 *   - S3 credentials     (region, access_key, secret_key, endpoint, dll.)
 *   - Image pipeline     (max_output_bytes, webp_quality, dll.)
 *   - Presigned URL      (HMAC key, TTL, dll.)
 *
 * Nilai tersimpan di tabel storage_settings; .env / FMSUploads hanya fallback.
 */
final class FMSStorageSettingsService
{
    private const KEY_ALLOWED_MIME = 'allowed_mime_types';

    /** Semua kunci setting yang disimpan di storage_settings. */
    private const ALL_KEYS = [
        /* Storage path */
        'storage_driver', 'storage_root', 'bucket_name', 'object_key_prefix', 'create_bucket_automatically',
        /* S3 */
        's3_region', 's3_access_key_id', 's3_secret_access_key', 's3_endpoint_host', 's3_path_style', 's3_use_tls',
        /* Presigned / download */
        'local_download_endpoint', 'local_hmac_key', 'local_hmac_key_identifier',
        'local_hmac_prev_key', 'local_hmac_prev_key_identifier',
        'presigned_ttl_seconds', 'presigned_max_ttl_seconds',
        /* Image pipeline */
        'maximum_output_bytes', 'maximum_width', 'maximum_height',
        'maximum_pixels', 'maximum_decoded_bytes', 'webp_quality',
        'maximum_files_per_request',
        /* MIME (handled separately) */
        'allowed_mime_types',
    ];

    public function __construct()
    {
        /* Tabel dibuat oleh migrasi; jangan throw di constructor agar aman
           saat boot awal. Method get/all/set akan return kosong bila belum ada. */
    }

    private function tableReady(): bool
    {
        return db_connect()->tableExists('storage_settings');
    }

    /**
     * Ambil satu nilai setting. Return null kalau tidak ada.
     */
    public function get(string $key): ?string
    {
        if (! $this->tableReady()) return null;
        if ($key === self::KEY_ALLOWED_MIME) {
            return json_encode($this->allowedMimeTypes(), JSON_UNESCAPED_SLASHES);
        }
        $row = db_connect()->table('storage_settings')
            ->where('setting_key', $key)->get(1)->getRowArray();
        return is_array($row) ? ($row['setting_value'] ?? null) : null;
    }

    /**
     * Ambil semua setting sebagai array key => value (string).
     */
    public function all(): array
    {
        if (! $this->tableReady()) return [];
        $rows = db_connect()->table('storage_settings')->get()->getResultArray();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'] ?? '';
        }
        return $result;
    }

    /**
     * Sama seperti all() tapi secret fields di-mask untuk JSON output.
     */
    public function allMasked(): array
    {
        $all = $this->all();
        $secretKeys = [
            's3_secret_access_key', 'local_hmac_key', 'local_hmac_prev_key',
        ];
        foreach ($secretKeys as $k) {
            if (isset($all[$k]) && $all[$k] !== '') {
                $all[$k] = '********';
            }
        }
        return $all;
    }

    /**
     * Simpan satu setting.
     */
    public function set(string $key, string $value): void
    {
        if (! $this->tableReady()) return;

        /* Normalisasi S3 endpoint host: terima URL penuh (https://host/path),
           simpan sebagai host polos. Skema menentukan s3_use_tls otomatis. */
        if ($key === 's3_endpoint_host') {
            [$host, $tls] = $this->normalizeEndpointHost($value);
            $value = $host;
            if ($tls !== null) {
                $this->writeRaw('s3_use_tls', $tls ? 'true' : 'false');
            }
        }

        $this->writeRaw($key, $value);
    }

    /**
     * Ubah input endpoint jadi host polos + deteksi skema TLS.
     * "https://assets-x.go.id/" -> ["assets-x.go.id", true]
     * "s3.amazonaws.com"        -> ["s3.amazonaws.com", null]
     *
     * @return array{0:string, 1:?bool}
     */
    private function normalizeEndpointHost(string $value): array
    {
        $value = trim($value);
        $tls = null;

        if (str_contains($value, '://')) {
            $host = parse_url($value, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
                if ($scheme === 'https') $tls = true;
                elseif ($scheme === 'http') $tls = false;
                $port = parse_url($value, PHP_URL_PORT);
                $value = $host . ($port ? ':' . $port : '');
            } else {
                $value = preg_replace('#^[a-z][a-z0-9+.\-]*://#i', '', $value) ?? $value;
            }
        }

        /* Buang path/query/fragment/spasi; sisakan host[:port]. */
        $value = preg_replace('#[/\s].*$#', '', $value) ?? $value;
        return [rtrim($value, '/'), $tls];
    }

    private function writeRaw(string $key, string $value): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = db_connect()->table('storage_settings')
            ->where('setting_key', $key)->get(1)->getRowArray();

        $row = ['setting_key' => $key, 'setting_value' => $value, 'updated_at' => $now];
        if (is_array($existing)) {
            $row['created_at'] = $existing['created_at'];
            db_connect()->table('storage_settings')->where('setting_key', $key)->update($row);
        } else {
            $row['created_at'] = $now;
            db_connect()->table('storage_settings')->insert($row);
        }
    }

    /**
     * Inject semua nilai dari storage_settings ke objek FMSUploads.
     * Panggil di service yang butuh config runtime, SETELAH DB ready.
     *
     * String dari DB di-cast ke tipe yang sesuai:
     *   - 'true'/'false'       → bool
     *   - numerik             → int
     *   - sonst               → string
     */
    public function applyTo(FMSUploads $config): FMSUploads
    {
        $all = $this->all();

        $mappings = [
            'storage_driver'                  => 'storageDriver',
            'storage_root'                    => 'storageRoot',
            'bucket_name'                    => 'bucketName',
            'object_key_prefix'              => 'objectKeyPrefix',
            'create_bucket_automatically'    => 'createBucketAutomatically',
            's3_region'                      => 's3RegionName',
            's3_access_key_id'               => 's3AccessKeyIdentifier',
            's3_secret_access_key'           => 's3SecretAccessKey',
            's3_endpoint_host'               => 's3EndpointHost',
            's3_path_style'                 => 's3PathStyleEndpoint',
            's3_use_tls'                     => 's3UseTls',
            'local_download_endpoint'         => 'localDownloadEndpoint',
            'local_hmac_key'                 => 'localDownloadHmacKey',
            'local_hmac_key_identifier'      => 'localDownloadHmacKeyIdentifier',
            'local_hmac_prev_key'            => 'localDownloadPreviousHmacKey',
            'local_hmac_prev_key_identifier' => 'localDownloadPreviousHmacKeyIdentifier',
            'presigned_ttl_seconds'           => 'presignedUrlTtlSeconds',
            'presigned_max_ttl_seconds'      => 'presignedUrlMaximumTtlSeconds',
            'maximum_output_bytes'            => 'maximumOutputBytes',
            'maximum_width'                  => 'maximumWidth',
            'maximum_height'                 => 'maximumHeight',
            'maximum_pixels'                 => 'maximumPixels',
            'maximum_decoded_bytes'          => 'maximumDecodedBytes',
            'webp_quality'                   => 'webPQuality',
            'maximum_files_per_request'      => 'maximumFilesPerRequest',
        ];

        foreach ($mappings as $dbKey => $prop) {
            if (!isset($all[$dbKey])) continue;
            $raw = $all[$dbKey];

            /* local_download_endpoint kosong = pakai default hasil constructor. */
            if ($dbKey === 'local_download_endpoint' && trim($raw) === '') continue;

            // Boolean strings
            if ($raw === 'true') { $config->{$prop} = true; continue; }
            if ($raw === 'false') { $config->{$prop} = false; continue; }

            // Numeric strings
            if (is_numeric($raw)) { $config->{$prop} = (int) $raw; continue; }

            // String
            $config->{$prop} = $raw;
        }

        // MIME dari DB (jika ada)
        if (isset($all[self::KEY_ALLOWED_MIME])) {
            $decoded = json_decode($all[self::KEY_ALLOWED_MIME], true);
            if (is_array($decoded) && count($decoded) > 0) {
                // allowedMimes() akan return ini lewat storage settings; inject via magic property
                // kita tidak override langsung karena allowedMimes() baca dari .env
                // override dengan inject allowedFileTypes berdasarkan MIME yang diizinkan
                $allowed = $decoded;
                $orig = $config->allowedFileTypes;
                $filtered = [];
                foreach ($orig as $key => $def) {
                    if (in_array($def['mime'], $allowed, true)) {
                        $filtered[$key] = $def;
                    }
                }
                $config->allowedFileTypes = $filtered;
            }
        }

        return $config;
    }

    /** @return array<string, array{key:string, mime:string, extension:string, maxBytes:int, label:string}> */
    public function availableMimeTypes(): array
    {
        $available = [];
        foreach (config(FMSUploads::class)->allowedFileTypes as $key => $type) {
            $available[$type['mime']] = [
                'key'       => (string) $key,
                'mime'      => (string) $type['mime'],
                'extension' => (string) $type['extension'],
                'maxBytes'  => (int) $type['maxBytes'],
                'label'     => strtoupper((string) $key),
            ];
        }
        return $available;
    }

    public function hasAllowedMimeSetting(): bool
    {
        if (! $this->tableReady()) return false;
        return db_connect()->table('storage_settings')
            ->where('setting_key', self::KEY_ALLOWED_MIME)
            ->countAllResults() > 0;
    }

    /** @return list<string> daftar MIME aktif */
    public function allowedMimeTypes(): array
    {
        if (! $this->tableReady()) return [];
        $row = db_connect()->table('storage_settings')
            ->where('setting_key', self::KEY_ALLOWED_MIME)
            ->get(1)->getRowArray();

        if (! is_array($row)) return [];

        $decoded = json_decode((string) ($row['setting_value'] ?? ''), true);
        return $this->normalize(is_array($decoded) ? $decoded : []);
    }

    /** @param array<int, mixed> $mimeTypes */
    public function saveAllowedMimeTypes(array $mimeTypes): array
    {
        $clean = $this->normalize($mimeTypes);
        $payload = json_encode(array_values($clean), JSON_UNESCAPED_SLASHES);
        $this->set(self::KEY_ALLOWED_MIME, $payload);
        return $clean;
    }

    /** @param array<int, mixed> $mimeTypes @return list<string> */
    private function normalize(array $mimeTypes): array
    {
        $available = array_keys($this->availableMimeTypes());
        $clean = [];
        foreach ($mimeTypes as $mime) {
            $mime = strtolower(trim((string) $mime));
            if ($mime !== '' && in_array($mime, $available, true) && ! in_array($mime, $clean, true)) {
                $clean[] = $mime;
            }
        }
        return $clean;
    }
}

