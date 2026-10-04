<?php

namespace App\Modules\Uploads\Services;

use App\Modules\Uploads\Contracts\FMSObjectStorageInterface;
use InvalidArgumentException;
use RuntimeException;

/** Local S3-compatible abstraction: private bucket/object keys + HMAC presigned GET. */
final class FMSLocalObjectStorage implements FMSObjectStorageInterface
{
    private const MAXIMUM_READABLE_OBJECT_BYTES = 8388608;

    public function __construct(
        private readonly string $storageRoot,
        private readonly string $configuredBucketName,
        private readonly string $objectKeyPrefix,
        private readonly string $downloadEndpoint,
        private readonly string $hmacKey,
        private readonly string $hmacKeyIdentifier,
        private readonly string $previousHmacKey = '',
        private readonly string $previousHmacKeyIdentifier = '',
        private readonly int $directoryMode = 0750,
        private readonly int $fileMode = 0640,
        bool $createBucketAutomatically = true,
    ) {
        if (! str_starts_with($storageRoot, DIRECTORY_SEPARATOR)) {
            throw new InvalidArgumentException('Local storage root must be absolute.');
        }
        if (preg_match('/\Afms-[a-z0-9][a-z0-9-]{1,61}\z/', $configuredBucketName) !== 1) {
            throw new InvalidArgumentException('Local bucket name must be an fms- S3-style name.');
        }
        if (strlen($hmacKey) < 32) {
            throw new InvalidArgumentException('Local download HMAC key must contain at least 32 bytes.');
        }
        if ($createBucketAutomatically && ! $this->bucketExists()) {
            $this->createBucket();
        }
    }

    public function driverName(): string { return 'local'; }
    public function bucketName(): string { return $this->configuredBucketName; }

    public function createBucket(): void
    {
        $bucketPath = $this->bucketPath();
        if (! is_dir($bucketPath) && ! mkdir($bucketPath, $this->directoryMode, true) && ! is_dir($bucketPath)) {
            throw new RuntimeException('Local private bucket could not be created.');
        }
        chmod($bucketPath, $this->directoryMode);
    }

    public function bucketExists(): bool { return is_dir($this->bucketPath()); }

    public function write(string $objectKey, string $sourceFilePath, string $contentType): array
    {
        if ($contentType !== 'image/webp' || ! is_file($sourceFilePath)) {
            throw new InvalidArgumentException('Private object source is invalid.');
        }

        $sourceBytes = file_get_contents($sourceFilePath);
        if (! is_string($sourceBytes) || strlen($sourceBytes) < 12 || substr($sourceBytes, 0, 4) !== 'RIFF' || substr($sourceBytes, 8, 4) !== 'WEBP') {
            throw new InvalidArgumentException('Private object must be a validated WebP payload.');
        }

        return $this->writeAny($objectKey, $sourceFilePath, $contentType);
    }

    public function writeAny(string $objectKey, string $sourceFilePath, string $contentType): array
    {
        if (! is_file($sourceFilePath) || ! is_readable($sourceFilePath) || is_link($sourceFilePath)) {
            throw new InvalidArgumentException('Private object source is invalid.');
        }

        if (! in_array($contentType, ['image/webp', 'application/pdf'], true)) {
            throw new InvalidArgumentException('Private object content type is not allowed.');
        }

        $sourceBytes = file_get_contents($sourceFilePath);
        if (! is_string($sourceBytes) || $sourceBytes === '') {
            throw new InvalidArgumentException('Private object source cannot be read.');
        }

        if ($contentType === 'image/webp'
            && (strlen($sourceBytes) < 12 || substr($sourceBytes, 0, 4) !== 'RIFF' || substr($sourceBytes, 8, 4) !== 'WEBP')) {
            throw new InvalidArgumentException('Private object must be a validated WebP payload.');
        }

        if ($contentType === 'application/pdf' && ! str_starts_with($sourceBytes, '%PDF-')) {
            throw new InvalidArgumentException('Private object must be a validated PDF payload.');
        }

        if (! $this->bucketExists()) {
            throw new RuntimeException('Local private bucket does not exist.');
        }

        $destinationPath = $this->absolutePath($objectKey);
        $destinationDirectory = dirname($destinationPath);
        if (! is_dir($destinationDirectory) && ! mkdir($destinationDirectory, $this->directoryMode, true) && ! is_dir($destinationDirectory)) {
            throw new RuntimeException('Private object prefix could not be created.');
        }

        $temporaryPath = tempnam($destinationDirectory, 'fms-tmp-');
        if (! is_string($temporaryPath)) {
            throw new RuntimeException('Temporary private object could not be created.');
        }

        try {
            if (! copy($sourceFilePath, $temporaryPath)) {
                throw new RuntimeException('Private object write failed.');
            }
            chmod($temporaryPath, $this->fileMode);
            if (! rename($temporaryPath, $destinationPath)) {
                throw new RuntimeException('Private object publication failed.');
            }
        } finally {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }

        return ['byte_size' => filesize($destinationPath) ?: 0, 'sha256' => hash_file('sha256', $destinationPath) ?: ''];
    }

    public function delete(string $objectKey): bool
    {
        $absolutePath = $this->absolutePath($objectKey);
        return ! is_file($absolutePath) || unlink($absolutePath);
    }

    public function exists(string $objectKey): bool { return is_file($this->absolutePath($objectKey)); }

