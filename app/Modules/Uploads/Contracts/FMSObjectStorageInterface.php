<?php

namespace App\Modules\Uploads\Contracts;

interface FMSObjectStorageInterface
{
    public function driverName(): string;

    public function bucketName(): string;

    public function createBucket(): void;

    public function bucketExists(): bool;

    /** @return array{byte_size: int, sha256: string} */
    public function write(string $objectKey, string $sourceFilePath, string $contentType): array;

    /** @return array{byte_size: int, sha256: string} */
    public function writeAny(string $objectKey, string $sourceFilePath, string $contentType): array;

    public function delete(string $objectKey): bool;

    public function exists(string $objectKey): bool;

    public function createPresignedDownloadUrl(string $objectKey, int $expiresAtUnixTimestamp): string;

    public function verifyPresignedRequest(string $objectKey, int $expiresAtUnixTimestamp, string $keyIdentifier, string $providedSignature): bool;

    /**
     * @return array{body: string, content_type: string, byte_size: int, sha256: string}
     */
    public function readObject(string $objectKey): array;
}
