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
use GdImage;
use InvalidArgumentException;
use Throwable;

/**
 * Secure image pipeline: read bounded bytes, verify type/dimensions/pixels,
 * decode once, orient JPEG, strip metadata by pixel re-encode, verify WebP,
 * and publish through an atomic rename outside the public directory.
 */
final class FMSWebPUploadService
{
    private const MIME_JPEG = 'image/jpeg';
    private const MIME_PNG = 'image/png';
    private const MIME_WEBP = 'image/webp';

    private readonly FMSUploads $configuration;

    private readonly FMSUploadNamingService $namingService;

    private readonly FMSPrivateUploadService $privateUploadService;

    private readonly string $stagingRoot;

    /**
     * @var callable(string, string, int): void|null
     */
    private $webPEncoder;

    /**
     * @param callable(string, string, int): void|null $webPEncoder
     */
    public function __construct(
        ?FMSUploads $configuration = null,
        ?FMSUploadNamingService $namingService = null,
        ?callable $webPEncoder = null,
        ?FMSPrivateUploadService $privateUploadService = null,
    ) {
        $this->configuration = $configuration ?? config(FMSUploads::class);
        $this->namingService = $namingService ?? new FMSUploadNamingService();
        $this->webPEncoder = $webPEncoder;
        $this->privateUploadService = $privateUploadService ?? new FMSPrivateUploadService(
            FMSObjectStorageFactory::create($this->configuration),
            $this->configuration->presignedUrlTtlSeconds,
            $this->configuration->presignedUrlMaximumTtlSeconds,
        );
        $this->stagingRoot = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'fms-staging';
        $this->validateConfiguration();
    }

    /**
     * @return array{storage_driver: string, bucket_name: string, file_name: string, object_key: string, download_url: string, mime_type: string, extension: string, width: int, height: int, size: int, size_bytes: int, sha256: string}
     */
    public function storeUploadedFile(UploadedFile $uploadedFile, string $purpose = 'images'): array
    {
        if ($uploadedFile->hasMoved()) {
            throw new FMSUploadException('Uploaded image has already been moved.');
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new FMSUploadException('Uploaded image reported a transfer error.');
        }

        return $this->storeLocalFile($uploadedFile->getTempName(), $purpose);
    }

    /**
     * This path-based entry point also supports trusted server-side import jobs.
     *
     * @return array{storage_driver: string, bucket_name: string, file_name: string, object_key: string, download_url: string, mime_type: string, extension: string, width: int, height: int, size: int, size_bytes: int, sha256: string}
     */
    public function storeLocalFile(string $sourceFilePath, string $purpose = 'images'): array
    {
        $sourceInformation = $this->inspectSourceFile($sourceFilePath);
        $folderName = $this->namingService->normalizeFolderName($purpose);
        $fileName = $this->namingService->newFileName('webp');
        $objectKey = $folderName . '/' . $fileName;
        $stagingDirectoryPath = $this->createStagingDirectory();
        $temporaryFilePath = $this->createTemporaryFile($stagingDirectoryPath);
        $decodedImage = null;
        $orientedImage = null;

        try {
            $decodedImage = $this->decodeSourceImage(
                $sourceInformation['bytes'],
                $sourceInformation['mime_type'],
            );
            $this->assertDecodedDimensions($decodedImage, $sourceInformation['width'], $sourceInformation['height']);

            if ($sourceInformation['mime_type'] === self::MIME_JPEG) {
                $orientedImage = $this->orientJpegImage($decodedImage, $sourceFilePath);
            }

            $imageToEncode = $orientedImage ?? $decodedImage;
            $this->encodeWebPImage($imageToEncode, $temporaryFilePath);
            $verifiedOutput = $this->verifyWebPOutput($temporaryFilePath);
            $publishedObject = $this->privateUploadService->publishWebP($temporaryFilePath, $objectKey);

            return [
                'storage_driver'  => $publishedObject['storage_driver'],
                'bucket_name'     => $publishedObject['bucket_name'],
                'file_name'       => $fileName,
                'object_key'      => $publishedObject['object_key'],
                'download_url'    => $this->privateUploadService->signedReadUrl($publishedObject['object_key']),
                'mime_type'       => self::MIME_WEBP,
                'extension'       => 'webp',
                'width'           => $verifiedOutput['width'],
                'height'          => $verifiedOutput['height'],
                'size'            => $verifiedOutput['size'],
                'size_bytes'      => $verifiedOutput['size'],
                'sha256'          => $verifiedOutput['sha256'],
            ];
        } catch (FMSUploadException $uploadException) {
            throw $uploadException;
        } catch (Throwable $unexpectedException) {
            throw new FMSUploadException('Image upload processing failed.', 0, $unexpectedException);
        } finally {
            if ($orientedImage instanceof GdImage && $orientedImage !== $decodedImage) {
                imagedestroy($orientedImage);
            }
            if ($decodedImage instanceof GdImage) {
                imagedestroy($decodedImage);
            }
            if ($temporaryFilePath !== '' && is_file($temporaryFilePath)) {
                @unlink($temporaryFilePath);
            }
        }
    }

