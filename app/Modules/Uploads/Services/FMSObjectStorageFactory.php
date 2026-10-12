<?php

namespace App\Modules\Uploads\Services;

use App\Config\FMSUploads;
use App\Modules\Uploads\Contracts\FMSObjectStorageInterface;
use InvalidArgumentException;

final class FMSObjectStorageFactory
{
    public static function create(?FMSUploads $configuration = null): FMSObjectStorageInterface
    {
        if ($configuration !== null) {
            $resolvedConfiguration = $configuration;
        } else {
            /* Clone singleton lalu inject overrides dari storage_settings,
               supaya driver/root/bucket/S3 mengikuti nilai UI Settings. */
            $resolvedConfiguration = clone config(FMSUploads::class);
            try {
                (new \App\Modules\Storage\Services\FMSStorageSettingsService())->applyTo($resolvedConfiguration);
            } catch (\Throwable $e) {
                /* DB belum siap — pakai default config saja. */
            }
        }

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
