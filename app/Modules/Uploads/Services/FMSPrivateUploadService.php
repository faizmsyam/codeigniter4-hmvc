<?php

namespace App\Modules\Uploads\Services;

use App\Modules\Uploads\Contracts\FMSObjectStorageInterface;
use InvalidArgumentException;

final class FMSPrivateUploadService
{
    public function __construct(
        private readonly FMSObjectStorageInterface $objectStorage,
        private readonly int $defaultSignedUrlTtlSeconds = 300,
        private readonly int $maximumSignedUrlTtlSeconds = 900,
    ) {
        if ($defaultSignedUrlTtlSeconds < 30 || $defaultSignedUrlTtlSeconds > $maximumSignedUrlTtlSeconds) {
            throw new InvalidArgumentException('Default signed URL TTL is invalid.');
        }
    }

    /**
     * @return array{storage_driver: string, bucket_name: string, object_key: string, mime_type: string, byte_size: int, sha256: string}
     */
    public function publishWebP(string $privateEncodedFilePath, string $objectKey): array
    {
        $normalizedObjectKey = $this->normalizeObjectKey($objectKey);
        $storageMetadata = $this->objectStorage->write($normalizedObjectKey, $privateEncodedFilePath, 'image/webp');

        return [
            'storage_driver' => $this->objectStorage->driverName(),
            'bucket_name' => $this->objectStorage->bucketName(),
            'object_key' => $normalizedObjectKey,
            'mime_type' => 'image/webp',
            'byte_size' => $storageMetadata['byte_size'],
            'sha256' => $storageMetadata['sha256'],
        ];
    }

    public function signedReadUrl(string $objectKey, ?int $requestedTtlSeconds = null): string
    {
        $ttlSeconds = $requestedTtlSeconds ?? $this->defaultSignedUrlTtlSeconds;
        if ($ttlSeconds < 30 || $ttlSeconds > $this->maximumSignedUrlTtlSeconds) {
            throw new InvalidArgumentException('Signed URL TTL is outside the allowed range.');
        }

        return $this->objectStorage->createPresignedDownloadUrl(
            $this->normalizeObjectKey($objectKey),
            time() + $ttlSeconds,
        );
    }

    public function delete(string $objectKey): bool
    {
        return $this->objectStorage->delete($this->normalizeObjectKey($objectKey));
    }

    private function normalizeObjectKey(string $objectKey): string
    {
        $normalizedObjectKey = trim(str_replace('\\', '/', $objectKey), '/');
        if (
            $normalizedObjectKey === ''
            || strlen($normalizedObjectKey) > 512
            || str_contains($normalizedObjectKey, "\0")
            || str_contains($normalizedObjectKey, '..')
            || preg_match('#\A(?:fms-[a-z0-9][a-z0-9-]{0,62}/)+fms-[A-Za-z0-9-]{1,200}\.(webp|pdf)\z#', $normalizedObjectKey) !== 1
        ) {
            throw new InvalidArgumentException('Private upload object key is invalid.');
        }

        return $normalizedObjectKey;
    }
}
