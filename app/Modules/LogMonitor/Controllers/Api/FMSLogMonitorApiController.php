<?php

namespace App\Modules\LogMonitor\Controllers\Api;

use App\Core\FMSApiController;
use App\Modules\LogMonitor\Validation\FMSLogMonitorValidation;
use CodeIgniter\HTTP\ResponseInterface;

final class FMSLogMonitorApiController extends FMSApiController
{
    private FMSLogMonitorValidation $logMonitorValidation;

    public function __construct()
    {
        parent::__construct();
        $this->logMonitorValidation = new FMSLogMonitorValidation();
    }

    public function files(): ResponseInterface
    {
        $authorizationFailureResponse = $this->requireApiPermission('log_monitor.read');
        if ($authorizationFailureResponse !== null) {
            return $authorizationFailureResponse;
        }

        $validationService = \Config\Services::validation();
        $validationService->setRules($this->logMonitorValidation->getFilesRules());

        $filters = [
            'path' => $this->request->getGet('path'),
        ];

        if (! $validationService->run($filters)) {
            return $this->respondValidationError($validationService->getErrors());
        }

        $path = (string) ($filters['path'] ?? '');
        $service = new \App\Modules\LogMonitor\Services\FMSLogMonitorService();

        try {
            $files = $service->listFiles($path);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError(422, $e->getMessage(), null, 422);
        }

        return $this->respondSuccess(200, 'Daftar log berhasil dimuat.', [
            'base_path' => $service->getBasePath(),
            'path' => $path,
            'files' => $files,
        ]);
    }

    public function read(): ResponseInterface
    {
        $authorizationFailureResponse = $this->requireApiPermission('log_monitor.read');
        if ($authorizationFailureResponse !== null) {
            return $authorizationFailureResponse;
        }

        $validationService = \Config\Services::validation();
        $validationService->setRules($this->logMonitorValidation->getReadRules());

        $filters = [
            'path' => $this->request->getGet('path'),
            'offset' => $this->request->getGet('offset'),
            'limit' => $this->request->getGet('limit'),
            'search' => $this->request->getGet('search'),
        ];

        if (! $validationService->run($filters)) {
            return $this->respondValidationError($validationService->getErrors());
        }

        $path = (string) ($filters['path'] ?? '');
        $offset = (int) ($filters['offset'] ?? 0);
        $limit = (int) ($filters['limit'] ?? 500);
        $search = (string) ($filters['search'] ?? '');

        $service = new \App\Modules\LogMonitor\Services\FMSLogMonitorService();

        try {
            $content = $service->readLines($path, $offset, $limit, $search);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError(422, $e->getMessage(), null, 422);
        } catch (\RuntimeException $e) {
            return $this->respondError(500, $e->getMessage(), null, 500);
        }

        return $this->respondSuccess(200, 'Isi log berhasil dimuat.', [
            'path' => $path,
            'offset' => $offset,
            'limit' => $limit,
            'total_lines' => $content['total_lines'],
            'lines' => $content['lines'],
            'has_more' => $content['has_more'],
        ]);
    }

    public function stats(): ResponseInterface
    {
        $authorizationFailureResponse = $this->requireApiPermission('log_monitor.read');
        if ($authorizationFailureResponse !== null) {
            return $authorizationFailureResponse;
        }

        $validationService = \Config\Services::validation();
        $validationService->setRules($this->logMonitorValidation->getStatsRules());

        $filters = [
            'path' => $this->request->getGet('path'),
        ];

        if (! $validationService->run($filters)) {
            return $this->respondValidationError($validationService->getErrors());
        }

        $path = (string) ($filters['path'] ?? '');
        $service = new \App\Modules\LogMonitor\Services\FMSLogMonitorService();

        try {
            $stats = $service->stats($path);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError(422, $e->getMessage(), null, 422);
        }

        return $this->respondSuccess(200, 'Statistik log berhasil dimuat.', [
            'path' => $path,
            'stats' => $stats,
        ]);
    }

    public function download(): ResponseInterface
    {
        $authorizationFailureResponse = $this->requireApiPermission('log_monitor.read');
        if ($authorizationFailureResponse !== null) {
            return $authorizationFailureResponse;
        }

        $validationService = \Config\Services::validation();
        $validationService->setRules($this->logMonitorValidation->getDownloadRules());

        $filters = [
            'path' => $this->request->getGet('path'),
        ];

        if (! $validationService->run($filters)) {
            return $this->respondValidationError($validationService->getErrors());
        }

        $path = (string) ($filters['path'] ?? '');
        $service = new \App\Modules\LogMonitor\Services\FMSLogMonitorService();

        try {
            $filePath = $service->resolvePath($path);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError(422, $e->getMessage(), null, 422);
        }

        $filename = basename($filePath);
        $mime = mime_content_type($filePath) ?: 'text/plain';

        return $this->response
            ->setStatusCode(200)
            ->setContentType($mime)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody(file_get_contents($filePath));
    }
}
