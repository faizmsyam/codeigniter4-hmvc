<?php

namespace App\Core;

use App\Filters\FMSRequestContext;
use App\Libraries\FMSApiResponse;
use App\Libraries\FMSPermissionAuthorizationService;
use CodeIgniter\HTTP\ResponseInterface;

class FMSApiController extends FMSController
{
    protected FMSApiResponse $apiResponse;
    protected FMSPermissionAuthorizationService $authorizationService;

    public function __construct(
        ?FMSApiResponse $apiResponse = null,
        ?FMSPermissionAuthorizationService $authorizationService = null,
    ) {
        $this->apiResponse = $apiResponse ?? new FMSApiResponse();
        $this->authorizationService = $authorizationService ?? new FMSPermissionAuthorizationService();
    }

    protected function respondSuccess(
        int|string $responseCode,
        string $responseMessage,
        mixed $responseData = null,
        int $httpStatusCode = 200,
    ): ResponseInterface {
        return $this->respondWithStatus(true, $responseMessage, $responseData, $httpStatusCode);
    }

    protected function respondOk(string $responseMessage, mixed $responseData = null): ResponseInterface
    {
        return $this->respondWithStatus(true, $responseMessage, $responseData, 200);
    }

    protected function respondCreated(string $responseMessage, mixed $responseData = null): ResponseInterface
    {
        return $this->respondWithStatus(true, $responseMessage, $responseData, 201);
    }

    protected function respondError(
        int|string $responseCode,
        string $responseMessage,
        mixed $responseData = null,
        int $httpStatusCode = 400,
    ): ResponseInterface {
        return $this->respondWithStatus(false, $responseMessage, $responseData, $httpStatusCode);
    }

    protected function respondBadRequest(string $responseMessage, mixed $responseData = null): ResponseInterface
    {
        return $this->respondWithStatus(false, $responseMessage, $responseData, 400);
    }

    protected function respondUnauthorized(string $responseMessage = 'Token akses tidak valid.', mixed $responseData = null): ResponseInterface
    {
        return $this->respondWithStatus(false, $responseMessage, $responseData, 401);
    }

    protected function respondForbidden(string $responseMessage = 'Akses ditolak.', mixed $responseData = null): ResponseInterface
    {
        return $this->respondWithStatus(false, $responseMessage, $responseData, 403);
    }

    protected function respondNotFound(string $responseMessage = 'Data tidak ditemukan.', mixed $responseData = null): ResponseInterface
    {
        return $this->respondWithStatus(false, $responseMessage, $responseData, 404);
    }

    protected function respondUnprocessableEntity(string $responseMessage, mixed $responseData = null): ResponseInterface
    {
        return $this->respondWithStatus(false, $responseMessage, $responseData, 422);
    }

    protected function respondServerError(string $responseMessage = 'Permintaan gagal diproses.', mixed $responseData = null): ResponseInterface
    {
        return $this->respondWithStatus(false, $responseMessage, $responseData, 500);
    }

    protected function respondWithStatus(
        bool $responseStatus,
        string $responseMessage,
        mixed $responseData,
        int $httpStatusCode,
    ): ResponseInterface {
        return $this->response
            ->setStatusCode($httpStatusCode)
            ->setJSON(($responseStatus ? $this->apiResponse->success(
                $httpStatusCode,
                $responseMessage,
                $responseData,
                $this->request->getHeaderLine('X-Request-ID') ?: null,
                gmdate(DATE_ATOM),
            ) : $this->apiResponse->error(
                $httpStatusCode,
                $responseMessage,
                $responseData,
                $this->request->getHeaderLine('X-Request-ID') ?: null,
                gmdate(DATE_ATOM),
            )));
    }

    /**
     * Tolak request API bila subject JWT tidak memiliki permission yang diminta.
     */
    protected function requireApiPermission(string $requiredPermission): ?ResponseInterface
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);
        if (! is_array($authenticatedSubject)) {
            return $this->respondUnauthorized();
        }

        if (! $this->authorizationService->allows($authenticatedSubject, $requiredPermission)) {
            return $this->respondForbidden();
        }

        return null;
    }

    protected function authenticatedApiUserIdentifier(): int
    {
        $authenticatedSubject = FMSRequestContext::authenticatedSubject($this->request);

        return is_array($authenticatedSubject)
            ? $this->authorizationService->authenticatedUserIdentifier($authenticatedSubject)
            : 0;
    }
    /**
     * @param array<string, array<int, string>> $validationErrors
     */
    protected function respondValidationError(array $validationErrors): ResponseInterface
    {
        return $this->response
            ->setStatusCode(422)
            ->setJSON($this->apiResponse->validationError(
                422,
                'Data yang dikirim tidak valid.',
                $validationErrors,
                $this->request->getHeaderLine('X-Request-ID') ?: null,
                gmdate(DATE_ATOM),
            ));
    }
}