    public function createPresignedDownloadUrl(string $objectKey, int $expiresAtUnixTimestamp): string
    {
        $this->assertSafeObjectKey($objectKey);

        if ($expiresAtUnixTimestamp <= time() || trim($this->downloadEndpoint) === '') {
            throw new InvalidArgumentException('Presigned local URL configuration is invalid.');
        }

        $canonicalPath = $this->canonicalObjectPath($objectKey);
        $canonicalQuery = http_build_query(['expires' => $expiresAtUnixTimestamp, 'key_id' => $this->hmacKeyIdentifier], '', '&', PHP_QUERY_RFC3986);
        $signature = $this->base64UrlEncode(hash_hmac('sha256', "GET\n{$canonicalPath}\n{$canonicalQuery}", $this->hmacKey, true));

        $encodedObjectKey = str_replace('%2F', '/', rawurlencode($objectKey));

        return rtrim($this->downloadEndpoint, '/') . '/buckets/' . rawurlencode($this->configuredBucketName) . '/objects/' . $encodedObjectKey . '?' . $canonicalQuery . '&signature=' . rawurlencode($signature);
    }

    public function verifyPresignedRequest(string $objectKey, int $expiresAtUnixTimestamp, string $keyIdentifier, string $providedSignature): bool
    {
        try {
            $this->assertSafeObjectKey($objectKey);
        } catch (InvalidArgumentException) {
            return false;
        }

        if ($expiresAtUnixTimestamp <= time()) {
            return false;
        }

        $verificationHmacKey = match (true) {
            hash_equals($this->hmacKeyIdentifier, $keyIdentifier) => $this->hmacKey,
            $this->previousHmacKey !== ''
                && $this->previousHmacKeyIdentifier !== ''
                && hash_equals($this->previousHmacKeyIdentifier, $keyIdentifier) => $this->previousHmacKey,
            default => '',
        };
        if ($verificationHmacKey === '') {
            return false;
        }

        try {
            $encodedObjectKey = str_replace('%2F', '/', rawurlencode($objectKey));
            $canonicalPath = $this->canonicalObjectPath($objectKey);
            $canonicalQuery = http_build_query(['expires' => $expiresAtUnixTimestamp, 'key_id' => $keyIdentifier], '', '&', PHP_QUERY_RFC3986);
            $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', "GET\n{$canonicalPath}\n{$canonicalQuery}", $verificationHmacKey, true));
        } catch (InvalidArgumentException) {
            return false;
        }

        return $providedSignature !== '' && hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Satu-satunya pembangun jalur kanonis untuk penandatanganan dan verifikasi
     * presigned URL. Wajib diturunkan dari endpoint yang dikonfigurasi agar
     * tidak terjadi drift antara kedua sisi.
     */
    private function canonicalObjectPath(string $objectKey): string
    {
        $downloadEndpointPath = trim((string) parse_url($this->downloadEndpoint, PHP_URL_PATH), '/');
        if ($downloadEndpointPath === '') {
            throw new InvalidArgumentException('Presigned local URL endpoint is invalid.');
        }

        $encodedObjectKey = str_replace('%2F', '/', rawurlencode($objectKey));

        return '/' . $downloadEndpointPath . '/buckets/' . rawurlencode($this->configuredBucketName) . '/objects/' . $encodedObjectKey;
    }

    public function absolutePath(string $objectKey): string
    {
        $this->assertSafeObjectKey($objectKey);
        return $this->bucketPath() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $this->prefixedObjectKey($objectKey));
    }

    public function readObject(string $objectKey): array
    {
        $absolutePath = $this->absolutePath($objectKey);
        if (! is_file($absolutePath) || is_link($absolutePath) || ! is_readable($absolutePath)) {
            throw new InvalidArgumentException('Private object does not exist.');
        }

        $objectBytes = file_get_contents($absolutePath);
        if (! is_string($objectBytes) || strlen($objectBytes) < 5 || strlen($objectBytes) > $this->maximumReadableBytes($objectKey)) {
            throw new InvalidArgumentException('Private object payload is outside the readable range.');
        }

        $contentType = str_starts_with($objectBytes, '%PDF-') ? 'application/pdf' : 'image/webp';

        if ($contentType === 'image/webp'
            && (strlen($objectBytes) < 12 || substr($objectBytes, 0, 4) !== 'RIFF' || substr($objectBytes, 8, 4) !== 'WEBP')) {
            throw new InvalidArgumentException('Private object payload is not valid WebP.');
        }

        return [
            'body' => $objectBytes,
            'content_type' => $contentType,
            'byte_size' => strlen($objectBytes),
            'sha256' => hash('sha256', $objectBytes),
        ];
    }

    private function bucketPath(): string
    {
        return rtrim($this->storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->configuredBucketName;
    }

    private function maximumReadableBytes(string $objectKey): int
    {
        unset($objectKey);

        return self::MAXIMUM_READABLE_OBJECT_BYTES;
    }

    private function prefixedObjectKey(string $objectKey): string
    {
        return trim($this->objectKeyPrefix, '/') . '/' . $objectKey;
    }

    private function assertSafeObjectKey(string $objectKey): void
    {
        if ($objectKey === '' || strlen($objectKey) > 512 || str_starts_with($objectKey, '/') || str_contains($objectKey, '..') || str_contains($objectKey, '\\') || str_contains($objectKey, "\0") || preg_match('#\A(?:fms-[a-z0-9][a-z0-9-]{0,62}/)*fms-[A-Za-z0-9-]{1,200}\.(webp|pdf)\z#', $objectKey) !== 1) {
            throw new InvalidArgumentException('Private object key is invalid.');
        }
    }

    private function base64UrlEncode(string $binaryData): string
    {
        return rtrim(strtr(base64_encode($binaryData), '+/', '-_'), '=');
    }
}
