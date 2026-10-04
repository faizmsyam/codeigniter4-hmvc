<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Brand\Controllers\Api;

use App\Core\FMSApiController;
use App\Libraries\FMSAuditLogger;
use App\Modules\Brand\Models\FMSBrandModel;
use App\Modules\Brand\Services\FMSBrandIdentityService;
use App\Modules\Brand\Services\FMSBrandService;
use App\Modules\Brand\Validation\FMSBrandValidation;
use App\Modules\Uploads\Exceptions\FMSUploadException;
use App\Modules\Uploads\Services\FMSWebPUploadService;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;
use Throwable;

/**
 * Thin API boundary for Brand configuration.
 * Business rules stay in FMSBrandService and FMSBrandValidation.
 */
final class FMSBrandApiController extends FMSApiController
{
    private FMSBrandModel $brandModel;

    private FMSBrandService $brandService;

    private ?FMSWebPUploadService $uploadService;

    public function __construct(
        ?FMSBrandModel $brandModel = null,
        ?FMSBrandService $brandService = null,
        ?FMSWebPUploadService $uploadService = null,
    ) {
        parent::__construct();
        $this->brandModel = $brandModel ?? new FMSBrandModel();
        $this->brandService = $brandService ?? new FMSBrandService();
        $this->uploadService = $uploadService;
    }

    public function show(): ResponseInterface
    {
        $authorizationFailureResponse = $this->requireApiPermission('brand.read');
        if ($authorizationFailureResponse !== null) {
            return $authorizationFailureResponse;
        }

        $existingBrandRow = $this->firstBrandRow();

        /* Baris pertama dibuat otomatis saat tabel masih kosong agar form selalu punya payload. */
        if ($existingBrandRow === null) {
            return $this->respondOk(
                'Data brand belum disetel.',
                ['brand' => null],
            );
        }

        return $this->respondOk(
            'Data brand berhasil dimuat.',
            ['brand' => $this->presentBrandRow($existingBrandRow)],
        );
    }

    public function uploadImages(): ResponseInterface
    {
        $authorizationFailureResponse = $this->requireApiPermission('brand.update');
        if ($authorizationFailureResponse !== null) {
            return $authorizationFailureResponse;
        }

        try {
            $storedImagePaths = $this->storeBrandImages();
            if ($storedImagePaths === []) {
                return $this->respondValidationError(['file' => ['Pilih minimal satu gambar brand.']]);
            }

            return $this->respondOk(
                'Gambar brand berhasil diunggah.',
                ['paths' => $storedImagePaths],
            );
        } catch (FMSUploadException $uploadException) {
            return $this->respondUnprocessableEntity($uploadException->getMessage());
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondValidationError(['file' => [$invalidArgumentException->getMessage()]]);
        } catch (Throwable $unexpectedException) {
            log_message('error', 'Brand image upload failed: {message}', ['message' => $unexpectedException->getMessage()]);

            return $this->respondServerError('Gambar brand gagal diunggah.');
        }
    }

    public function store(): ResponseInterface
    {
        $authorizationFailureResponse = $this->requireApiPermission('brand.update');
        if ($authorizationFailureResponse !== null) {
            FMSAuditLogger::record(
                event: 'access.denied',
                module: 'brand',
                actorId: $this->authenticatedApiUserIdentifier(),
                entityType: 'menu',
                entityId: 'brand',
                description: 'Akses ditolak (403) saat mencoba menyimpan brand baru tanpa permission brand.update.',
                httpMethod: 'POST',
                statusCode: 403,
            );

            return $authorizationFailureResponse;
        }

        $existingBrandRow = $this->firstBrandRow();
        if ($existingBrandRow !== null) {
            return $this->update();
        }

        try {
            $requestData = $this->extractRequestData();
            $requestData = $this->normalizeBrandRequestData($requestData);
            $validationErrors = FMSBrandValidation::validateCreate($requestData);

            if ($validationErrors !== []) {
                return $this->respondValidationError($validationErrors);
            }

            $actorUserIdentifier = $this->authenticatedApiUserIdentifier();
            $preparedBrandData = $this->brandService->prepareCreateData($requestData, $actorUserIdentifier);

            $insertedBrandIdentifier = $this->brandModel->insert($preparedBrandData, true);
            if ($insertedBrandIdentifier === false) {
                return $this->respondServerError('Data brand gagal disimpan.');
            }

            $createdBrandRow = $this->brandModel->find($insertedBrandIdentifier);

            FMSBrandIdentityService::clearCache();

            FMSAuditLogger::record(
                event: 'brand.created',
                module: 'brand',
                actorId: $actorUserIdentifier,
                entityType: 'brand',
                entityId: (string) $insertedBrandIdentifier,
                description: 'Data brand berhasil dibuat: ' . ($preparedBrandData['name'] ?? 'Brand'),
                after: $preparedBrandData,
                httpMethod: 'POST',
                statusCode: 201,
            );

            return $this->respondCreated(
                'Data brand berhasil disimpan.',
                ['brand' => $this->presentBrandRow(is_array($createdBrandRow) ? $createdBrandRow : $preparedBrandData)],
            );
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondValidationError(['brand' => [$invalidArgumentException->getMessage()]]);
        } catch (Throwable $unexpectedException) {
            log_message('error', 'Brand store failed: {message}', ['message' => $unexpectedException->getMessage()]);

            return $this->respondServerError('Permintaan brand gagal diproses.');
        }
    }

