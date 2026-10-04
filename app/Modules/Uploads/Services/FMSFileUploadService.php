<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Uploads\Services;

use App\Config\FMSUploads;
use App\Modules\Uploads\Exceptions\FMSUploadException;
use CodeIgniter\HTTP\Files\UploadedFile;
use finfo;
use Throwable;

/**
 * Secure general-purpose file upload service.
 *
 * Pipeline validasi berlapis:
 *   1. Ukuran file (sebelum baca apapun)
 *   2. Deteksi MIME via finfo (bytes, bukan header client)
 *   3. Lookup whitelist — tolak jika tidak ada entri
 *   4. Verifikasi magic bytes (hex pattern dari config)
 *   5. Per-type limit ukuran
 *   6. Gambar → re-encode ke WebP via FMSWebPUploadService (strip metadata, validasi dimensi)
 *   7. PDF → simpan as-is setelah magic byte %PDF- verified
 *   8. Cleanup semua temp file setelah sukses MAUPUN gagal
 *
 * Filename strategy:
 *   - Ada nama klien / override → sanitized kebab-case + prefix fms-
 *   - Tidak ada / kosong → fallback random hex
 *
 * @return array{
 *   storage_driver: string,
 *   bucket_name: string,
 *   file_name: string,
 *   object_key: string,
 *   download_url: string,
 *   mime_type: string,
 *   extension: string,
 *   size: int,
 *   sha256: string,
 * }
 */
final class FMSFileUploadService
{
    private readonly FMSUploads $configuration;
    private readonly FMSUploadNamingService $namingService;
    private readonly FMSPrivateUploadService $privateUploadService;
    private readonly FMSWebPUploadService $imageUploadService;

    public function __construct(
        ?FMSUploads $configuration = null,
        ?FMSUploadNamingService $namingService = null,
        ?FMSPrivateUploadService $privateUploadService = null,
        ?FMSWebPUploadService $imageUploadService = null,
    ) {
        $this->configuration  = $configuration ?? config(FMSUploads::class);
        $this->namingService  = $namingService ?? new FMSUploadNamingService();
        $storage              = FMSObjectStorageFactory::create($this->configuration);
        $this->privateUploadService = $privateUploadService ?? new FMSPrivateUploadService(
            $storage,
            $this->configuration->presignedUrlTtlSeconds,
            $this->configuration->presignedUrlMaximumTtlSeconds,
        );
        $this->imageUploadService = $imageUploadService ?? new FMSWebPUploadService(
            $this->configuration,
            $this->namingService,
            null,
            $this->privateUploadService,
        );
    }

    /**
     * Upload dari UploadedFile CI4.
     *
     * @return array{storage_driver: string, bucket_name: string, file_name: string, object_key: string, download_url: string, mime_type: string, extension: string, size: int, sha256: string}
     */
    public function storeUploadedFile(
        UploadedFile $uploadedFile,
        string $purpose = 'fms-uploads',
        ?string $overrideFilename = null,
    ): array {
        if ($uploadedFile->hasMoved()) {
            throw new FMSUploadException('Berkas yang diunggah sudah dipindahkan.');
        }
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new FMSUploadException('Berkas mengalami kesalahan transfer saat diunggah.');
        }

        $sourceFilePath = $uploadedFile->getTempName();
        $clientName     = $overrideFilename ?? $uploadedFile->getClientName();

