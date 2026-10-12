<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi upload FMS.
 * Setiap properti terbaca dari .env dengan prefix FMSUploads.
 *
 * allowedFileTypes: whitelist tipe file beserta:
 *   - mime     : MIME yang diizinkan (diperiksa via finfo, BUKAN dari client)
 *   - magic    : magic bytes pembuka file (hex, opsional — null = skip check)
 *   - extension: ekstensi output yang disimpan
 *   - maxBytes : batas ukuran file khusus tipe ini (0 = pakai maximumInputBytes global)
 *   - reEncode : true = pipeline image harus re-encode; false = simpan as-is setelah verifikasi
 */
final class FMSUploads extends BaseConfig
{
    /**
     * Whitelist tipe file yang diizinkan.
     * Key = identifier tipe, value = aturan validasi.
     *
     * @var array<string, array{mime: string, magic: string|null, extension: string, maxBytes: int, reEncode: bool}>
     */
    public array $allowedFileTypes = [
        /* ── Gambar (di-re-encode ke WebP) ────────────────── */
        'jpeg' => [
            'mime'      => 'image/jpeg',
            'magic'     => 'FFD8FF',
            'extension' => 'webp',        /* re-encode ke WebP */
            'maxBytes'  => 5_242_880,     /* 5 MB */
            'reEncode'  => true,
        ],
        'png' => [
            'mime'      => 'image/png',
            'magic'     => '89504E47',
            'extension' => 'webp',
            'maxBytes'  => 5_242_880,
            'reEncode'  => true,
        ],
        'webp' => [
            'mime'      => 'image/webp',
            'magic'     => null,          /* WebP: RIFF + WEBP, diperiksa di dalam service */
            'extension' => 'webp',
            'maxBytes'  => 5_242_880,
            'reEncode'  => true,
        ],
        'gif' => [
            'mime'      => 'image/gif',
            'magic'     => '47494638',    /* GIF8 */
            'extension' => 'webp',
            'maxBytes'  => 5_242_880,
            'reEncode'  => true,
        ],

        /* ── Dokumen ──────────────────────────────────────── */
        'pdf' => [
            'mime'      => 'application/pdf',
            'magic'     => '255044462D', /* %PDF- */
            'extension' => 'pdf',
            'maxBytes'  => 20_971_520,   /* 20 MB */
            'reEncode'  => false,
        ],
        'docx' => [
            'mime'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'magic'     => '504B0304',   /* PK.. (OOXML = zip) */
            'extension' => 'docx',
            'maxBytes'  => 20_971_520,
            'reEncode'  => false,
        ],
        'doc' => [
            'mime'      => 'application/msword',
            'magic'     => 'D0CF11E0',   /* OLE2 */
            'extension' => 'doc',
            'maxBytes'  => 20_971_520,
            'reEncode'  => false,
        ],
        'xlsx' => [
            'mime'      => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'magic'     => '504B0304',
            'extension' => 'xlsx',
            'maxBytes'  => 20_971_520,
            'reEncode'  => false,
        ],
        'xls' => [
            'mime'      => 'application/vnd.ms-excel',
            'magic'     => 'D0CF11E0',
            'extension' => 'xls',
            'maxBytes'  => 20_971_520,
            'reEncode'  => false,
        ],
        'pptx' => [
            'mime'      => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'magic'     => '504B0304',
            'extension' => 'pptx',
            'maxBytes'  => 20_971_520,
            'reEncode'  => false,
        ],
        'ppt' => [
            'mime'      => 'application/vnd.ms-powerpoint',
            'magic'     => 'D0CF11E0',
            'extension' => 'ppt',
            'maxBytes'  => 20_971_520,
            'reEncode'  => false,
        ],
        'odt' => [
            'mime'      => 'application/vnd.oasis.opendocument.text',
            'magic'     => '504B0304',
            'extension' => 'odt',
            'maxBytes'  => 20_971_520,
            'reEncode'  => false,
        ],
        'ods' => [
            'mime'      => 'application/vnd.oasis.opendocument.spreadsheet',
            'magic'     => '504B0304',
            'extension' => 'ods',
            'maxBytes'  => 20_971_520,
            'reEncode'  => false,
        ],

        /* ── Teks & data ──────────────────────────────────── */
        'txt' => [
            'mime'      => 'text/plain',
            'magic'     => null,
            'extension' => 'txt',
            'maxBytes'  => 5_242_880,
            'reEncode'  => false,
        ],
        'csv' => [
            'mime'      => 'text/csv',
            'magic'     => null,
            'extension' => 'csv',
            'maxBytes'  => 5_242_880,
            'reEncode'  => false,
        ],
        'json' => [
            'mime'      => 'application/json',
            'magic'     => null,
            'extension' => 'json',
            'maxBytes'  => 5_242_880,
            'reEncode'  => false,
        ],
        'xml' => [
            'mime'      => 'application/xml',
            'magic'     => null,
            'extension' => 'xml',
            'maxBytes'  => 5_242_880,
            'reEncode'  => false,
        ],
        'md' => [
            'mime'      => 'text/markdown',
            'magic'     => null,
            'extension' => 'md',
            'maxBytes'  => 5_242_880,
            'reEncode'  => false,
        ],
        'html' => [
            'mime'      => 'text/html',
            'magic'     => null,
            'extension' => 'html',
            'maxBytes'  => 5_242_880,
            'reEncode'  => false,
        ],

        /* ── Arsip ────────────────────────────────────────── */
        'zip' => [
            'mime'      => 'application/zip',
            'magic'     => '504B0304',
            'extension' => 'zip',
            'maxBytes'  => 52_428_800,   /* 50 MB */
            'reEncode'  => false,
        ],
        'gz' => [
            'mime'      => 'application/gzip',
            'magic'     => '1F8B',
            'extension' => 'gz',
            'maxBytes'  => 52_428_800,
            'reEncode'  => false,
        ],
        'rar' => [
            'mime'      => 'application/vnd.rar',
            'magic'     => '526172211A07', /* Rar!.. */
            'extension' => 'rar',
            'maxBytes'  => 52_428_800,
            'reEncode'  => false,
        ],
        '7z' => [
            'mime'      => 'application/x-7z-compressed',
            'magic'     => '377ABCAF271C',
            'extension' => '7z',
            'maxBytes'  => 52_428_800,
            'reEncode'  => false,
        ],
        'tar' => [
            'mime'      => 'application/x-tar',
            'magic'     => null,          /* 'ustar' ada di offset 257, bukan prefix */
            'extension' => 'tar',
            'maxBytes'  => 52_428_800,
            'reEncode'  => false,
        ],

        /* ── Audio & video ────────────────────────────────── */
        'mp3' => [
            'mime'      => 'audio/mpeg',
            'magic'     => null,          /* ID3 / frame-sync, bervariasi */
            'extension' => 'mp3',
            'maxBytes'  => 52_428_800,
            'reEncode'  => false,
        ],
        'wav' => [
            'mime'      => 'audio/wav',
            'magic'     => '52494646',    /* RIFF */
            'extension' => 'wav',
            'maxBytes'  => 52_428_800,
            'reEncode'  => false,
        ],
        'ogg' => [
            'mime'      => 'audio/ogg',
            'magic'     => '4F676753',    /* OggS */
            'extension' => 'ogg',
            'maxBytes'  => 52_428_800,
            'reEncode'  => false,
        ],
        'mp4' => [
            'mime'      => 'video/mp4',
            'magic'     => null,          /* 'ftyp' ada di offset 4 */
            'extension' => 'mp4',
            'maxBytes'  => 52_428_800,
            'reEncode'  => false,
        ],
        'webm' => [
            'mime'      => 'video/webm',
            'magic'     => null,
            'extension' => 'webm',
            'maxBytes'  => 52_428_800,
            'reEncode'  => false,
        ],
        'mov' => [
            'mime'      => 'video/quicktime',
            'magic'     => null,
            'extension' => 'mov',
            'maxBytes'  => 52_428_800,
            'reEncode'  => false,
        ],
    ];