    public function deleteStoredFile(string $objectKey): bool
    {
        return $this->privateUploadService->delete($objectKey);
    }

    /**
     * @return array{bytes: string, mime_type: string, width: int, height: int}
     */
    private function inspectSourceFile(string $sourceFilePath): array
    {
        if ($sourceFilePath === '' || str_contains($sourceFilePath, "\0")) {
            throw new FMSUploadException('Source file path is invalid.');
        }

        if (! is_file($sourceFilePath) || ! is_readable($sourceFilePath) || is_link($sourceFilePath)) {
            throw new FMSUploadException('Source file must be a readable regular file without symlinks.');
        }

        $sourceFileSize = filesize($sourceFilePath);
        if ($sourceFileSize === false || $sourceFileSize < 1 || $sourceFileSize > $this->configuration->maximumInputBytes()) {
            throw new FMSUploadException('Source image size is outside the allowed range.');
        }

        $sourceBytes = file_get_contents($sourceFilePath, false, null, 0, $this->configuration->maximumInputBytes() + 1);
        if ($sourceBytes === false || strlen($sourceBytes) !== $sourceFileSize) {
            throw new FMSUploadException('Source image could not be read safely.');
        }

        $detectedMimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($sourceBytes);
        if (! is_string($detectedMimeType) || ! in_array($detectedMimeType, $this->configuration->allowedMimes(), true)) {
            throw new FMSUploadException('Source image MIME type is not allowed.');
        }

        $this->assertMatchingMagicBytes($sourceBytes, $detectedMimeType);
        $imageInformation = @getimagesizefromstring($sourceBytes);
        if ($imageInformation === false) {
            throw new FMSUploadException('Source image header cannot be decoded.');
        }

        $sourceWidth = (int) ($imageInformation[0] ?? 0);
        $sourceHeight = (int) ($imageInformation[1] ?? 0);
        $reportedMimeType = (string) ($imageInformation['mime'] ?? '');
        if ($reportedMimeType !== $detectedMimeType) {
            throw new FMSUploadException('Source image MIME declarations do not match.');
        }

        $this->assertSafeDimensions($sourceWidth, $sourceHeight);

        return [
            'bytes'     => $sourceBytes,
            'mime_type' => $detectedMimeType,
            'width'     => $sourceWidth,
            'height'    => $sourceHeight,
        ];
    }

    private function assertMatchingMagicBytes(string $sourceBytes, string $detectedMimeType): void
    {
        $hasMatchingMagicBytes = match ($detectedMimeType) {
            self::MIME_JPEG => str_starts_with($sourceBytes, "\xFF\xD8\xFF"),
            self::MIME_PNG  => str_starts_with($sourceBytes, "\x89PNG\r\n\x1A\n"),
            self::MIME_WEBP => strlen($sourceBytes) >= 12
                && substr($sourceBytes, 0, 4) === 'RIFF'
                && substr($sourceBytes, 8, 4) === 'WEBP',
            default => false,
        };

        if (! $hasMatchingMagicBytes) {
            throw new FMSUploadException('Source image magic bytes are invalid.');
        }
    }

