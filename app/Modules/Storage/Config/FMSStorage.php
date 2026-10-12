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
        'image/vnd.microsoft.icon' => 'ico',
        'image/svg+xml' => 'svg',
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.oasis.opendocument.text' => 'odt',
        'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
        'application/json' => 'json',
        'application/xml' => 'xml',
        'text/markdown' => 'md',
        'text/html' => 'html',
        'application/zip' => 'zip',
        'application/gzip' => 'gz',
        'application/vnd.rar' => 'rar',
        'application/x-7z-compressed' => '7z',
        'application/x-tar' => 'tar',
        'audio/mpeg' => 'mp3',
        'audio/wav' => 'wav',
        'audio/ogg' => 'ogg',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
        'application/octet-stream' => 'bin',
    ];

    public function extensionForMime(string $mime): string
    {
        return $this->mimeExtensions[$mime] ?? 'bin';
    }

    /**
     * Single source of truth untuk allowed MIME types.
     * Pilihan aktif dibaca dari fmsuploads.allowedMimeTypes di .env;
     * definisi tipe dan validasi tetap berasal dari FMSUploads.
     */
    public function allowedMimeTypes(): array
    {
        $settings = new \App\Modules\Storage\Services\FMSStorageSettingsService();
        if ($settings->hasAllowedMimeSetting()) {
            return $settings->allowedMimeTypes();
        }

        return config(FMSUploads::class)->allowedMimes();
    }
}