    /* ── Storage ────────────────────────────────────────── */
    public string $storageDriver = 'local';
    public string $storageRoot   = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'fms-buckets';
    public string $bucketName    = 'fms-private';
    public string $objectKeyPrefix = 'fms-uploads';
    public bool   $createBucketAutomatically = true;

    /* ── Image pipeline ─────────────────────────────────── */
    public int    $maximumOutputBytes   = 8_388_608;   /* 8 MB WebP output */
    public int    $maximumWidth         = 4096;
    public int    $maximumHeight        = 4096;
    public int    $maximumPixels        = 16_777_216;
    public int    $maximumDecodedBytes  = 134_217_728;
    public int    $decodedBytesPerPixel = 4;
    public int    $webPQuality          = 82;

    /* ── Naming ─────────────────────────────────────────── */
    public string $filenamePrefix          = 'fms-';
    public string $folderPrefix            = 'fms-';
    public string $temporaryFilenamePrefix = 'fms-tmp-';
    public int    $directoryMode           = 0750;
    public int    $fileMode                = 0640;
    public int    $maximumFilesPerRequest  = 10;

    /* ── WebP binaries ──────────────────────────────────── */
    public string $cwebpBinary = '';
    public string $dwebpBinary = '';

    /* ── Presigned URL ──────────────────────────────────── */
    public string $localDownloadEndpoint            = '';
    public string $localDownloadHmacKey             = '';
    public string $localDownloadHmacKeyIdentifier   = 'fms-download-2026-01';
    public string $localDownloadPreviousHmacKey     = '';
    public string $localDownloadPreviousHmacKeyIdentifier = '';
    public int    $presignedUrlTtlSeconds        = 300;
    public int    $presignedUrlMaximumTtlSeconds = 900;