    private function assertSafeDimensions(int $imageWidth, int $imageHeight): void
    {
        if (
            $imageWidth < 1
            || $imageHeight < 1
            || $imageWidth > $this->configuration->maximumWidth
            || $imageHeight > $this->configuration->maximumHeight
            || $imageWidth > intdiv($this->configuration->maximumPixels, $imageHeight)
        ) {
            throw new FMSUploadException('Source image dimensions or pixel count exceed the configured limit.');
        }

        $estimatedDecodedBytes = $imageWidth * $imageHeight * $this->configuration->decodedBytesPerPixel;
        if ($estimatedDecodedBytes > $this->configuration->maximumDecodedBytes) {
            throw new FMSUploadException('Source image decoded memory estimate exceeds the configured limit.');
        }
    }

    private function decodeSourceImage(string $sourceBytes, string $detectedMimeType): GdImage
    {
        if ($detectedMimeType === self::MIME_WEBP && ! function_exists('imagecreatefromwebp')) {
            throw new FMSUploadException('This server cannot securely decode WebP input.');
        }

        set_error_handler(static function (int $severity, string $message): never {
            throw new FMSUploadException('Image decoder rejected the source: ' . $message);
        });

        try {
            $decodedImage = imagecreatefromstring($sourceBytes);
        } finally {
            restore_error_handler();
        }

        if (! $decodedImage instanceof GdImage) {
            throw new FMSUploadException('Source image decoder rejected the source.');
        }

        /* Pertahankan alpha channel PNG/WebP transparan: palette ke truecolor + jangan flatten alpha */
        if (! imageistruecolor($decodedImage)) {
            imagepalettetotruecolor($decodedImage);
        }
        imagealphablending($decodedImage, false);
        imagesavealpha($decodedImage, true);

        return $decodedImage;
    }

    private function assertDecodedDimensions(GdImage $decodedImage, int $expectedWidth, int $expectedHeight): void
    {
        if (imagesx($decodedImage) !== $expectedWidth || imagesy($decodedImage) !== $expectedHeight) {
            throw new FMSUploadException('Decoded image dimensions differ from the inspected dimensions.');
        }
    }

    private function orientJpegImage(GdImage $decodedImage, string $sourceFilePath): GdImage
    {
        $orientation = 1;
        if (function_exists('exif_read_data')) {
            $exifData = @exif_read_data($sourceFilePath, 'IFD0', true, false);
            $orientation = is_array($exifData)
                ? (int) ($exifData['IFD0']['Orientation'] ?? $exifData['Orientation'] ?? 1)
                : 1;
        }

        return match ($orientation) {
            2 => $this->flipImage($decodedImage, IMG_FLIP_HORIZONTAL),
            3 => $this->rotateImage($decodedImage, 180),
            4 => $this->flipImage($decodedImage, IMG_FLIP_VERTICAL),
            5 => $this->flipImage($this->rotateImage($decodedImage, 270), IMG_FLIP_HORIZONTAL),
            6 => $this->rotateImage($decodedImage, 270),
            7 => $this->flipImage($this->rotateImage($decodedImage, 90), IMG_FLIP_HORIZONTAL),
            8 => $this->rotateImage($decodedImage, 90),
            default => $decodedImage,
        };
    }

    private function rotateImage(GdImage $sourceImage, int $counterClockwiseDegrees): GdImage
    {
        $rotatedImage = imagerotate($sourceImage, $counterClockwiseDegrees, 0);
        if (! $rotatedImage instanceof GdImage) {
            throw new FMSUploadException('JPEG orientation rotation failed.');
        }

        return $rotatedImage;
    }

    private function flipImage(GdImage $sourceImage, int $flipMode): GdImage
    {
        if (! imageflip($sourceImage, $flipMode)) {
            throw new FMSUploadException('JPEG orientation flip failed.');
        }

        return $sourceImage;
    }

    private function encodeWebPImage(GdImage $decodedImage, string $temporaryFilePath): void
    {
        /* Pastikan alpha channel dipertahankan pada seluruh jalur encode */
        imagealphablending($decodedImage, false);
        imagesavealpha($decodedImage, true);

        if ($this->webPEncoder !== null) {
            $losslessIntermediatePath = $temporaryFilePath . '.png';
            try {
                if (! imagepng($decodedImage, $losslessIntermediatePath, 6, PNG_ALL_FILTERS)) {
                    throw new FMSUploadException('Lossless intermediate image could not be encoded.');
                }
                chmod($losslessIntermediatePath, 0600);
                ($this->webPEncoder)($losslessIntermediatePath, $temporaryFilePath, $this->configuration->webPQuality);
            } finally {
                if (is_file($losslessIntermediatePath)) {
                    @unlink($losslessIntermediatePath);
                }
            }

            return;
        }

        if (function_exists('imagewebp')) {
            if (! imagewebp($decodedImage, $temporaryFilePath, $this->configuration->webPQuality)) {
                throw new FMSUploadException('WebP output could not be encoded.');
            }
            chmod($temporaryFilePath, 0600);
            return;
        }

        $this->encodeWebPWithBinary($decodedImage, $temporaryFilePath);
    }

