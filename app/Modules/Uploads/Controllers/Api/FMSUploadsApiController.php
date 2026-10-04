<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Uploads\Controllers\Api;

use App\Config\FMSUploads;
use App\Core\FMSApiController;
use App\Modules\Uploads\Exceptions\FMSUploadException;
use App\Modules\Uploads\Services\FMSFileUploadService;
use App\Modules\Uploads\Services\FMSObjectStorageFactory;
use App\Modules\Uploads\Validation\FMSUploadValidation;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;
use Throwable;

/**
 * Thin API boundary for secure file uploads (WebP images + PDF documents).
 * Supports local storage, S3, and MinIO.
 */
final class FMSUploadsApiController extends FMSApiController
{
    public function store(): ResponseInterface
    {
        $authorizationFailureResponse = $this->requireApiPermission('uploads.create');
        if ($authorizationFailureResponse !== null) {
            return $authorizationFailureResponse;
        }

        try {
            $rawPurpose = (string) ($this->request->getPost('purpose') ?? 'fms-uploads');
            $rawPurpose = trim($rawPurpose);

            /* Normalisasi legacy alias */
            if ($rawPurpose === '' || strtolower($rawPurpose) === 'images') {
                $rawPurpose = 'fms-images';
            } elseif (! str_starts_with(strtolower($rawPurpose), 'fms-')) {
                $rawPurpose = 'fms-' . ltrim($rawPurpose, '-');
            }

            $validationErrors = FMSUploadValidation::validatePurpose(['purpose' => $rawPurpose]);
            if ($validationErrors !== []) {
                return $this->respondValidationError($validationErrors);
            }

            $uploadedFiles   = $this->request->getFiles();
            $receivedFiles   = $uploadedFiles['file'] ?? $uploadedFiles['image'] ?? null;
            $normalizedFiles = is_array($receivedFiles) ? $receivedFiles : ($receivedFiles === null ? [] : [$receivedFiles]);

            $validationErrors = FMSUploadValidation::validateFileCount(count($normalizedFiles));
            if ($validationErrors !== []) {
                return $this->respondValidationError($validationErrors);
            }

            $uploadService = new FMSFileUploadService();
            $storedFiles   = [];

            foreach ($normalizedFiles as $uploadedFile) {
                $storedFile    = $uploadService->storeUploadedFile($uploadedFile, $rawPurpose);
                $storedFiles[] = [
                    'bucket_name'  => $storedFile['bucket_name'],
                    'file_name'    => $storedFile['file_name'],
                    'object_key'   => $storedFile['object_key'],
                    'download_url' => $storedFile['download_url'],
                    'mime_type'    => $storedFile['mime_type'],
                    'extension'    => $storedFile['extension'],
                    'size'         => $storedFile['size'],
                    'sha256'       => $storedFile['sha256'],
                ];
            }

            return $this->respondCreated(
                'Berkas berhasil diunggah.',
                ['files' => $storedFiles],
            );
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondValidationError(['file' => [$invalidArgumentException->getMessage()]]);
        } catch (FMSUploadException $uploadException) {
            return $this->respondUnprocessableEntity($uploadException->getMessage());
        } catch (Throwable $unexpectedException) {
            log_message('error', 'Upload store failed: {message}', ['message' => $unexpectedException->getMessage()]);

            return $this->respondError(500, 'Unggahan berkas gagal diproses.', null, 500);
        }
    }

    public function download(string $bucketName, string ...$objectKeySegments): ResponseInterface
    {
        try {
            $configuration = config(FMSUploads::class);
            $objectStorage = FMSObjectStorageFactory::create($configuration);

            if ($objectStorage->driverName() !== 'local' || ! hash_equals($objectStorage->bucketName(), rawurldecode($bucketName))) {
                return $this->respondError(404, 'Objek tidak ditemukan.', null, 404);
            }

            /* Gabungkan kembali semua segmen object key yang dipecah router dari (:any) */
            $decodedObjectKey       = implode('/', array_map('rawurldecode', $objectKeySegments));
            $expiresAtUnixTimestamp = (int) ($this->request->getGet('expires') ?? 0);
            $keyIdentifier          = (string) ($this->request->getGet('key_id') ?? '');
            $providedSignature      = (string) ($this->request->getGet('signature') ?? '');

            if (! $objectStorage->verifyPresignedRequest($decodedObjectKey, $expiresAtUnixTimestamp, $keyIdentifier, $providedSignature)) {
                return $this->respondError(403, 'URL bertanda tangan tidak valid atau kedaluwarsa.', null, 403);
            }

            $storedObject = $objectStorage->readObject($decodedObjectKey);
            $contentType  = $storedObject['content_type'] ?? 'application/octet-stream';
            $filename     = basename($decodedObjectKey);

            /* Inline display untuk PDF & WebP */
            $disposition = in_array($contentType, ['image/webp', 'application/pdf'], true)
                ? 'inline; filename="' . addslashes($filename) . '"'
                : 'attachment; filename="' . addslashes($filename) . '"';

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', $contentType)
                ->setHeader('Content-Length', (string) $storedObject['byte_size'])
                ->setHeader('Cache-Control', 'private, no-store, max-age=0')
                ->setHeader('Content-Disposition', $disposition)
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setHeader('ETag', '"' . $storedObject['sha256'] . '"')
                ->setBody($storedObject['body']);
        } catch (InvalidArgumentException) {
            return $this->respondError(404, 'Objek tidak ditemukan.', null, 404);
        } catch (Throwable $unexpectedException) {
            log_message('error', 'Private object read failed: {message}', ['message' => $unexpectedException->getMessage()]);

            return $this->respondError(500, 'Objek gagal dibaca.', null, 500);
        }
    }

}