        return $this->processFile($sourceFilePath, $purpose, $clientName);
    }

    /**
     * Upload dari path lokal (server-side import).
     *
     * @return array{storage_driver: string, bucket_name: string, file_name: string, object_key: string, download_url: string, mime_type: string, extension: string, size: int, sha256: string}
     */
    public function storeLocalFile(
        string $sourceFilePath,
        string $purpose = 'fms-uploads',
        ?string $explicitFilename = null,
    ): array {
        return $this->processFile($sourceFilePath, $purpose, $explicitFilename);
    }

    public function deleteStoredFile(string $objectKey): bool
    {
        return $this->privateUploadService->delete($objectKey);
    }

    /* ── Core pipeline ───────────────────────────────────── */

    /**
     * @return array{storage_driver: string, bucket_name: string, file_name: string, object_key: string, download_url: string, mime_type: string, extension: string, size: int, sha256: string}
     */
    private function processFile(string $sourceFilePath, string $purpose, ?string $clientName): array
    {
        /* 1 — path aman & file ada */
        $this->assertSafePath($sourceFilePath);
        $this->assertFileReadable($sourceFilePath);

        /* 2 — ukuran global (sebelum baca isi) */
        $fileSize = $this->assertFileSizeBelow($sourceFilePath, $this->configuration->maximumInputBytes());

        /* 3 — deteksi MIME via finfo (membaca bytes nyata) */
        $detectedMime = $this->detectMime($sourceFilePath);

        /* 4 — lookup whitelist; reject jika tidak ada */
        $typeDefinition = $this->configuration->typeDefinitionForMime($detectedMime);
        if ($typeDefinition === null) {
            throw new FMSUploadException(
                'Tipe MIME "' . $detectedMime . '" tidak diizinkan. '
                . 'Diizinkan: ' . implode(', ', $this->configuration->allowedMimes()) . '.',
            );
        }

        /* 5 — magic bytes (dari config whitelist) */
        if ($typeDefinition['magic'] !== null) {
            $this->assertMagicBytes($sourceFilePath, $typeDefinition['magic'], $detectedMime);
        }

        /* 6 — per-type size limit */
        if ($typeDefinition['maxBytes'] > 0 && $fileSize > $typeDefinition['maxBytes']) {
            throw new FMSUploadException(
                'Ukuran berkas ' . number_format($fileSize / 1048576, 1) . ' MB'
                . ' melebihi batas ' . number_format($typeDefinition['maxBytes'] / 1048576, 1) . ' MB'
                . ' untuk tipe ' . $detectedMime . '.',
            );
        }

        /* 7 — proses sesuai tipe */
        if ($typeDefinition['reEncode'] === true) {
            /* Gambar: delegasikan ke pipeline WebP yang strict (GD decode → re-encode → verify) */
            return $this->imageUploadService->storeLocalFile($sourceFilePath, $purpose);
        }

        /* Non-image (PDF): simpan as-is setelah verifikasi */
        return $this->storeDirect($sourceFilePath, $purpose, $clientName, $typeDefinition);
    }

    /* ── Direct store (PDF as-is) ────────────────────────── */

    /**
     * @param array{mime: string, magic: string|null, extension: string, maxBytes: int, reEncode: bool} $typeDefinition
     * @return array{storage_driver: string, bucket_name: string, file_name: string, object_key: string, download_url: string, mime_type: string, extension: string, size: int, sha256: string}
     */
    private function storeDirect(
        string $sourceFilePath,
        string $purpose,
        ?string $clientName,
        array $typeDefinition,
    ): array {
        $folderName  = $this->namingService->normalizeFolderName($purpose);
        $extension   = $typeDefinition['extension'];
        $fileName    = $this->buildFileName($clientName, $extension);
        $objectKey   = $folderName . '/' . $fileName;

        $tempPath = $this->createStagingTemp($folderName);

        try {
            if (! @copy($sourceFilePath, $tempPath)) {
                throw new FMSUploadException('Gagal menyalin berkas ke staging.');
            }

            chmod($tempPath, $this->configuration->fileMode);

            /* Verifikasi magic sekali lagi setelah copy (defense in depth) */
            if ($typeDefinition['magic'] !== null) {
                $this->assertMagicBytes($tempPath, $typeDefinition['magic'], $typeDefinition['mime']);
            }

            $storage         = FMSObjectStorageFactory::create($this->configuration);
            $storageMetadata = $storage->writeAny($objectKey, $tempPath, $typeDefinition['mime']);

            $signedUrl = $this->privateUploadService->signedReadUrl($objectKey);
            $fileSize  = filesize($sourceFilePath);
            $sha256    = hash_file('sha256', $sourceFilePath);

            /* Cleanup temp SETELAH sukses */
            if (is_file($tempPath)) {
                @unlink($tempPath);
                $tempPath = '';
            }

            return [
                'storage_driver' => $storage->driverName(),
                'bucket_name'    => $storage->bucketName(),
                'file_name'      => $fileName,
                'object_key'     => $objectKey,
                'download_url'   => $signedUrl,
                'mime_type'      => $typeDefinition['mime'],
                'extension'      => $extension,
                'size'           => $fileSize !== false ? $fileSize : 0,
                'sha256'         => $sha256 !== false ? $sha256 : '',
            ];
        } catch (FMSUploadException $uploadException) {
            throw $uploadException;
        } catch (Throwable $unexpectedException) {
            throw new FMSUploadException(
                'Pemrosesan berkas gagal.',
                0,
                $unexpectedException,
            );
        } finally {
            /* Cleanup temp jika belum terhapus (gagal sebelum unlink eksplisit) */
            if ($tempPath !== '' && is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /* ── Validators ──────────────────────────────────────── */

    private function assertSafePath(string $filePath): void
    {
        if (
            $filePath === ''
            || str_contains($filePath, "\0")
            || str_contains($filePath, '..')
        ) {
            throw new FMSUploadException('Path berkas tidak valid.');
        }
    }

    private function assertFileReadable(string $filePath): void
    {
        if (! is_file($filePath) || is_link($filePath) || ! is_readable($filePath)) {
            throw new FMSUploadException('Berkas harus berupa file reguler yang dapat dibaca tanpa symlink.');
        }
    }

    private function assertFileSizeBelow(string $filePath, int $maximumBytes): int
    {
        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize < 1) {
            throw new FMSUploadException('Ukuran berkas tidak dapat dibaca atau file kosong.');
        }
        if ($fileSize > $maximumBytes) {
            throw new FMSUploadException(
                'Ukuran berkas ' . number_format($fileSize / 1048576, 1) . ' MB'
                . ' melebihi batas global ' . number_format($maximumBytes / 1048576, 1) . ' MB.',
            );
        }
        return $fileSize;
    }

    private function detectMime(string $filePath): string
    {
        /* Baca hanya 8 KB untuk deteksi — tidak perlu load seluruh file */
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new FMSUploadException('Berkas tidak dapat dibuka untuk deteksi tipe.');
        }
        $sampleBytes = fread($handle, 8192);
        fclose($handle);

        if (! is_string($sampleBytes) || $sampleBytes === '') {
            throw new FMSUploadException('Berkas tidak dapat dibaca untuk deteksi tipe.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->buffer($sampleBytes);

        if (! is_string($detectedMime) || $detectedMime === '') {
            throw new FMSUploadException('Tipe MIME berkas tidak dapat dideteksi.');
        }

        /* Jangan percaya tipe "octet-stream" atau "text/*" yang ambigu */
        if (
            $detectedMime === 'application/octet-stream'
            || str_starts_with($detectedMime, 'text/')
        ) {
            throw new FMSUploadException(
                'Tipe MIME "' . $detectedMime . '" tidak dapat diterima — format tidak dikenali.',
            );
        }

        return $detectedMime;
    }

    private function assertMagicBytes(string $filePath, string $magicHex, string $mimeLabel): void
    {
        $magicBinary = hex2bin($magicHex);
        if ($magicBinary === false) {
            throw new FMSUploadException('Konfigurasi magic bytes tidak valid untuk tipe: ' . $mimeLabel);
        }

        $magicLength = strlen($magicBinary);
        $handle      = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new FMSUploadException('Berkas tidak dapat dibuka untuk verifikasi magic bytes.');
        }

        $headerBytes = fread($handle, $magicLength);
        fclose($handle);

        if (! is_string($headerBytes) || ! hash_equals($magicBinary, substr($headerBytes, 0, $magicLength))) {
            throw new FMSUploadException(
                'Magic bytes tidak cocok untuk tipe ' . $mimeLabel
                . ' — berkas mungkin dipalsukan atau rusak.',
            );
        }
    }

    /* ── Helpers ─────────────────────────────────────────── */

    /** Sanitize nama klien → kebab-case dengan prefix fms-, fallback random hex */
    private function buildFileName(?string $clientName, string $extension): string
    {
        if ($clientName !== null && trim($clientName) !== '') {
            $baseName  = pathinfo(trim($clientName), PATHINFO_FILENAME);
            $sanitized = strtolower($baseName);
            $sanitized = (string) preg_replace('/[\s_]+/', '-', $sanitized);
            $sanitized = (string) preg_replace('/[^a-z0-9\-]/', '', $sanitized);
            $sanitized = trim($sanitized, '-');

            /* Batasi panjang nama agar object key tidak melebihi 512 char */
            if ($sanitized !== '') {
                return 'fms-' . substr($sanitized, 0, 80) . '.' . $extension;
            }
        }

        return 'fms-' . bin2hex(random_bytes(16)) . '.' . $extension;
    }

    private function createStagingTemp(string $folderName): string
    {
        $stagingDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'fms-staging'
            . DIRECTORY_SEPARATOR . $folderName;

        if (! is_dir($stagingDir)
            && ! mkdir($stagingDir, $this->configuration->directoryMode, true)
            && ! is_dir($stagingDir)) {
            throw new FMSUploadException('Direktori staging tidak dapat dibuat.');
        }

        $tempPath = tempnam($stagingDir, $this->configuration->temporaryFilenamePrefix);
        if ($tempPath === false) {
            throw new FMSUploadException('File sementara tidak dapat dibuat di staging.');
        }

        return $tempPath;
    }
}
