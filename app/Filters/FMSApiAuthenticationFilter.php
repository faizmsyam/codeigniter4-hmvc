<?php

namespace App\Filters;

use App\Libraries\FMSApiResponse;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Gerbang autentikasi API tunggal untuk Bearer JWT, Basic, dan API key.
 *
 * Filter memilih tepat satu verifier berdasarkan credential yang dikirim.
 * Subject hasil verifikasi selalu ditaruh di FMSRequestContext sehingga
 * requireApiPermission() dapat memeriksa claim permissions/scopes seragam.
 */
final class FMSApiAuthenticationFilter implements FilterInterface
{
    public function __construct(
        private readonly ?FMSJwtAuthenticationFilter $jwtFilter = null,
        private readonly ?FMSApiKeyAuthenticationFilter $apiKeyFilter = null,
        private readonly ?FMSBasicAuthenticationFilter $basicFilter = null,
        private readonly ?FMSApiResponse $apiResponse = null,
    ) {
    }

    public function before(RequestInterface $request, $filterArguments = null)
    {
        $authorizationHeader = trim((string) $request->getHeaderLine('Authorization'));
        $apiKeyHeader = trim((string) $request->getHeaderLine('X-API-Key'));

        if ($apiKeyHeader !== '' && $authorizationHeader !== '') {
            return $this->failure('Gunakan tepat satu jenis kredensial API.', 400);
        }

        if ($apiKeyHeader !== '' || preg_match('/\AApiKey\s+/i', $authorizationHeader) === 1) {
            return ($this->apiKeyFilter ?? new FMSApiKeyAuthenticationFilter())->before($request, $filterArguments);
        }

        if (preg_match('/\ABasic\s+/i', $authorizationHeader) === 1) {
            return ($this->basicFilter ?? new FMSBasicAuthenticationFilter())->before($request, $filterArguments);
        }

        if (preg_match('/\ABearer\s+/i', $authorizationHeader) === 1) {
            return ($this->jwtFilter ?? new FMSJwtAuthenticationFilter())->before($request, $filterArguments);
        }

        return $this->failure('Kredensial API wajib dikirim.', 401);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $filterArguments = null)
    {
        return null;
    }

    private function failure(string $message, int $httpStatusCode): ResponseInterface
    {
        $response = service('response');
        $envelope = ($this->apiResponse ?? new FMSApiResponse())->error(
            $httpStatusCode,
            $message,
            null,
            null,
            gmdate(DATE_ATOM),
        );

        return $response
            ->setStatusCode($httpStatusCode)
            ->setContentType('application/json')
            ->setJSON($envelope);
    }
}
