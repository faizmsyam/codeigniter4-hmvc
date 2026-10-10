<?php
namespace App\Modules\Storage\Config;

use App\Config\FMSUploads;
use CodeIgniter\Config\BaseConfig;

/**
 * Storage module runtime configuration.
 *
 * Perintah: "Semua dinamis fmsuploads.storageRoot check .env".
 * Artinya storageRoot, bucketName, objectKeyPrefix, driver, dan semua
 * setting bucket/object berasal dari config tunggal FMSUploads, yang
 * pada gilirannya dibaca dari .env prefix fmsuploads.*
 * Config ini TIDAK menduplikat storage path — ia hanya mengekspor pointer
 * ke sumbertunggal FMSUploads agar semua service konsisten.
 */
final class FMSStorage extends BaseConfig
{
    public string $sessionPrefix       = 'fms_storage_';
    public int    $sessionTtl          = 28800;
    public int    $shareDefaultTtl     = 3600;
    public int    $shareMaximumTtl     = 604800;
    public bool   $allowLocalDriver    = true;

    /** @var array<string, string> */
    public array $mimeExtensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'application/zip' => 'zip',
        'application/octet-stream' => 'bin',
    ];

    public function extensionForMime(string $mime): string
    {
        return $this->mimeExtensions[$mime] ?? 'bin';
    }

    /**
     * Single source of truth untuk allowed MIME types.
     * Dibaca langsung dari FMSUploads (yang bisa override lewat .env
     * fmsuploads.allowedFileTypes.*), sehingga tidak ada duplikasi daftar.
     */
    public function allowedMimeTypes(): array
    {
        return array_values(array_unique(array_column(
            config(FMSUploads::class)->allowedFileTypes ?? [],
            'mime'
        )));
    }
}
