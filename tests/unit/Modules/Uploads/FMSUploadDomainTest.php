<?php

namespace Tests\Unit\Modules\Uploads;

use App\Config\FMSUploads;
use App\Modules\Uploads\Exceptions\FMSUploadException;
use App\Modules\Uploads\Services\FMSLocalObjectStorage;
use App\Modules\Uploads\Services\FMSPrivateUploadService;
use App\Modules\Uploads\Services\FMSUploadNamingService;
use App\Modules\Uploads\Services\FMSWebPUploadService;
use App\Modules\Uploads\Validation\FMSUploadValidation;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

final class FMSUploadDomainTest extends CIUnitTestCase
{
    private string $temporaryStorageRoot;

    private FMSUploads $configuration;

    private FMSLocalObjectStorage $objectStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryStorageRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fms_upload_domain_' . bin2hex(random_bytes(6));
        $this->configuration = new FMSUploads();
        $this->configuration->storageDriver = 'local';
        $this->configuration->storageRoot = $this->temporaryStorageRoot;
        $this->configuration->bucketName = 'fms-private';
        $this->configuration->objectKeyPrefix = 'fms-uploads';
        $this->configuration->localDownloadEndpoint = 'https://files.example.test/api/v1/uploads';
        $this->configuration->localDownloadHmacKey = str_repeat('k', 64);
        $this->configuration->localDownloadHmacKeyIdentifier = 'fms-download-test';

