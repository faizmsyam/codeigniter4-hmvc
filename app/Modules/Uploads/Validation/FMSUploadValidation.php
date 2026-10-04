<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Uploads\Validation;

use App\Config\FMSUploads;

final class FMSUploadValidation
{
    /**
     * Validasi purpose/folder tujuan upload.
     *
     * @param array<string, mixed> $requestData
     * @return array<string, array<int, string>>
     */
    public static function validatePurpose(array $requestData, ?FMSUploads $configuration = null): array
    {
        $resolvedConfiguration = $configuration ?? config(FMSUploads::class);
        $purposeName           = trim((string) ($requestData['purpose'] ?? ''));

        if ($purposeName === '' || mb_strlen($purposeName) > 64) {
            return ['purpose' => ['Folder tujuan wajib 1-64 karakter.']];
        }

        if (
            str_contains($purposeName, '/')
            || str_contains($purposeName, '\\')
            || str_contains($purposeName, '..')
            || str_contains($purposeName, "\0")
        ) {
            return ['purpose' => ['Folder tujuan tidak boleh memuat path traversal.']];
        }

        /* Prefix wajib fms- */
        $folderPrefix = (string) $resolvedConfiguration->folderPrefix;
        if (! str_starts_with(strtolower($purposeName), strtolower($folderPrefix))) {
            return ['purpose' => ['Folder tujuan wajib diawali "' . $folderPrefix . '".']];
        }

        if (preg_match('/\Afms-[a-z0-9][a-z0-9-]{0,62}\z/', strtolower($purposeName)) !== 1) {
            return ['purpose' => ['Folder tujuan hanya boleh huruf kecil, angka, dan tanda hubung.']];
        }

        return [];
    }

    /**
     * Validasi jumlah file dalam satu request.
     *
     * @return array<string, array<int, string>>
     */
    public static function validateFileCount(int $receivedFileCount, ?FMSUploads $configuration = null): array
    {
        $resolvedConfiguration = $configuration ?? config(FMSUploads::class);

        if ($receivedFileCount < 1) {
            return ['file' => ['Berkas wajib diunggah.']];
        }

        if ($receivedFileCount > $resolvedConfiguration->maximumFilesPerRequest) {
            return ['file' => ['Jumlah berkas melebihi batas ' . $resolvedConfiguration->maximumFilesPerRequest . ' file per permintaan.']];
        }

        return [];
    }

    /**
     * Validasi metadata upload (folder, owner_type, owner_id).
     *
     * @param array<string, mixed> $requestData
     * @return array<string, array<int, string>>
     */
    public static function validateUploadMetadata(array $requestData, ?FMSUploads $configuration = null): array
    {
        $resolvedConfiguration = $configuration ?? config(FMSUploads::class);
        $validationErrors      = [];

        $folderName   = trim((string) ($requestData['folder'] ?? ''));
        $folderPrefix = (string) $resolvedConfiguration->folderPrefix;

        if (
            $folderName === ''
            || mb_strlen($folderName) > 64
            || ! str_starts_with(strtolower($folderName), strtolower($folderPrefix))
            || str_contains($folderName, '/')
            || str_contains($folderName, '\\')
            || str_contains($folderName, '..')
            || str_contains($folderName, "\0")
            || preg_match('/\Afms-[a-z0-9][a-z0-9-]{0,62}\z/', strtolower($folderName)) !== 1
        ) {
            $validationErrors['folder'] = ['Folder tujuan tidak valid. Gunakan format: fms-nama-folder'];
        }

        $ownerType = trim((string) ($requestData['owner_type'] ?? ''));
        if ($ownerType === '' || mb_strlen($ownerType) > 64 || preg_match('/\A[A-Za-z0-9_.\-]+\z/', $ownerType) !== 1) {
            $validationErrors['owner_type'] = ['Jenis pemilik wajib alfanumerik terbatas.'];
        }

        $ownerIdentifier = (int) ($requestData['owner_id'] ?? 0);
        if ($ownerIdentifier < 1) {
            $validationErrors['owner_id'] = ['ID pemilik wajib bilangan bulat positif.'];
        }

        return $validationErrors;
    }

    /**
     * Daftar MIME yang diizinkan dari config whitelist (untuk info/error message).
     *
     * @return list<string>
     */
    public static function allowedMimeList(?FMSUploads $configuration = null): array
    {
        $resolvedConfiguration = $configuration ?? config(FMSUploads::class);
        return $resolvedConfiguration->allowedMimes();
    }
}