    private function encodeWebPWithBinary(GdImage $decodedImage, string $temporaryFilePath): void
    {
        $cwebpBinaryPath = $this->validatedBinaryPath($this->configuration->cwebpBinary, 'cwebp');
        $losslessIntermediatePath = $temporaryFilePath . '.png';

        try {
            /* Pastikan alpha channel diteruskan ke intermediate lossless PNG */
            imagealphablending($decodedImage, false);
            imagesavealpha($decodedImage, true);

            if (! imagepng($decodedImage, $losslessIntermediatePath, 6, PNG_ALL_FILTERS)) {
                throw new FMSUploadException('Lossless intermediate image could not be encoded.');
            }
            chmod($losslessIntermediatePath, 0600);

            /* Flag -exact membuat cwebp mempertahankan nilai RGB di area transparan (tidak flatten ke hitam) */
            $commandParts = [
                escapeshellarg($cwebpBinaryPath),
                '-quiet',
                '-mt',
                '-metadata',
                'none',
                '-q',
                (string) $this->configuration->webPQuality,
                '-exact',
                escapeshellarg($losslessIntermediatePath),
                '-o',
                escapeshellarg($temporaryFilePath),
            ];
            $commandOutput = [];
            $commandExitCode = 1;
            exec(implode(' ', $commandParts), $commandOutput, $commandExitCode);

            if ($commandExitCode !== 0 || ! is_file($temporaryFilePath)) {
                throw new FMSUploadException('WebP encoder process failed.');
            }
            chmod($temporaryFilePath, 0600);
        } finally {
            if (is_file($losslessIntermediatePath)) {
                @unlink($losslessIntermediatePath);
            }
        }
    }

    /**
     * @return array{width: int, height: int, size: int, sha256: string}
     */
    private function verifyWebPOutput(string $temporaryFilePath): array
    {
        if (! is_file($temporaryFilePath) || is_link($temporaryFilePath)) {
            throw new FMSUploadException('Encoded WebP output is missing.');
        }

        $outputFileSize = filesize($temporaryFilePath);
        if ($outputFileSize === false || $outputFileSize < 12 || $outputFileSize > $this->configuration->maximumOutputBytes) {
            throw new FMSUploadException('Encoded WebP output size is outside the allowed range.');
        }

        $outputBytes = file_get_contents($temporaryFilePath);
        if ($outputBytes === false || strlen($outputBytes) !== $outputFileSize) {
            throw new FMSUploadException('Encoded WebP output cannot be read safely.');
        }

        $this->assertMatchingMagicBytes($outputBytes, self::MIME_WEBP);
        $detectedMimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($outputBytes);
        $imageInformation = @getimagesizefromstring($outputBytes);
        if (
            $detectedMimeType !== self::MIME_WEBP
            || $imageInformation === false
            || ($imageInformation['mime'] ?? '') !== self::MIME_WEBP
        ) {
            throw new FMSUploadException('Encoded output is not a valid WebP image.');
        }

        $this->assertNoWebPMetadataOrAnimation($outputBytes);
        $outputWidth = (int) $imageInformation[0];
        $outputHeight = (int) $imageInformation[1];
        $this->assertSafeDimensions($outputWidth, $outputHeight);
        $this->decodeWebPOutput($temporaryFilePath, $outputBytes, $outputWidth, $outputHeight);

        return [
            'width'  => $outputWidth,
            'height' => $outputHeight,
            'size'   => $outputFileSize,
            'sha256' => hash_file('sha256', $temporaryFilePath),
        ];
    }

