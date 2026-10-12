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
    ) {
        /* NULL default: resolve runtime dengan DB overrides (storage_settings).
           Semua nilai .env lama kini dibaca dari DB agar single source of truth. */
    }

    private function storageConfig(): FMSStorage
    {
        return $this->storageConfig ?? config(FMSStorage::class);
    }

    private function uploadsConfig(): FMSUploads
    {
        if ($this->uploadsConfig !== null) {
            return $this->uploadsConfig;
        }
        /* Clone agar mutasi overrides tidak mencemari singleton config global. */
        $config = clone config(FMSUploads::class);
        try {
            (new \App\Modules\Storage\Services\FMSStorageSettingsService())->applyTo($config);
        } catch (\Throwable $e) {
            /* DB belum siap — pakai default config saja. */
        }
        return $config;
    }

    public function list(
        string $prefix = '',
        string $search = '',
        string $sort = 'name',
        string $direction = 'asc',
        int $page = 1,
        int $perPage = 100,
    ): array
    {
        $uploads = $this->uploadsConfig();
        $prefix = trim(str_replace('\\', '/', $prefix), '/');
        if ($prefix !== '' && preg_match('#^(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+$#', $prefix) !== 1) {
            throw new InvalidArgumentException('Prefix object tidak valid.');
        }

        $perPage = max(1, min(500, $perPage > 0 ? $perPage : 100));
        $page = max(1, $page > 0 ? $page : 1);
        $root = rtrim($uploads->storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uploads->bucketName;
        $base = realpath($root);
        if ($base === false) {
            return ['bucket' => $uploads->bucketName, 'prefix' => $prefix, 'folders' => [], 'files' => [], 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => 0, 'total_pages' => 1]];
        }

        $searchPrefix = $prefix !== '' ? $prefix . '/' : '';
        $physicalPrefix = trim((string) $uploads->objectKeyPrefix, '/');
        $physicalRoot = $base . ($physicalPrefix !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $physicalPrefix) : '');
        $physicalBase = realpath($physicalRoot);
        if ($physicalBase === false) {
            return ['bucket' => $uploads->bucketName, 'prefix' => $prefix, 'folders' => [], 'files' => [], 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => 0, 'total_pages' => 1]];
        }
        $directory = $physicalBase . ($prefix !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $prefix) : '');
        $realDirectory = realpath($directory);
        if ($realDirectory === false || ! str_starts_with($realDirectory, $physicalBase . DIRECTORY_SEPARATOR) && $realDirectory !== $physicalBase) {
            return ['bucket' => $uploads->bucketName, 'prefix' => $prefix, 'folders' => [], 'files' => [], 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => 0, 'total_pages' => 1]];
        }

        $allItems = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->isLink()) continue;
            $key = $this->logicalKey(ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($base))), '/'));
            if ($key === '') continue;
            // Skip dotfile system (DS_Store, ._* Mac metadata, .git, etc.)
            if (str_starts_with(basename($key), '.')) continue;
            $allItems[] = [
                'key' => $key,
                'name' => basename($key),
                'size' => $file->getSize(),
                'mime' => (new \finfo(FILEINFO_MIME_TYPE))->file($file->getPathname()) ?: 'application/octet-stream',
                'updated_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        $query = strtolower(trim($search));
        if ($query !== '') {
            $allItems = array_values(array_filter($allItems, static function (array $item) use ($query): bool {
                return str_contains(strtolower((string) $item['key']), $query)
                    || str_contains(strtolower((string) $item['name']), $query)
                    || str_contains(strtolower((string) $item['mime']), $query);
            }));
        }

        $seen = [];
        $folders = [];
        $files = [];

        /* Rekonsiliasi: (1) folder yang sudah ada di filesystem (termasuk kosong) */
        if ($realDirectory !== false) {
            foreach (new \FilesystemIterator($realDirectory, \FilesystemIterator::SKIP_DOTS) as $entry) {
                if (! $entry->isDir() || $entry->isLink()) continue;
                $name = $entry->getFilename();
                if (str_starts_with($name, '.')) continue;
                if (! isset($seen[$name])) {
                    $folders[] = ['name' => $name, 'prefix' => $searchPrefix . $name];
                    $seen[$name] = true;
                }
            }
        }

        /* (2) folder yang hanya terlihat dari path file (non-empty) */
        foreach ($allItems as $item) {
            $relative = $searchPrefix !== '' && str_starts_with($item['key'], $searchPrefix)
                ? substr($item['key'], strlen($searchPrefix))
                : ($searchPrefix === '' ? $item['key'] : null);
            if ($relative === null || $relative === '') continue;

            $slashPos = strpos($relative, '/');
            if ($slashPos !== false) {
                $folderName = substr($relative, 0, $slashPos);
                if (! isset($seen[$folderName])) {
                    $folders[] = ['name' => $folderName, 'prefix' => $searchPrefix . $folderName];
                    $seen[$folderName] = true;
                }
            } else {
                $files[] = $item;
            }
        }

        usort($folders, static fn($a, $b) => strcmp($a['name'], $b['name']));
        $sort = in_array($sort, ['name', 'size', 'updated_at', 'mime'], true) ? $sort : 'name';
        $descending = strtolower($direction) === 'desc';
        usort($files, static function (array $a, array $b) use ($sort, $descending): int {
            $left = $a[$sort] ?? '';
            $right = $b[$sort] ?? '';
            $comparison = $sort === 'size' ? ((int) $left <=> (int) $right) : strnatcasecmp((string) $left, (string) $right);
            return $descending ? -$comparison : $comparison;
        });

        $perPage = max(1, min(500, $perPage > 0 ? $perPage : 100));
        $page = max(1, $page > 0 ? $page : 1);
        $totalFiles = count($files);
        $files = array_slice($files, ($page - 1) * $perPage, $perPage);

        return [
            'bucket' => $uploads->bucketName,
            'prefix' => $prefix,
            'folders' => $folders,
            'files' => $files,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalFiles,
                'total_pages' => max(1, (int) ceil($totalFiles / $perPage)),
            ],
        ];
    }

    private function physicalKey(string $key): string
    {
        return $this->safeKey($key);
    }

    public function metadata(string $key): array
    {
        $safeKey = $this->safeKey($key);
        $storage = $this->storage();
        $object = $storage->readObject($safeKey);
        $absolutePath = method_exists($storage, 'absolutePath') ? $storage->absolutePath($safeKey) : null;
        $modifiedAt = is_string($absolutePath) && is_file($absolutePath) ? filemtime($absolutePath) : time();

        return [
            'key' => $safeKey,
            'name' => basename($safeKey),
            'mime' => $object['content_type'],
            'size' => $object['byte_size'],
            'etag' => '"' . md5($object['body']) . '"',
            'sha256' => $object['sha256'],
            'updated_at' => date(DATE_ATOM, (int) $modifiedAt),
            'storage_class' => 'STANDARD',
            'driver' => $storage->driverName(),
            'bucket' => $storage->bucketName(),
        ];
    }

    public function download(string $key): array
    {
        return $this->storage()->readObject($this->safeKey($key));
    }

    private function logicalKey(string $physicalKey): string
    {
        $prefix = trim((string) $this->uploadsConfig()->objectKeyPrefix, '/');
        $physicalKey = ltrim(str_replace('\\', '/', $physicalKey), '/');
        if ($prefix !== '' && ($physicalKey === $prefix || str_starts_with($physicalKey, $prefix . '/'))) {
            return ltrim(substr($physicalKey, strlen($prefix)), '/');
        }
        return $physicalKey;
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
            throw new InvalidArgumentException(
                'Tipe MIME "' . ($mime !== '' ? $mime : 'tidak dikenal') . '" tidak diizinkan. '
                . 'Diizinkan: ' . (implode(', ', $allowedMimes) !== '' ? implode(', ', $allowedMimes) : 'belum ada - aktifkan di Pengaturan Storage.')
            );
        }

        // Nama asli tanpa path: buang komponen direktori (webkitRelativePath bisa "folder\file.txt").
        $name = str_replace('\\', '/', $file->getClientName() ?: 'object');
        $name = basename($name);
        // Tolak dotfile sistem (.DS_Store, ._*, .git*) — tidak boleh masuk storage.
        if ($name === '' || str_starts_with($name, '.')) {
            throw new InvalidArgumentException('Nama file tidak diizinkan (file tersembunyi/sistem).');
        }
        // Buang karakter yang tidak aman di key: sisakan huruf/angka/._-
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?: 'object';
        $name = ltrim($name, '.') ?: 'object';

        $keyPrefix = trim(str_replace('\\', '/', $prefix), '/');
        if ($keyPrefix !== '' && preg_match('#^(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+$#', $keyPrefix) !== 1) {
            throw new InvalidArgumentException('Prefix object tidak valid.');
        }

        $key = ($keyPrefix !== '' ? $keyPrefix . '/' : '') . $name;

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
        $normalized = trim(str_replace('\\', '/', $key), '/');

        // Folder (prefix tanpa file) → hapus direktori hanya jika kosong.
        if ($normalized !== '' && preg_match('#^(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+$#', $normalized) === 1
            && ! str_contains(basename($normalized), '.')) {
            return $this->deleteFolderIfEmpty($normalized);
        }

        $safeKey = $this->safeKey($key);
        $deleted = $this->storage()->delete($safeKey);

        if ($deleted) {
            $this->pruneEmptyParentFolders($safeKey);
        }

        return $deleted;
    }

    /** Hapus folder fisik hanya bila benar-benar kosong. Kembalikan true bila terhapus. */
    private function deleteFolderIfEmpty(string $prefix): bool
    {
        $directory = $this->resolveDirectory($prefix);
        if ($directory === null) {
            throw new InvalidArgumentException('Folder tidak ditemukan.');
        }
        if ((new \FilesystemIterator($directory))->valid()) {
            throw new InvalidArgumentException('Folder tidak kosong. Hapus semua isinya dulu.');
        }
        if (! @rmdir($directory)) {
            throw new InvalidArgumentException('Folder gagal dihapus.');
        }

        // Naik ke atas: hapus folder induk yang kini kosong.
        $parent = dirname($prefix);
        if ($parent !== '.' && $parent !== '') {
            try {
                $this->deleteFolderIfEmpty($parent);
            } catch (\Throwable) {
                // Induk masih berisi — berhenti diam-diam.
            }
        }

        return true;
    }

    /** Resolve prefix logis ke path direktori nyata di dalam bucket (atau null bila tak ada). */
    private function resolveDirectory(string $prefix): ?string
    {
        $uploads = $this->uploadsConfig();
        $physicalPrefix = trim((string) $uploads->objectKeyPrefix, '/');
        $root = rtrim($uploads->storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uploads->bucketName;
        $base = realpath($root);
        if ($base === false) {
            return null;
        }
        $physicalBase = realpath($base . ($physicalPrefix !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $physicalPrefix) : ''));
        if ($physicalBase === false) {
            return null;
        }
        $candidate = $physicalBase . ($prefix !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $prefix) : '');
        $real = realpath($candidate);
        if ($real === false || ! is_dir($real) || $real === $physicalBase || ! str_starts_with($real, $physicalBase . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $real;
    }

    /**
     * Hapus folder induk yang kosong setelah object terakhir dihapus.
     * Berhenti di root bucket / di bawah prefix fisik — tidak pernah menghapus
     * bucket atau folder yang masih berisi file/folder lain.
     */
    private function pruneEmptyParentFolders(string $safeKey): void
    {
        $segments = explode('/', trim(str_replace('\\', '/', $safeKey), '/'));
        array_pop($segments); // buang nama file; sisa = rantai folder logis
        if ($segments === []) {
            return; // object ada di root, tidak ada folder untuk dipangkas
        }

        $uploads = $this->uploadsConfig();
        $root = rtrim($uploads->storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uploads->bucketName;
        $base = realpath($root);
        if ($base === false) {
            return;
        }

        $physicalPrefix = trim((string) $uploads->objectKeyPrefix, '/');
        $physicalRoot = $base . ($physicalPrefix !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $physicalPrefix) : '');
        $physicalBase = realpath($physicalRoot);
        if ($physicalBase === false) {
            return;
        }

        while ($segments !== []) {
            $candidate = $physicalBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, implode('/', $segments));
            $realCandidate = realpath($candidate);

            // Hanya di dalam prefix fisik, jangan sentuh base itu sendiri.
            if ($realCandidate === false
                || $realCandidate === $physicalBase
                || ! str_starts_with($realCandidate, $physicalBase . DIRECTORY_SEPARATOR)
            ) {
                break;
            }

            if (! is_dir($realCandidate) || (new \FilesystemIterator($realCandidate))->valid()) {
                break; // masih ada isinya (file atau subfolder) — stop menaik
            }

            if (! @rmdir($realCandidate)) {
                break;
            }

            array_pop($segments);
        }
    }

    public function createFolder(string $prefix, string $name): array
    {
        $prefix = trim(str_replace('\\', '/', $prefix), '/');
        if ($prefix !== '' && preg_match('#^(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+$#', $prefix) !== 1) {
            throw new InvalidArgumentException('Prefix object tidak valid.');
        }
        // Nama folder disanitasi: buang path, sisakan charset aman (samakan dengan safeSegment JS).
        $name = trim(str_replace(['\\', '/'], '-', $name));
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '_', $name) ?? '';
        $name = trim($name, '_-');
        if ($name === '' || strlen($name) > 100) {
            throw new InvalidArgumentException('Nama folder tidak valid.');
        }

        $uploads = $this->uploadsConfig();
        $physicalPrefix = trim((string) $uploads->objectKeyPrefix, '/');
        $root = rtrim($uploads->storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uploads->bucketName;
        $base = realpath($root);
        $physicalBase = $base === false ? false : realpath($base . ($physicalPrefix !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $physicalPrefix) : ''));
        if ($physicalBase === false || ! is_dir($physicalBase)) {
            throw new InvalidArgumentException('Folder storage tidak ditemukan.');
        }

        $targetParent = $physicalBase . ($prefix !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $prefix) : '');
        $realParent = realpath($targetParent);
        if ($realParent === false || ! is_dir($realParent) || (! str_starts_with($realParent, $physicalBase . DIRECTORY_SEPARATOR) && $realParent !== $physicalBase)) {
            throw new InvalidArgumentException('Folder induk tidak ditemukan.');
        }

        $destination = $realParent . DIRECTORY_SEPARATOR . $name;
        if (file_exists($destination)) {
            throw new InvalidArgumentException('Nama folder sudah digunakan.');
        }
        if (! mkdir($destination, 0750) && ! is_dir($destination)) {
            throw new InvalidArgumentException('Folder gagal dibuat.');
        }

        $newPrefix = ($prefix !== '' ? $prefix . '/' : '') . $name;
        return ['prefix' => $newPrefix, 'name' => $name];
    }

    public function rename(string $key, string $newName): array
    {
        $safeKey = $this->safeKey($key);
        $newName = trim(str_replace(['\\', '/'], '-', $newName));
        $newName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $newName) ?? '';
        $newName = ltrim($newName, '._-');
        if ($newName === '' || strlen($newName) > 200) {
            throw new InvalidArgumentException('Nama object baru tidak valid.');
        }
        $prefix = trim(str_replace('\\', '/', dirname($safeKey)), '.\\/');
        $newKey = ($prefix !== '' ? $prefix . '/' : '') . $newName;
        $object = $this->storage()->readObject($safeKey);
        $temporary = tempnam(sys_get_temp_dir(), 'fms-storage-');
        if ($temporary === false || file_put_contents($temporary, $object['body'], LOCK_EX) === false) {
            throw new InvalidArgumentException('Object sementara gagal dibuat.');
        }
        try {
            $this->storage()->writeAny($newKey, $temporary, $object['content_type']);
            $this->storage()->delete($safeKey);
        } finally {
            @unlink($temporary);
        }
        return ['old_key' => $safeKey, 'key' => $newKey, 'name' => $newName];
    }

    public function renameFolder(string $prefix, string $newName): array
    {
        $prefix = trim(str_replace('\\', '/', $prefix), '/');
        $newName = trim(str_replace(['\\', '/'], '-', $newName));
        $newName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $newName) ?? '';
        $newName = trim($newName, '_-');
        if (preg_match('#^[A-Za-z0-9_-]+$#', $newName) !== 1 || strlen($newName) > 100) {
            throw new InvalidArgumentException('Nama folder baru tidak valid.');
        }

        $uploads = $this->uploadsConfig();
        $physicalPrefix = trim((string) $uploads->objectKeyPrefix, '/');
        $root = rtrim($uploads->storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uploads->bucketName;
        $base = realpath($root);
        $physicalBase = $base === false ? false : realpath($base . ($physicalPrefix !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $physicalPrefix) : ''));
        if ($physicalBase === false || !is_dir($physicalBase)) {
            throw new InvalidArgumentException('Folder storage tidak ditemukan.');
        }

        $source = $physicalBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $prefix);
        $sourceReal = realpath($source);
        if ($sourceReal === false || !is_dir($sourceReal) || (!str_starts_with($sourceReal, $physicalBase . DIRECTORY_SEPARATOR) && $sourceReal !== $physicalBase)) {
            throw new InvalidArgumentException('Folder tidak ditemukan.');
        }
        $parent = dirname($sourceReal);
        $destination = $parent . DIRECTORY_SEPARATOR . $newName;
        if (file_exists($destination)) {
            throw new InvalidArgumentException('Nama folder sudah digunakan.');
        }
        if (!rename($sourceReal, $destination)) {
            throw new InvalidArgumentException('Folder gagal di-rename.');
        }

        $parentPrefix = dirname($prefix);
        $newPrefix = ($parentPrefix !== '.' ? $parentPrefix . '/' : '') . $newName;
        return ['old_prefix' => $prefix, 'prefix' => $newPrefix, 'name' => $newName];
    }

    public function bulkDelete(array $keys): int
    {
        $deleted = 0;
        foreach (array_values(array_unique(array_map('strval', $keys))) as $key) {
            if ($this->delete($key)) $deleted++;
        }
        return $deleted;
    }

    public function copy(string $key, string $targetPrefix): array
    {
        $safeKey = $this->safeKey($key);
        $targetPrefix = trim(str_replace('\\', '/', $targetPrefix), '/');
        if ($targetPrefix !== '' && preg_match('#^(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+$#', $targetPrefix) !== 1) {
            throw new InvalidArgumentException('Prefix tujuan tidak valid.');
        }
        $object = $this->storage()->readObject($safeKey);
        $newKey = ($targetPrefix !== '' ? $targetPrefix . '/' : '') . basename($safeKey);
        $temporary = tempnam(sys_get_temp_dir(), 'fms-storage-');
        if ($temporary === false || file_put_contents($temporary, $object['body'], LOCK_EX) === false) {
            throw new InvalidArgumentException('Object sementara gagal dibuat.');
        }
        try {
            $this->storage()->writeAny($newKey, $temporary, $object['content_type']);
        } finally {
            @unlink($temporary);
        }
        return ['source_key' => $safeKey, 'key' => $newKey, 'name' => basename($newKey)];
    }

    public function listPrefixes(): array
    {
        $listing = $this->list('', '', 'name', 'asc', 1, 500);
        return array_values(array_map(static fn(array $folder): string => (string) $folder['prefix'], $listing['folders']));
    }

    public function preview(string $key): array
    {
        $object = $this->download($key);
        $mime = (string) $object['content_type'];
        $previewable = str_starts_with($mime, 'image/')
            || str_starts_with($mime, 'text/')
            || in_array($mime, ['application/pdf', 'application/json', 'application/xml'], true);
        return [
            'key' => $this->safeKey($key),
            'mime' => $mime,
            'body' => $previewable ? $object['body'] : '',
            'previewable' => $previewable,
        ];
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
        // Normalize: backslash → forward slash, strip leading/trailing slashes.
        $key = trim(str_replace('\\', '/', $key), '/');
        // Shape: [prefix/]filename.ext — charset aman, ekstensi token pendek.
        // Keamanan konten dijaga MIME check (finfo) di upload(), bukan ekstensi.
        $pattern = '#^(?:[A-Za-z0-9._-]+/)*[A-Za-z0-9._-]+\.[A-Za-z0-9]{1,10}$#';

        if (
            $key === ''
            || strlen($key) > 512
            || str_starts_with($key, '/')
            || str_contains($key, '\\')
            || str_contains($key, '..')
            || str_contains($key, "\0")
            || preg_match($pattern, $key) !== 1
        ) {
            throw new InvalidArgumentException('Object key tidak valid.');
        }

        return $key;
    }
}