    public function update(): ResponseInterface
    {
        $authorizationFailureResponse = $this->requireApiPermission('brand.update');
        if ($authorizationFailureResponse !== null) {
            FMSAuditLogger::record(
                event: 'access.denied',
                module: 'brand',
                actorId: $this->authenticatedApiUserIdentifier(),
                entityType: 'menu',
                entityId: 'brand',
                description: 'Akses ditolak (403) saat mencoba mengubah brand tanpa permission brand.update.',
                httpMethod: 'POST',
                statusCode: 403,
            );

            return $authorizationFailureResponse;
        }

        try {
            $existingBrandRow = $this->firstBrandRow();
            if ($existingBrandRow === null) {
                return $this->store();
            }

            $requestData = $this->extractRequestData();
            $requestData = $this->normalizeBrandRequestData($requestData);
            $validationErrors = FMSBrandValidation::validateUpdate($requestData);
            if ($validationErrors !== []) {
                return $this->respondValidationError($validationErrors);
            }

            $actorUserIdentifier = $this->authenticatedApiUserIdentifier();
            $preparedBrandData = $this->brandService->prepareUpdateData(
                $requestData,
                $existingBrandRow,
                $actorUserIdentifier,
            );

            if ($this->brandModel->update((int) $existingBrandRow['id'], $preparedBrandData) === false) {
                return $this->respondServerError('Data brand gagal diperbarui.');
            }

            $updatedBrandRow = $this->brandModel->find((int) $existingBrandRow['id']);

            FMSBrandIdentityService::clearCache();

            FMSAuditLogger::record(
                event: 'brand.updated',
                module: 'brand',
                actorId: $actorUserIdentifier,
                entityType: 'brand',
                entityId: (string) $existingBrandRow['id'],
                description: 'Data brand berhasil diperbarui: ' . ($preparedBrandData['name'] ?? $existingBrandRow['name'] ?? 'Brand'),
                before: $existingBrandRow,
                after: is_array($updatedBrandRow) ? $updatedBrandRow : $preparedBrandData,
                httpMethod: 'POST',
                statusCode: 200,
            );

            return $this->respondOk(
                'Data brand berhasil diperbarui.',
                ['brand' => $this->presentBrandRow(is_array($updatedBrandRow) ? $updatedBrandRow : array_merge($existingBrandRow, $preparedBrandData))],
            );
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->respondValidationError(['brand' => [$invalidArgumentException->getMessage()]]);
        } catch (Throwable $unexpectedException) {
            log_message('error', 'Brand update failed: {message}', ['message' => $unexpectedException->getMessage()]);

            return $this->respondServerError('Permintaan brand gagal diproses.');
        }
    }

    /**
     * @param array<string, mixed> $brandRow
     * @return array<string, mixed>
     */
    private function presentBrandRow(array $brandRow): array
    {
        $socialLinks = $brandRow['social_links'] ?? [];
        if (is_string($socialLinks)) {
            $decodedSocialLinks = json_decode($socialLinks, true);
            $socialLinks = is_array($decodedSocialLinks) ? $decodedSocialLinks : [];
        }

        return [
            'uuid'            => $brandRow['uuid'] ?? null,
            'name'            => $brandRow['name'] ?? null,
            'tagline'         => $brandRow['tagline'] ?? null,
            'description'     => $brandRow['description'] ?? null,
            'email'           => $brandRow['email'] ?? null,
            'phone'           => $brandRow['phone'] ?? null,
            'whatsapp'        => $brandRow['whatsapp'] ?? null,
            'website_url'     => $brandRow['website_url'] ?? null,
            'address'         => $brandRow['address'] ?? null,
            'social_links'    => $socialLinks,
            'logo_path'       => $brandRow['logo_path'] ?? null,
            'logo_url'        => $this->resolvePublicBrandUrl($brandRow['logo_path'] ?? null, 'brand/logo'),
            'logo_light_path' => $brandRow['logo_light_path'] ?? null,
            'logo_light_url'  => $this->resolvePublicBrandUrl($brandRow['logo_light_path'] ?? null, 'brand/logo-light'),
            'favicon_path'    => $brandRow['favicon_path'] ?? null,
            'favicon_url'     => $this->resolvePublicBrandUrl($brandRow['favicon_path'] ?? null, 'brand/favicon'),
            'is_active'       => isset($brandRow['is_active']) ? (int) $brandRow['is_active'] : 1,
            'created_at'      => $brandRow['created_at'] ?? null,
            'updated_at'      => $brandRow['updated_at'] ?? null,
        ];
    }