    private function assertNoWebPMetadataOrAnimation(string $webPBytes): void
    {
        $byteLength = strlen($webPBytes);
        $chunkOffset = 12;

        while ($chunkOffset + 8 <= $byteLength) {
            $chunkType = substr($webPBytes, $chunkOffset, 4);
            $chunkLengthData = unpack('Vlength', substr($webPBytes, $chunkOffset + 4, 4));
            $chunkLength = (int) ($chunkLengthData['length'] ?? -1);
            if ($chunkLength < 0 || $chunkOffset + 8 + $chunkLength > $byteLength) {
                throw new FMSUploadException('Encoded WebP chunk structure is invalid.');
            }

            if (in_array($chunkType, ['EXIF', 'XMP ', 'ICCP', 'ANIM', 'ANMF'], true)) {
                throw new FMSUploadException('Encoded WebP contains forbidden metadata or animation.');
            }

            $chunkOffset += 8 + $chunkLength + ($chunkLength % 2);
        }

        if ($chunkOffset !== $byteLength) {
            throw new FMSUploadException('Encoded WebP has unexpected trailing data.');
        }
    }

    private function decodeWebPOutput(string $temporaryFilePath, string $outputBytes, int $expectedWidth, int $expectedHeight): void
    {
        if (function_exists('imagecreatefromwebp')) {
            $decodedOutput = @imagecreatefromwebp($temporaryFilePath);
            if (! $decodedOutput instanceof GdImage) {
                throw new FMSUploadException('Encoded WebP output failed decode verification.');
            }

            try {
                $this->assertDecodedDimensions($decodedOutput, $expectedWidth, $expectedHeight);
            } finally {
                imagedestroy($decodedOutput);
            }

            return;
        }

        if ($this->configuration->dwebpBinary === '') {
            throw new FMSUploadException('No WebP decoder is available for output verification.');
        }

        $dwebpBinaryPath = $this->validatedBinaryPath($this->configuration->dwebpBinary, 'dwebp');
        $decodedOutputPath = $temporaryFilePath . '.verified.png';
        try {
            $commandOutput = [];
            $commandExitCode = 1;
            $command = implode(' ', [
                escapeshellarg($dwebpBinaryPath),
                '-quiet',
                escapeshellarg($temporaryFilePath),
                '-o',
                escapeshellarg($decodedOutputPath),
            ]);
            exec($command, $commandOutput, $commandExitCode);
            if ($commandExitCode !== 0 || ! is_file($decodedOutputPath)) {
                throw new FMSUploadException('Encoded WebP output failed decode verification.');
            }

            $decodedInformation = @getimagesize($decodedOutputPath);
            if (
                $decodedInformation === false
                || (int) $decodedInformation[0] !== $expectedWidth
                || (int) $decodedInformation[1] !== $expectedHeight
            ) {
                throw new FMSUploadException('Decoded WebP output dimensions are invalid.');
            }
        } finally {
            if (is_file($decodedOutputPath)) {
                @unlink($decodedOutputPath);
            }
        }
    }

    private function createStagingDirectory(): string
    {
        $stagingRoot = rtrim($this->stagingRoot, DIRECTORY_SEPARATOR);
        $this->assertStorageOutsidePublicRoot($stagingRoot);

        if (! is_dir($stagingRoot) && ! mkdir($stagingRoot, $this->configuration->directoryMode, true) && ! is_dir($stagingRoot)) {
            throw new FMSUploadException('Private staging root could not be created.');
        }
        chmod($stagingRoot, $this->configuration->directoryMode);

        if (is_link($stagingRoot) || ! is_writable($stagingRoot)) {
            throw new FMSUploadException('Private staging root is unsafe or not writable.');
        }

        return $stagingRoot;
    }

    private function createPrivateDirectory(string $folderName): string
    {
        $storageRoot = rtrim($this->configuration->storageRoot, DIRECTORY_SEPARATOR);
        $this->assertStorageOutsidePublicRoot($storageRoot);

        if (! is_dir($storageRoot) && ! mkdir($storageRoot, $this->configuration->directoryMode, true) && ! is_dir($storageRoot)) {
            throw new FMSUploadException('Private upload root could not be created.');
        }
        chmod($storageRoot, $this->configuration->directoryMode);

        $destinationDirectoryPath = $this->namingService->joinUnderRoot($storageRoot, $folderName);
        if (! is_dir($destinationDirectoryPath) && ! mkdir($destinationDirectoryPath, $this->configuration->directoryMode) && ! is_dir($destinationDirectoryPath)) {
            throw new FMSUploadException('Private upload folder could not be created.');
        }
        chmod($destinationDirectoryPath, $this->configuration->directoryMode);

        if (is_link($destinationDirectoryPath) || ! is_writable($destinationDirectoryPath)) {
            throw new FMSUploadException('Private upload folder is unsafe or not writable.');
        }

        return $destinationDirectoryPath;
    }