        $this->objectStorage = new FMSLocalObjectStorage(
            $this->configuration->storageRoot,
            $this->configuration->bucketName,
            $this->configuration->objectKeyPrefix,
            $this->configuration->localDownloadEndpoint,
            $this->configuration->localDownloadHmacKey,
            $this->configuration->localDownloadHmacKeyIdentifier,
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->temporaryStorageRoot);
        parent::tearDown();
    }

    public function testFolderAndFilenameRulesRejectTraversalAndClientPaths(): void
    {
        $namingService = new FMSUploadNamingService();
        $deterministicTarget = $namingService->buildDeterministicTarget('fms-images', str_repeat('a', 32));

        $this->assertSame('fms-images', $deterministicTarget['uploadFolder']);
        $this->assertTrue($namingService->isSafeStoredFilename(basename($deterministicTarget['relativePath'])));
        $this->assertSame('', $namingService->sanitizeStoredFilename('../fms_evil.php'));
        $this->assertNotSame('', $namingService->newFileName('webp'));

        foreach (['../fms_evil', 'fms_evil/../../x', '/abs', 'brand', 'FMS_X', ''] as $invalidFolderName) {
            try {
                $namingService->normalizeFolderName($invalidFolderName);
                $this->fail('Folder should be rejected: ' . $invalidFolderName);
            } catch (InvalidArgumentException $invalidFolderException) {
                $this->assertNotSame('', $invalidFolderException->getMessage());
            }
        }

        $validationErrors = FMSUploadValidation::validateUploadMetadata([
            'folder' => '../fms_evil',
            'owner_type' => 'ok',
            'owner_id' => '1',
        ], $this->configuration);
        $this->assertArrayHasKey('folder', $validationErrors);
    }

    public function testJoinUnderRootRejectsTraversalAndAbsoluteEscapes(): void
    {
        $namingService = new FMSUploadNamingService();
        $storageRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fms_join_root' . DIRECTORY_SEPARATOR;

        $joinedPath = $namingService->joinUnderRoot($storageRoot, 'fms-images/fms-0123456789abcdef.webp');
        $this->assertStringStartsWith(rtrim($storageRoot, DIRECTORY_SEPARATOR), $joinedPath);

        foreach (['../evil.webp', '/abs/fms_x.webp', 'fms-images/../../evil.webp', 'a/./b.webp'] as $invalidRelativePath) {
            try {
                $namingService->joinUnderRoot($storageRoot, $invalidRelativePath);
                $this->fail('Relative path should be rejected: ' . $invalidRelativePath);
            } catch (InvalidArgumentException $invalidPathException) {
                $this->assertNotSame('', $invalidPathException->getMessage());
            }
        }
    }

    public function testStorageOutsidePublicRootHandlesAbsenceOfPublicPathConstant(): void
    {
        $service = $this->createUploadService($this->configuration);
        $method = new \ReflectionMethod($service, 'assertStorageOutsidePublicRoot');
        $method->setAccessible(true);

        /* Harusnya berhasil tanpa error undefined constant */
        $method->invoke($service, WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'fms-buckets');
        $this->assertTrue(true);
    }

    public function testUploadsConfigurationAutomaticallyResolvesWebPBinariesFromHost(): void
    {
        $configuration = new FMSUploads();
        $defaultProperties = (new \ReflectionClass(FMSUploads::class))->getDefaultProperties();

        $this->assertSame('', $defaultProperties['cwebpBinary']);
        $this->assertSame('', $defaultProperties['dwebpBinary']);

        $hostCwebp = trim((string) @shell_exec('command -v cwebp 2>/dev/null'));
        $hostDwebp = trim((string) @shell_exec('command -v dwebp 2>/dev/null'));

        if ($hostCwebp !== '' && is_executable($hostCwebp)) {
            $this->assertSame($hostCwebp, $configuration->cwebpBinary);
            $this->assertTrue(is_executable($configuration->cwebpBinary));
        }

        if ($hostDwebp !== '' && is_executable($hostDwebp)) {
            $this->assertSame($hostDwebp, $configuration->dwebpBinary);
            $this->assertTrue(is_executable($configuration->dwebpBinary));
        }
    }

    public function testJpegFixtureIsReEncodedAndPublishedOnlyAsPrivateWebPObject(): void
    {
        $uploadService = $this->createUploadService($this->configuration);
        $fixtureBytes = $this->buildJpegFixtureBytes(96, 72);
        $fixtureTemporaryPath = $this->writeTemporaryFixture($fixtureBytes, 'fms-brand-fixture', '.jpg');
        $uploadedFile = new UploadedFile($fixtureTemporaryPath, 'client-photo.jpg', 'image/jpeg', strlen($fixtureBytes), UPLOAD_ERR_OK, true);

        $storedFile = $uploadService->storeUploadedFile($uploadedFile, 'fms-brand-fixture');

        try {
            $this->assertSame('local', $storedFile['storage_driver']);
            $this->assertSame('fms-private', $storedFile['bucket_name']);
            $this->assertStringStartsWith('fms-brand-fixture/', $storedFile['object_key']);
            $this->assertStringContainsString('signature=', $storedFile['download_url']);
            $this->assertArrayNotHasKey('absolute_path', $storedFile);
            $this->assertArrayNotHasKey('relative_path', $storedFile);
            $this->assertSame('image/webp', $storedFile['mime_type']);
            $this->assertSame('webp', $storedFile['extension']);
            $this->assertGreaterThan(0, $storedFile['size_bytes']);
            $this->assertSame(96, $storedFile['width']);
            $this->assertSame(72, $storedFile['height']);
            $this->assertSame(64, strlen($storedFile['sha256']));

            $privateObject = $this->objectStorage->readObject($storedFile['object_key']);
            $this->assertSame('RIFF', substr($privateObject['body'], 0, 4));
            $this->assertSame('WEBP', substr($privateObject['body'], 8, 4));
            $this->assertStringNotContainsStringIgnoringCase('exif', $privateObject['body']);
            $this->assertFileDoesNotExist(PUBLICPATH . $storedFile['object_key']);
        } finally {
            $uploadService->deleteStoredFile($storedFile['object_key']);
            @unlink($fixtureTemporaryPath);
        }
    }

    public function testPngWithTransparencyPreservesAlphaChannelAfterWebPConversion(): void
    {
        /* Buat PNG truecolor transparan: lingkaran merah di atas background transparan */
        $canvas = imagecreatetruecolor(80, 80);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $fullyTransparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, 79, 79, $fullyTransparent);
        $red = imagecolorallocatealpha($canvas, 255, 0, 0, 0);
        imagefilledellipse($canvas, 40, 40, 40, 40, $red);

        ob_start();
        imagepng($canvas, null, 6, PNG_ALL_FILTERS);
        $pngBytes = (string) ob_get_clean();
        imagedestroy($canvas);

        $fixturePath = $this->writeTemporaryFixture($pngBytes, 'fms-alpha-fixture', '.png');
        $uploadedFile = new UploadedFile($fixturePath, 'logo-transparent.png', 'image/png', strlen($pngBytes), UPLOAD_ERR_OK, true);
        $uploadService = $this->createUploadService($this->configuration);

        $storedFile = $uploadService->storeUploadedFile($uploadedFile, 'fms-brand-fixture');

        try {
            $this->assertSame('image/webp', $storedFile['mime_type']);

            /* Baca WebP yang sudah disimpan lalu decode dengan dwebp agar bisa periksa pixel alpha */
            $privateObject = $this->objectStorage->readObject($storedFile['object_key']);
            $webpPath = sys_get_temp_dir() . '/fms_alpha_check_' . bin2hex(random_bytes(4)) . '.webp';
            file_put_contents($webpPath, $privateObject['body']);

            try {
                $dwebpBinary = trim((string) @shell_exec('command -v dwebp 2>/dev/null'));
                if ($dwebpBinary === '' || ! is_executable($dwebpBinary)) {
                    $this->markTestSkipped('dwebp binary not available for alpha channel verification.');
                }

                $decodedPng = $webpPath . '.decoded.png';
                exec(escapeshellarg($dwebpBinary) . ' -quiet ' . escapeshellarg($webpPath) . ' -o ' . escapeshellarg($decodedPng), $out, $rc);
                $this->assertSame(0, $rc, 'dwebp decode must succeed');
                $this->assertFileExists($decodedPng);

                $decoded = imagecreatefrompng($decodedPng);
                $this->assertInstanceOf(\GdImage::class, $decoded);

                /* Corner pixel (0,0) harus transparan (alpha=127 di GD berarti fully transparent) */
                $cornerRgb    = imagecolorat($decoded, 0, 0);
                $cornerColors = imagecolorsforindex($decoded, $cornerRgb);
                imagedestroy($decoded);
                @unlink($decodedPng);

                $this->assertGreaterThanOrEqual(100, $cornerColors['alpha'],
                    'Corner pixel harus transparan (alpha >=100), bukan background hitam/putih solid.');
            } finally {
                @unlink($webpPath);
            }
        } finally {
            $uploadService->deleteStoredFile($storedFile['object_key']);
            @unlink($fixturePath);
        }
    }

    public function testPngIndexedPaletteWithTransparencyPreservesAlphaChannel(): void
    {
        /* Buat PNG indexed/palette dengan indexed transparency (seperti logo PNG-8 banyak ditemui) */
        $canvas = imagecreate(80, 80);
        $white  = imagecolorallocate($canvas, 255, 255, 255);
        imagecolortransparent($canvas, $white);
        $red = imagecolorallocate($canvas, 200, 30, 30);
        imagefilledellipse($canvas, 40, 40, 40, 40, $red);

        ob_start();
        imagepng($canvas, null, 6, PNG_ALL_FILTERS);
        $pngBytes = (string) ob_get_clean();
        imagedestroy($canvas);

        $fixturePath = $this->writeTemporaryFixture($pngBytes, 'fms-palette-fixture', '.png');
        $uploadedFile = new UploadedFile($fixturePath, 'logo-palette.png', 'image/png', strlen($pngBytes), UPLOAD_ERR_OK, true);
        $uploadService = $this->createUploadService($this->configuration);

        $storedFile = $uploadService->storeUploadedFile($uploadedFile, 'fms-brand-fixture');

        try {
            $this->assertSame('image/webp', $storedFile['mime_type']);

            $privateObject = $this->objectStorage->readObject($storedFile['object_key']);
            $webpPath = sys_get_temp_dir() . '/fms_palette_check_' . bin2hex(random_bytes(4)) . '.webp';
            file_put_contents($webpPath, $privateObject['body']);

            try {
                $dwebpBinary = trim((string) @shell_exec('command -v dwebp 2>/dev/null'));
                if ($dwebpBinary === '' || ! is_executable($dwebpBinary)) {
                    $this->markTestSkipped('dwebp binary not available.');
                }

                $decodedPng = $webpPath . '.decoded.png';
                exec(escapeshellarg($dwebpBinary) . ' -quiet ' . escapeshellarg($webpPath) . ' -o ' . escapeshellarg($decodedPng), $out, $rc);
                $this->assertSame(0, $rc, 'dwebp decode must succeed');

                $decoded      = imagecreatefrompng($decodedPng);
                $cornerRgb    = imagecolorat($decoded, 0, 0);
                $cornerColors = imagecolorsforindex($decoded, $cornerRgb);
                imagedestroy($decoded);
                @unlink($decodedPng);

                $this->assertGreaterThanOrEqual(100, $cornerColors['alpha'],
                    'Indexed PNG corner harus transparan setelah konversi WebP, bukan background solid.');
            } finally {
                @unlink($webpPath);
            }
        } finally {
            $uploadService->deleteStoredFile($storedFile['object_key']);
            @unlink($fixturePath);
        }
    }

    public function testStagingDirectoryIsEmptiedAfterSuccessfulStore(): void
    {
        /* Staging root harus dikosongkan setelah store sukses, agar tidak menumpuk sampah */
        $canvas = imagecreatetruecolor(40, 40);
        $red    = imagecolorallocate($canvas, 10, 120, 200);
        imagefilledrectangle($canvas, 0, 0, 39, 39, $red);

        ob_start();
        imagepng($canvas, null, 6);
        $pngBytes = (string) ob_get_clean();
        imagedestroy($canvas);

        $fixturePath = $this->writeTemporaryFixture($pngBytes, 'fms-staging-fixture', '.png');
        $uploadedFile = new UploadedFile($fixturePath, 'staging-check.png', 'image/png', strlen($pngBytes), UPLOAD_ERR_OK, true);
        $uploadService = $this->createUploadService($this->configuration);

        $stagingRoot = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'fms-staging';

        try {
            $storedFile = $uploadService->storeUploadedFile($uploadedFile, 'fms-staging-fixture');

            $leftoverFiles = [];
            if (is_dir($stagingRoot)) {
                foreach (scandir($stagingRoot) ?: [] as $entry) {
                    if ($entry !== '.' && $entry !== '..' && is_file($stagingRoot . DIRECTORY_SEPARATOR . $entry)) {
                        $leftoverFiles[] = $entry;
                    }
                }
            }

            $this->assertSame([], $leftoverFiles, 'Tidak boleh ada file staging tersisa setelah store sukses.');

            $uploadService->deleteStoredFile($storedFile['object_key']);
        } finally {
            @unlink($fixturePath);
        }
    }

    public function testPolyglotFixtureIsRejectedAsUndecodableImage(): void
    {
        $uploadService = $this->createUploadService($this->configuration);
        $polyglotBytes = "\xFF\xD8\xFF\xE0<?php echo 'pwn'; ?>" . random_bytes(256);
        $fixtureTemporaryPath = $this->writeTemporaryFixture($polyglotBytes, 'fms_polyglot_fixture', '.jpg');
        $uploadedFile = new UploadedFile($fixtureTemporaryPath, 'polyglot.jpg', 'image/jpeg', strlen($polyglotBytes), UPLOAD_ERR_OK, true);

        $this->expectException(FMSUploadException::class);
        $uploadService->storeUploadedFile($uploadedFile, 'fms_polyglot');
    }

    public function testDecompressionBombFixtureIsRejectedBeforeEncoding(): void
    {
        $bombConfiguration = clone $this->configuration;
        $bombConfiguration->maximumWidth = 8;
        $bombConfiguration->maximumHeight = 8;
        $bombConfiguration->maximumPixels = 64;
        $uploadService = $this->createUploadService($bombConfiguration);
        $fixtureBytes = $this->buildJpegFixtureBytes(16, 16);
        $fixtureTemporaryPath = $this->writeTemporaryFixture($fixtureBytes, 'fms_bomb_fixture', '.jpg');
        $uploadedFile = new UploadedFile($fixtureTemporaryPath, 'bomb.jpg', 'image/jpeg', strlen($fixtureBytes), UPLOAD_ERR_OK, true);

        $this->expectException(FMSUploadException::class);
        $uploadService->storeUploadedFile($uploadedFile, 'fms_bomb');
    }

    private function createUploadService(FMSUploads $configuration): FMSWebPUploadService
    {
        return new FMSWebPUploadService(
            $configuration,
            new FMSUploadNamingService(),
            null,
            new FMSPrivateUploadService($this->objectStorage, 300, 900),
        );
    }

    private function buildJpegFixtureBytes(int $imageWidth, int $imageHeight): string
    {
        $imageCanvas = imagecreatetruecolor($imageWidth, $imageHeight);
        $redComponent = imagecolorallocate($imageCanvas, 190, 40, 40);
        $whiteComponent = imagecolorallocate($imageCanvas, 255, 255, 255);
        imagefilledrectangle($imageCanvas, 0, 0, $imageWidth - 1, $imageHeight - 1, $redComponent);
        imagefilledrectangle($imageCanvas, 4, 4, $imageWidth - 5, $imageHeight - 5, $whiteComponent);

        ob_start();
        imagejpeg($imageCanvas, null, 90);
        $jpegBytes = (string) ob_get_clean();
        imagedestroy($imageCanvas);

        $this->assertNotSame('', $jpegBytes);

        return $jpegBytes;
    }

    private function writeTemporaryFixture(string $fixtureBytes, string $fixturePrefix, string $fixtureSuffix): string
    {
        $fixtureTemporaryPath = tempnam(sys_get_temp_dir(), $fixturePrefix) . $fixtureSuffix;
        file_put_contents($fixtureTemporaryPath, $fixtureBytes);

        return $fixtureTemporaryPath;
    }

    private function removeDirectory(string $directoryPath): void
    {
        if (! is_dir($directoryPath)) {
            return;
        }

        $directoryIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directoryPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($directoryIterator as $fileInformation) {
            $fileInformation->isDir() ? @rmdir($fileInformation->getPathname()) : @unlink($fileInformation->getPathname());
        }
        @rmdir($directoryPath);
    }
}
