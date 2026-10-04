<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Uploads\Services;

/**
 * Pure normalization helpers for the Uploads vertical slice.
 * No filesystem or framework I/O happens here so the rules stay unit-testable.
 */
final class FMSUploadNamingService
{
    public const FILENAME_PATTERN = '/\Afms-[A-Za-z0-9]{16,64}\.webp\z/';

    public const FOLDER_PATTERN = '/\Afms-[a-z0-9][a-z0-9-]{0,62}\z/';

    /**
     * @return array{filename: string, relativePath: string, uploadFolder: string}
     */
    public function buildDeterministicTarget(string $folderName, string $randomToken): array
    {
        $normalizedFolderName = $this->normalizeFolderName($folderName);
        $normalizedRandomToken = $this->normalizeRandomToken($randomToken);
        $generatedFilename = 'fms-' . $normalizedRandomToken . '.webp';

        return [
            'filename'     => $generatedFilename,
            'relativePath' => $normalizedFolderName . '/' . $generatedFilename,
            'uploadFolder' => $normalizedFolderName,
        ];
    }

    public function normalizeFolderName(string $folderName): string
    {
        $trimmedFolderName = trim($folderName);

        if ($trimmedFolderName === '') {
            throw new \InvalidArgumentException('Upload folder name must not be empty.');
        }

        if (
            str_contains($trimmedFolderName, '/')
            || str_contains($trimmedFolderName, '\\')
            || str_contains($trimmedFolderName, '..')
            || str_contains($trimmedFolderName, "\0")
        ) {
            throw new \InvalidArgumentException('Upload folder name must be a single fms- segment.');
        }

        if (! str_starts_with($trimmedFolderName, 'fms-')) {
            throw new \InvalidArgumentException('Upload folder name must start with fms- and use lowercase characters only.');
        }

        if (preg_match(self::FOLDER_PATTERN, $trimmedFolderName) !== 1) {
            throw new \InvalidArgumentException('Upload folder name must start with fms- and use lowercase alphanumeric with hyphen only.');
        }

        return $trimmedFolderName;
    }

    public function normalizeRandomToken(string $randomToken): string
    {
        $trimmedRandomToken = trim($randomToken);

        if (preg_match('/\A[A-Za-z0-9]{16,64}\z/', $trimmedRandomToken) !== 1) {
            throw new \InvalidArgumentException('Random token must be 16-64 alphanumeric characters.');
        }

        return $trimmedRandomToken;
    }

    public function generateRandomToken(int $tokenLength = 32): string
    {
        if ($tokenLength < 16 || $tokenLength > 64) {
            throw new \InvalidArgumentException('Random token length must be 16-64 characters.');
        }

        $randomCharacters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $generatedToken = '';

        for ($characterIndex = 0; $characterIndex < $tokenLength; $characterIndex++) {
            $generatedToken .= $randomCharacters[random_int(0, 61)];
        }

        return $generatedToken;
    }

    public function newFileName(string $extension = 'webp'): string
    {
        if ($extension !== 'webp') {
            throw new \InvalidArgumentException('Stored image extension must be webp.');
        }

        return 'fms-' . bin2hex(random_bytes(16)) . '.webp';
    }

    public function isSafeStoredFilename(string $storedFilename): bool
    {
        return preg_match(self::FILENAME_PATTERN, $storedFilename) === 1;
    }

    public function joinUnderRoot(string $storageRoot, string $relativePath): string
    {
        $normalizedStorageRoot = rtrim(str_replace('\\', '/', $storageRoot), '/');
        $normalizedRelativePath = str_replace('\\', '/', $relativePath);

        if ($normalizedStorageRoot === '' || ! str_starts_with($normalizedStorageRoot, '/')) {
            throw new \InvalidArgumentException('Upload storage root must be absolute.');
        }

        if (
            $normalizedRelativePath === ''
            || str_starts_with($normalizedRelativePath, '/')
            || str_contains($normalizedRelativePath, "\0")
            || preg_match('#(?:\A|/)\.{1,2}(?:/|\z)#', $normalizedRelativePath) === 1
        ) {
            throw new \InvalidArgumentException('Upload relative path contains traversal.');
        }

        $relativeSegments = explode('/', $normalizedRelativePath);
        foreach ($relativeSegments as $relativeSegment) {
            if ($relativeSegment === '' || ! preg_match('/\A[A-Za-z0-9._-]+\z/', $relativeSegment)) {
                throw new \InvalidArgumentException('Upload path contains an invalid segment.');
            }
        }

        return $normalizedStorageRoot . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $relativeSegments);
    }

    public function resolveExistingFileUnderRoot(string $storageRoot, string $relativePath): string
    {
        $candidatePath = $this->joinUnderRoot($storageRoot, $relativePath);
        $realStorageRoot = realpath($storageRoot);
        $realCandidatePath = realpath($candidatePath);

        if ($realStorageRoot === false || $realCandidatePath === false || ! is_file($realCandidatePath)) {
            throw new \InvalidArgumentException('Stored upload does not exist.');
        }

        $normalizedStorageRoot = rtrim(str_replace('\\', '/', $realStorageRoot), '/') . '/';
        $normalizedCandidatePath = str_replace('\\', '/', $realCandidatePath);
        if (! str_starts_with($normalizedCandidatePath, $normalizedStorageRoot)) {
            throw new \InvalidArgumentException('Stored upload resolves outside its root.');
        }

        return $realCandidatePath;
    }

    /**
     * Strip client-controlled path parts and return a bare safe filename,
     * or an empty string when the candidate can never be a stored fms_ file.
     */
    public function sanitizeStoredFilename(string $candidateFilename): string
    {
        $basenameFilename = basename(str_replace('\\', '/', $candidateFilename));

        return $this->isSafeStoredFilename($basenameFilename) ? $basenameFilename : '';
    }
}