    private function createTemporaryFile(string $destinationDirectoryPath): string
    {
        $temporaryFilePath = tempnam($destinationDirectoryPath, 'fms-tmp-');
        if ($temporaryFilePath === false) {
            throw new FMSUploadException('Temporary upload file could not be created.');
        }
        chmod($temporaryFilePath, 0600);

        return $temporaryFilePath;
    }

    private function publishAtomically(string $temporaryFilePath, string $destinationFilePath): void
    {
        if (file_exists($destinationFilePath) || is_link($destinationFilePath)) {
            throw new FMSUploadException('Generated upload destination unexpectedly exists.');
        }

        if (! rename($temporaryFilePath, $destinationFilePath)) {
            throw new FMSUploadException('Encoded WebP could not be published atomically.');
        }
        chmod($destinationFilePath, $this->configuration->fileMode);
    }

    private function validatedBinaryPath(string $binaryPath, string $binaryName): string
    {
        if ($binaryPath === '' || ! str_starts_with($binaryPath, DIRECTORY_SEPARATOR)) {
            throw new FMSUploadException('Configured ' . $binaryName . ' binary is unavailable or unsafe.');
        }

        $resolvedBinaryPath = realpath($binaryPath);
        $effectiveBinaryPath = $resolvedBinaryPath !== false ? $resolvedBinaryPath : $binaryPath;

        if (! is_file($effectiveBinaryPath) || ! is_executable($effectiveBinaryPath)) {
            throw new FMSUploadException('Configured ' . $binaryName . ' binary is unavailable or unsafe.');
        }

        return $effectiveBinaryPath;
    }

    private function assertStorageOutsidePublicRoot(string $storageRoot): void
    {
        $normalizedStorageRoot = $this->normalizeAbsolutePath($storageRoot);
        $publicPath = defined('PUBLICPATH') ? PUBLICPATH : (defined('FCPATH') ? FCPATH : ROOTPATH . 'public' . DIRECTORY_SEPARATOR);
        $normalizedPublicRoot = $this->normalizeAbsolutePath(rtrim($publicPath, DIRECTORY_SEPARATOR));

        if (
            $normalizedStorageRoot === $normalizedPublicRoot
            || str_starts_with($normalizedStorageRoot . '/', $normalizedPublicRoot . '/')
        ) {
            throw new FMSUploadException('Upload storage must remain outside the public web root.');
        }
    }

    private function normalizeAbsolutePath(string $path): string
    {
        $normalizedPath = str_replace('\\', '/', $path);
        $segments = [];
        foreach (explode('/', $normalizedPath) as $pathSegment) {
            if ($pathSegment === '' || $pathSegment === '.') {
                continue;
            }
            if ($pathSegment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $pathSegment;
        }

        return '/' . implode('/', $segments);
    }

    private function validateConfiguration(): void
    {
        if (
            $this->configuration->maximumInputBytes() < 1
            || $this->configuration->maximumOutputBytes < 1
            || $this->configuration->maximumWidth < 1
            || $this->configuration->maximumHeight < 1
            || $this->configuration->maximumPixels < 1
            || $this->configuration->maximumDecodedBytes < 1
            || $this->configuration->decodedBytesPerPixel < 4
            || $this->configuration->webPQuality < 1
            || $this->configuration->webPQuality > 100
        ) {
            throw new FMSUploadException('Upload configuration limits are invalid.');
        }

        /* WebP pipeline hanya izinkan image MIME, bukan PDF */
        $imageMimes = array_filter(
            $this->configuration->allowedMimes(),
            static fn (string $m): bool => in_array($m, [self::MIME_JPEG, self::MIME_PNG, self::MIME_WEBP], true),
        );
        foreach ($imageMimes as $allowedMimeType) {
            if (! in_array($allowedMimeType, [self::MIME_JPEG, self::MIME_PNG, self::MIME_WEBP], true)) {
                throw new FMSUploadException('Upload configuration contains an unsupported MIME type.');
            }
        }
    }
}
