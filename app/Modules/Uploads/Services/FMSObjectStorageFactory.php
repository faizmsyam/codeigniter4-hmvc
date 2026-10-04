<?php

namespace App\Modules\Uploads\Services;

use App\Config\FMSUploads;
use App\Modules\Uploads\Contracts\FMSObjectStorageInterface;
use InvalidArgumentException;

final class FMSObjectStorageFactory
{
    public static function create(?FMSUploads $configuration = null): FMSObjectStorageInterface
    {
        $resolvedConfiguration = $configuration ?? config(FMSUploads::class);

        return match (strtolower(trim($resolvedConfiguration->storageDriver))) {
            'local' => new FMSLocalObjectStorage(
                $resolvedConfiguration->storageRoot,
                $resolvedConfiguration->bucketName,
                $resolvedConfiguration->objectKeyPrefix,
                $resolvedConfiguration->localDownloadEndpoint,
                self::requiredSecret($resolvedConfiguration->localDownloadHmacKey, 'local HMAC key'),
                $resolvedConfiguration->localDownloadHmacKeyIdentifier,
                $resolvedConfiguration->localDownloadPreviousHmacKey,
                $resolvedConfiguration->localDownloadPreviousHmacKeyIdentifier,
                $resolvedConfiguration->directoryMode,
                (int) $resolvedConfiguration->fileMode,
                (bool) $resolvedConfiguration->createBucketAutomatically,
            ),
            's3' => new FMSS3ObjectStorage(
                $resolvedConfiguration->bucketName,
                $resolvedConfiguration->s3RegionName,
                self::requiredSecret($resolvedConfiguration->s3AccessKeyIdentifier, 'S3 access key identifier'),
                self::requiredSecret($resolvedConfiguration->s3SecretAccessKey, 'S3 secret access key'),
                $resolvedConfiguration->s3EndpointHost,
                $resolvedConfiguration->s3PathStyleEndpoint,
                $resolvedConfiguration->s3UseTls,
                $resolvedConfiguration->objectKeyPrefix,
            ),
            default => throw new InvalidArgumentException('Unsupported private upload storage driver.'),
        };
    }

    private static function requiredSecret(string $secretValue, string $secretLabel): string
    {
        if (trim($secretValue) === '') {
            throw new InvalidArgumentException('Missing ' . $secretLabel . ' configuration.');
        }

        return $secretValue;
    }
}