    public function __construct()
    {
        parent::__construct();

        if (trim($this->cwebpBinary) === '') {
            $this->cwebpBinary = $this->discoverBinaryPath('cwebp');
        }

        if (trim($this->dwebpBinary) === '') {
            $this->dwebpBinary = $this->discoverBinaryPath('dwebp');
        }

        if (trim($this->localDownloadEndpoint) === '') {
            $this->localDownloadEndpoint = rtrim(site_url('api/v1/uploads'), '/');
        }
    }

    private function discoverBinaryPath(string $name): string
    {
        $candidates = [
            '/usr/local/bin/' . $name,
            '/opt/homebrew/bin/' . $name,
            '/usr/bin/' . $name,
            '/bin/' . $name,
            '/data/data/com.termux/files/usr/bin/' . $name,
        ];

        foreach ($candidates as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        /* Fallback: ask shell (safe; output is only used if it looks like an absolute path) */
        if (function_exists('shell_exec')) {
            $found = trim((string) @shell_exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null'));
            if ($found !== '' && str_starts_with($found, DIRECTORY_SEPARATOR) && is_executable($found)) {
                return $found;
            }
        }

        return '';
    }

    /* ── S3 ─────────────────────────────────────────────── */
    public string $s3RegionName       = '';
    public string $s3AccessKeyIdentifier = '';
    public string $s3SecretAccessKey  = '';
    public string $s3EndpointHost     = '';
    public bool   $s3PathStyleEndpoint = true;
    public bool   $s3UseTls           = true;

    /* ── Helpers ────────────────────────────────────────── */

    /** @return list<string> MIME yang diizinkan dari whitelist */
    public function allowedMimes(): array
    {
        $configured = trim((string) env('fmsuploads.allowedMimeTypes', ''));
        $available = array_values(array_unique(array_column($this->allowedFileTypes, 'mime')));

        if ($configured === '') {
            return $available;
        }

        $requested = array_values(array_unique(array_filter(array_map(
            static fn (string $mime): string => strtolower(trim($mime)),
            explode(',', $configured),
        ))));

        return array_values(array_intersect($requested, $available));
    }

    /** @return int ukuran maksimum global (maks dari semua tipe) */
    public function maximumInputBytes(): int
    {
        $max = 0;
        foreach ($this->allowedFileTypes as $type) {
            if ($type['maxBytes'] > $max) {
                $max = $type['maxBytes'];
            }
        }
        return $max > 0 ? $max : 5_242_880;
    }

    /**
     * Lookup type definition by detected MIME.
     *
     * @return array{mime: string, magic: string|null, extension: string, maxBytes: int, reEncode: bool}|null
     */
    public function typeDefinitionForMime(string $mime): ?array
    {
        foreach ($this->allowedFileTypes as $typeDefinition) {
            if ($typeDefinition['mime'] === $mime) {
                return $typeDefinition;
            }
        }
        return null;
    }
}
