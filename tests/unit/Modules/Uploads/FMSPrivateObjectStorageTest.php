<?php

namespace Tests\Unit\Modules\Uploads;

use App\Config\FMSUploads;
use App\Modules\Uploads\Services\FMSLocalObjectStorage;
use App\Modules\Uploads\Services\FMSObjectStorageFactory;
use App\Modules\Uploads\Services\FMSPrivateUploadService;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

final class FMSPrivateObjectStorageTest extends CIUnitTestCase
{
    private string $temporaryStorageRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporaryStorageRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fms-buckets_' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->temporaryStorageRoot);
        parent::tearDown();
    }

    public function testLocalDriverCreatesPrivateBucketAndStoresObjectLikeS3(): void
    {
        $storage = $this->createLocalStorage();
        $this->assertTrue($storage->bucketExists());
        $this->assertSame('fms-private', $storage->bucketName());

        $sourceFilePath = tempnam(sys_get_temp_dir(), 'fms_webp_');
        file_put_contents($sourceFilePath, 'RIFF' . str_repeat("\0", 4) . 'WEBP' . random_bytes(48));
        $metadata = $storage->write('fms-brand/fms-0123456789abcdef.webp', $sourceFilePath, 'image/webp');

        $this->assertTrue($storage->exists('fms-brand/fms-0123456789abcdef.webp'));
        $this->assertGreaterThan(0, $metadata['byte_size']);
        $this->assertSame(64, strlen($metadata['sha256']));
        $this->assertFileDoesNotExist(PUBLICPATH . 'fms-brand/fms-0123456789abcdef.webp');
        @unlink($sourceFilePath);
    }

    public function testLocalPresignedUrlUsesHmacAndRejectsTamperingOrExpiry(): void
    {
        $storage = $this->createLocalStorage();
        $objectKey = 'fms-brand/fms-0123456789abcdef.webp';
        $expiryTimestamp = time() + 300;
        $signedUrl = $storage->createPresignedDownloadUrl($objectKey, $expiryTimestamp);
        parse_str((string) parse_url($signedUrl, PHP_URL_QUERY), $queryParameters);

        $this->assertTrue($storage->verifyPresignedRequest($objectKey, $expiryTimestamp, (string) $queryParameters['key_id'], (string) $queryParameters['signature']));
        $this->assertFalse($storage->verifyPresignedRequest($objectKey . 'x', $expiryTimestamp, (string) $queryParameters['key_id'], (string) $queryParameters['signature']));
        $this->assertFalse($storage->verifyPresignedRequest($objectKey, time() - 1, (string) $queryParameters['key_id'], (string) $queryParameters['signature']));
    }

    public function testFactoryIsConfiguredOnlyFromEnvironmentBackedConfiguration(): void
    {
        $configuration = new FMSUploads();
        $configuration->storageDriver = 'local';
        $configuration->storageRoot = $this->temporaryStorageRoot;
        $configuration->bucketName = 'fms-private';
        $configuration->objectKeyPrefix = 'fms-uploads';
        $configuration->localDownloadEndpoint = 'https://files.example.test/api/v1/uploads';
        $configuration->localDownloadHmacKey = str_repeat('k', 64);

        $storage = FMSObjectStorageFactory::create($configuration);
        $this->assertInstanceOf(FMSLocalObjectStorage::class, $storage);
        $privateService = new FMSPrivateUploadService($storage, 300, 900);
        $this->assertStringContainsString('signature=', $privateService->signedReadUrl('fms-brand/fms-0123456789abcdef.webp'));
    }

    public function testUploadsConfigAutomaticallyResolvesLocalDownloadEndpointFromBaseUrlWhenEmpty(): void
    {
        $configuration = new FMSUploads();
        $this->assertNotEmpty($configuration->localDownloadEndpoint);
        $this->assertStringEndsWith('/api/v1/uploads', $configuration->localDownloadEndpoint);
    }

    public function testUnsafeBucketOrObjectKeyIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FMSLocalObjectStorage($this->temporaryStorageRoot, '../public', 'fms-uploads', 'https://files.example.test/api/v1/uploads', str_repeat('k', 64), 'key-1');
    }

    public function testUploadsDownloadControllerAcceptsMultiSegmentObjectKeysWithoutDroppingFilename(): void
    {
        $reflection = new \ReflectionMethod(
            \App\Modules\Uploads\Controllers\Api\FMSUploadsApiController::class,
            'download'
        );
        $parameters = $reflection->getParameters();

        $this->assertGreaterThanOrEqual(2, count($parameters));
        $secondParameter = $parameters[1];

        $this->assertTrue(
            $secondParameter->isVariadic(),
            'Parameter kedua FMSUploadsApiController::download harus variadic (...$objectKeySegments) agar CI4 router tidak memecah slash dan membuang nama file.'
        );
    }

    private function createLocalStorage(): FMSLocalObjectStorage
    {
        return new FMSLocalObjectStorage(
            $this->temporaryStorageRoot,
            'fms-private',
            'fms-uploads',
            'https://files.example.test/api/v1/uploads',
            str_repeat('k', 64),
            'fms-download-2026-01',
        );
    }

    private function removeDirectory(string $directoryPath): void
    {
        if (! is_dir($directoryPath)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directoryPath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $fileInfo) {
            $fileInfo->isDir() ? @rmdir($fileInfo->getPathname()) : @unlink($fileInfo->getPathname());
        }
        @rmdir($directoryPath);
    }
}