    private function resolvePublicBrandUrl(mixed $rawPath, string $publicRoute): string
    {
        $rawPath = trim((string) $rawPath);
        if ($rawPath === '') {
            return '';
        }

        if (str_starts_with($rawPath, 'http://') || str_starts_with($rawPath, 'https://')) {
            return $rawPath;
        }

        return site_url($publicRoute);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function firstBrandRow(): ?array
    {
        $foundBrandRow = $this->brandModel->orderBy('id', 'ASC')->first();

        return is_array($foundBrandRow) ? $foundBrandRow : null;
    }

    /**
     * @return array<string, string>
     */
    private function storeBrandImages(): array
    {
        $storedImagePaths = [];
        $uploadedFiles = $this->request->getFiles();
        $imageFieldMap = [
            'logo'       => 'logo_path',
            'logo_light' => 'logo_light_path',
            'favicon'    => 'favicon_path',
        ];

        foreach ($imageFieldMap as $requestFieldName => $storageFieldName) {
            if (! isset($uploadedFiles[$requestFieldName])) {
                continue;
            }

            $uploadedFile = $uploadedFiles[$requestFieldName];
            if (is_array($uploadedFile)) {
                $uploadedFile = reset($uploadedFile);
            }

            if ($uploadedFile === null || $uploadedFile->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $storedFile = $this->uploadService()->storeUploadedFile($uploadedFile, 'fms-brand');
            $storedImagePaths[$storageFieldName] = $storedFile['object_key'];
        }

        if ($storedImagePaths !== []) {
            return $storedImagePaths;
        }

        return [];
    }

    private function uploadService(): FMSWebPUploadService
    {
        if (! $this->uploadService instanceof FMSWebPUploadService) {
            $this->uploadService = new FMSWebPUploadService();
        }

        return $this->uploadService;
    }

    /**
     * @param array<string, string> $storedImagePaths
     */
    private function deleteStoredImages(array $storedImagePaths): void
    {
        foreach ($storedImagePaths as $relativeImagePath) {
            try {
                $this->uploadService()->deleteStoredFile($relativeImagePath);
            } catch (Throwable $cleanupException) {
                log_message('error', 'Brand image cleanup failed: {message}', ['message' => $cleanupException->getMessage()]);
            }
        }
    }

    /**
     * @param array<string, mixed> $existingBrandRow
     * @param array<string, string> $storedImagePaths
     */
    private function deleteReplacedImages(array $existingBrandRow, array $storedImagePaths): void
    {
        foreach ($storedImagePaths as $storageFieldName => $relativeImagePath) {
            $previousImagePath = isset($existingBrandRow[$storageFieldName]) ? (string) $existingBrandRow[$storageFieldName] : '';
            if ($previousImagePath === '' || $previousImagePath === $relativeImagePath) {
                continue;
            }

            try {
                $this->uploadService()->deleteStoredFile($previousImagePath);
            } catch (Throwable $cleanupException) {
                log_message('error', 'Brand replaced image cleanup failed: {message}', ['message' => $cleanupException->getMessage()]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extractRequestData(): array
    {
        $postData = (array) $this->request->getPost();
        if ($postData !== []) {
            return $postData;
        }

        $contentType = (string) $this->request->getHeaderLine('Content-Type');
        if (stripos($contentType, 'application/json') === false) {
            return [];
        }

        try {
            $jsonData = $this->request->getJSON(true);

            return is_array($jsonData) ? $jsonData : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param array<string, mixed> $requestData
     * @return array<string, mixed>
     */
    private function normalizeBrandRequestData(array $requestData): array
    {
        $socialLinks = [];
        foreach (['facebook', 'instagram', 'twitter', 'youtube', 'linkedin', 'tiktok'] as $socialNetwork) {
            $socialField = 'social_' . $socialNetwork;
            $socialUrl = trim((string) ($requestData[$socialField] ?? ''));
            if ($socialUrl !== '') {
                $socialLinks[$socialNetwork] = $socialUrl;
            }
            unset($requestData[$socialField]);
        }

        if ($socialLinks !== [] || ! array_key_exists('social_links', $requestData)) {
            $requestData['social_links'] = $socialLinks;
        }

        return $requestData;
    }

}
