<?php

namespace App\Modules\ActivityLogs\Controllers\Api;

use App\Core\FMSApiController;
use App\Modules\ActivityLogs\Models\FMSActivityLogModel;
use App\Modules\ActivityLogs\Services\FMSActivityLogRedactionService;
use App\Modules\ActivityLogs\Services\FMSActivityLogService;
use App\Modules\ActivityLogs\Validation\FMSActivityLogValidation;
use CodeIgniter\HTTP\ResponseInterface;

final class FMSActivityLogsApiController extends FMSApiController
{
    private FMSActivityLogService $activityLogService;

    private FMSActivityLogValidation $activityLogValidation;

    public function __construct(
        ?FMSActivityLogService $activityLogService = null,
        ?FMSActivityLogValidation $activityLogValidation = null,
    ) {
        parent::__construct();

        $this->activityLogService = $activityLogService
            ?? new FMSActivityLogService(new FMSActivityLogModel(), new FMSActivityLogRedactionService());
        $this->activityLogValidation = $activityLogValidation ?? new FMSActivityLogValidation();
    }

    public function index(): ResponseInterface
    {
        $authorizationFailureResponse = $this->requireApiPermission('activity_logs.read');
        if ($authorizationFailureResponse !== null) {
            return $authorizationFailureResponse;
        }

        $validationService = \Config\Services::validation();
        $validationService->setRules($this->activityLogValidation->getListRules());

        $searchFilters = [
            'actor_user_id'  => $this->request->getGet('actor_user_id'),
            'event'          => $this->request->getGet('event'),
            'module'         => $this->request->getGet('module'),
            'entity_type'    => $this->request->getGet('entity_type'),
            'entity_id'      => $this->request->getGet('entity_id'),
            'http_method'    => $this->request->getGet('http_method'),
            'status_code'    => $this->request->getGet('status_code'),
            'request_id'     => $this->request->getGet('request_id'),
            'date_from'      => $this->request->getGet('date_from'),
            'date_to'        => $this->request->getGet('date_to'),
            'search_keyword' => $this->request->getGet('search_keyword'),
            'page'           => $this->request->getGet('page'),
            'per_page'       => $this->request->getGet('per_page'),
        ];

        if (! $validationService->run($searchFilters)) {
            return $this->respondValidationError($validationService->getErrors());
        }

        $pageNumber = (int) ($searchFilters['page'] ?? 1);
        $pageSize = (int) ($searchFilters['per_page'] ?? FMSActivityLogService::DEFAULT_PAGE_SIZE);

        $searchResult = $this->activityLogService->searchActivity($searchFilters, $pageNumber, $pageSize);

        return $this->respondSuccess(200, 'Daftar activity logs berhasil dimuat.', $searchResult);
    }

    public function show(?string $activityLogUuid = null): ResponseInterface
    {
        $authorizationFailureResponse = $this->requireApiPermission('activity_logs.read');
        if ($authorizationFailureResponse !== null) {
            return $authorizationFailureResponse;
        }

        $validationService = \Config\Services::validation();
        $validationService->setRules($this->activityLogValidation->getIdentifierRules());

        if (! $validationService->run(['uuid' => $activityLogUuid])) {
            return $this->respondValidationError($validationService->getErrors());
        }

        $activityLogEntry = $this->activityLogService->findActivity((string) $activityLogUuid);

        if ($activityLogEntry === null) {
            return $this->respondError(404, 'Activity log tidak ditemukan.', null, 404);
        }

        return $this->respondSuccess(200, 'Detail activity log berhasil dimuat.', $activityLogEntry);
    }

    public function export(): ResponseInterface
    {
        $authorizationFailureResponse = $this->requireApiPermission('activity_logs.export');
        if ($authorizationFailureResponse !== null) {
            return $authorizationFailureResponse;
        }

        $validationService = \Config\Services::validation();
        $validationService->setRules($this->activityLogValidation->getExportRules());

        $searchFilters = [
            'actor_user_id'  => $this->request->getGet('actor_user_id'),
            'event'          => $this->request->getGet('event'),
            'module'         => $this->request->getGet('module'),
            'entity_type'    => $this->request->getGet('entity_type'),
            'entity_id'      => $this->request->getGet('entity_id'),
            'http_method'    => $this->request->getGet('http_method'),
            'status_code'    => $this->request->getGet('status_code'),
            'request_id'     => $this->request->getGet('request_id'),
            'date_from'      => $this->request->getGet('date_from'),
            'date_to'        => $this->request->getGet('date_to'),
            'search_keyword' => $this->request->getGet('search_keyword'),
        ];

        if (! $validationService->run($searchFilters)) {
            return $this->respondValidationError($validationService->getErrors());
        }

        try {
            $csvContents = $this->activityLogService->exportActivityAsCsv($searchFilters);
        } catch (\RuntimeException $exportException) {
            return $this->respondError(422, $exportException->getMessage(), null, 422);
        }

        return $this->response
            ->setStatusCode(200)
            ->setContentType('text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="activity-logs-export.csv"')
            ->setBody($csvContents);
    }

}
