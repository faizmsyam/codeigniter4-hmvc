<?php
namespace App\Modules\Storage\Services;

use App\Config\FMSUploads;
use App\Modules\Uploads\Contracts\FMSObjectStorageInterface;
use App\Modules\Uploads\Services\FMSObjectStorageFactory;
use App\Modules\Storage\Config\FMSStorage;
use CodeIgniter\HTTP\Files\UploadedFile;
use InvalidArgumentException;

final class FMSStorageObjectService
{
    public function __construct(
        private readonly ?FMSStorage $storageConfig = null,
        private readonly ?FMSUploads $uploadsConfig = null,
    ) {}

    private function storageConfig(): FMSStorage
    {
        return $this->storageConfig ?? config(FMSStorage::class);
    }

    private function uploadsConfig(): FMSUploads
    {
        return $this->uploadsConfig ?? config(FMSUploads::class);
    }

    public function list(string $prefix = ''): array
    {
        $uploads = $this->uploadsConfig();
        $root = rtrim($uploads->storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uploads->bucketName;
        $base = realpath($root);
        if ($base === false) {
            return ['bucket' => $uploads->bucketName, 'prefix' => '', 'folders' => [], 'files' => []];
        }

        $prefix = trim(str_replace('\\', '/', $prefix), '/');
        $searchPrefix = $prefix !== '' ? $prefix . '/' : '';
        $directory = $base . ($prefix !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $prefix) : '');
        $realDirectory = realpath($directory);
        if ($realDirectory === false || !str_starts_with($realDirectory, $base)) {
            return ['bucket' => $uploads->bucketName, 'prefix' => $prefix, 'folders' => [], 'files' => []];
        }

        $allItems = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->isLink()) continue;
            $key = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($base))), '/');
            $allItems[] = [
                'key' => $key,
                'name' => basename($key),
                'size' => $file->getSize(),
                'mime' => (new \finfo(FILEINFO_MIME_TYPE))->file($file->getPathname()) ?: 'application/octet-stream',
                'updated_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        $folders = [];
        $files = [];
        $seen = [];

        foreach ($allItems as $item) {
            $relative = $searchPrefix !== '' && str_starts_with($item['key'], $searchPrefix)
                ? substr($item['key'], strlen($searchPrefix))
                : ($searchPrefix === '' ? $item['key'] : null);
            if ($relative === null || $relative === '') continue;

            $slashPos = strpos($relative, '/');
            if ($slashPos !== false) {
                $folderName = substr($relative, 0, $slashPos);
                if (!isset($seen[$folderName])) {
                    $folders[] = ['name' => $folderName, 'prefix' => $searchPrefix . $folderName];
                    $seen[$folderName] = true;
                }
            } else {
                $files[] = $item;
            }
        }

        usort($folders, static fn($a, $b) => strcmp($a['name'], $b['name']));
        usort($files, static fn($a, $b) => strcmp($a['name'], $b['name']));

        return [
            'bucket' => $uploads->bucketName,
            'prefix' => $prefix,
            'folders' => $folders,
            'files' => $files,
        ];
    }

    public function upload(UploadedFile $file, string $prefix = ''): array
    {
        $storage = $this->storageConfig();
        $uploads = $this->uploadsConfig();

        if (!$file->isValid() || $file->hasMoved()) {
            throw new InvalidArgumentException('File upload tidak valid.');
        }

        if ($file->getSize() > $uploads->maximumInputBytes()) {
            throw new InvalidArgumentException('Ukuran file melebihi batas.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getTempName()) ?: '';
        $allowedMimes = $storage->allowedMimeTypes();

        if (!in_array($mime, $allowedMimes, true)) {
            throw new InvalidArgumentException('Tipe MIME tidak diizinkan oleh konfigurasi FMS Storage.');
        }

        $name = preg_replace('/[^A-Za-z0-9._-]/', '-', $file->getClientName()) ?: 'object';
        $stem = preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($name, PATHINFO_FILENAME)) ?: 'object';
        $extension = $storage->extensionForMime($mime);

        $keyPrefix = trim(str_replace('\\', '/', $prefix), '/');
        if ($keyPrefix !== '' && preg_match('#^(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+$#', $keyPrefix) !== 1) {
            throw new InvalidArgumentException('Prefix object tidak valid.');
        }

        $key = ($keyPrefix !== '' ? $keyPrefix . '/' : '') . 'fms-' . bin2hex(random_bytes(12)) . '-' . $stem . '.' . $extension;

        $objectStorage = $this->storage();
        $objectStorage->writeAny($key, $file->getTempName(), $mime);

        return [
            'key' => $key,
            'name' => $name,
            'size' => $file->getSize(),
            'mime' => $mime,
            'driver' => $objectStorage->driverName(),
            'bucket' => $objectStorage->bucketName(),
        ];
    }

    public function delete(string $key): bool
    {
        return $this->storage()->delete($this->safeKey($key));
    }

    public function presigned(string $key, int $ttl): string
    {
        return $this->storage()->createPresignedDownloadUrl(
            $this->safeKey($key),
            time() + max(30, min($this->storageConfig()->shareMaximumTtl, $ttl))
        );
    }

    public function readLocal(string $key): array
    {
        return $this->storage()->readObject($this->safeKey($key));
    }

    private function storage(): FMSObjectStorageInterface
    {
        return FMSObjectStorageFactory::create($this->uploadsConfig());
    }

    private function safeKey(string $key): string
    {
        $key = trim(str_replace('\\', '/', $key), '/');
        if (
            $key === ''
            || strlen($key) > 512
            || str_contains($key, '..')
            || str_contains($key, "\0")
            || preg_match('#^(?:[A-Za-z0-9_-]+/)*fms-[A-Za-z0-9_-]{1,200}\.(jpg|jpeg|png|webp|gif|pdf|txt|zip|bin)$#', $key) !== 1
        ) {
            throw new InvalidArgumentException('Object key tidak valid.');
        }

        return $key;
    }
}
