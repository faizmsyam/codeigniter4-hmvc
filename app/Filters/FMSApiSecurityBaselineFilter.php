<?php

namespace App\Filters;

use App\Config\FMSApiSecurity;
use App\Libraries\FMSApiResponse;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Baseline hardening for every `/api/v1` request.
 *
 * Order of decision is deterministic so failures are cheap and never reach a
 * controller: transport-level guards first (body size, content type), then
 * cross-origin denial, then defensive response headers.
 *
 * Browser cookie sessions are never accepted here: authentication on API routes
 * must be an explicit Authorization credential. Ambient cookies are ignored so a
 * cross-site request cannot ride an existing admin session.
 */
final class FMSApiSecurityBaselineFilter implements FilterInterface
{
    private const JSON_MEDIA_TYPE = 'application/json';

    private const MULTIPART_MEDIA_TYPE = 'multipart/form-data';

    private const BODYLESS_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    private const MULTIPART_ALLOWED_PATH_SUFFIXES = ['/uploads/images', '/brand/images', '/profile/avatar'];

    public function __construct(
        private readonly ?FMSApiSecurity $apiSecurityConfiguration = null,
    ) {
    }

    /**
     * @param list<string>|null $filterArguments
     */
    public function before(RequestInterface $request, $filterArguments = null)
    {
        $securityConfiguration = $this->apiSecurityConfiguration ?? config(FMSApiSecurity::class);
        $requestMethod = strtoupper($request->getMethod());

        $declaredBodyBytes = $this->declaredBodySize($request);
        $maximumBodyBytes = $this->isMultipartUploadPath($request)
            ? $securityConfiguration->maximumUploadBodyBytes
            : $securityConfiguration->maximumJsonBodyBytes;

        if ($declaredBodyBytes !== null && $declaredBodyBytes > $maximumBodyBytes) {
            return $this->reject($request, 'FMS_API_PAYLOAD_TOO_LARGE', 'Ukuran payload melebihi batas.', 413);
        }

        $actualBodyBytes = strlen((string) $request->getBody());
        if ($actualBodyBytes > $maximumBodyBytes) {
            return $this->reject($request, 'FMS_API_PAYLOAD_TOO_LARGE', 'Ukuran payload melebihi batas.', 413);
        }

        if ($securityConfiguration->requireJsonContentType && ! $this->isBodyless($requestMethod)) {
            if (! $this->hasAcceptableContentType($request)) {
                return $this->reject(
                    $request,
                    'FMS_API_CONTENT_TYPE_INVALID',
                    'Content-Type harus application/json.',
                    415,
                );
            }
        }

        if (! $securityConfiguration->allowCrossOriginRequests && $this->hasForeignOrigin($request)) {
            return $this->reject($request, 'FMS_API_CROSS_ORIGIN_DENIED', 'Permintaan lintas origin ditolak.', 403);
        }

        return null;
    }

    /**
     * @param list<string>|null $filterArguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $filterArguments = null)
    {
        $securityConfiguration = $this->apiSecurityConfiguration ?? config(FMSApiSecurity::class);

        foreach ($this->baselineHeaders() as $headerName => $headerValue) {
            if (! $response->hasHeader($headerName)) {
                $response->setHeader($headerName, $headerValue);
            }
        }

        $response->setHeader('Cache-Control', 'no-store, max-age=0, no-cache');
        $response->setHeader('Pragma', 'no-cache');

        if (! $securityConfiguration->allowCrossOriginRequests) {
            $response->removeHeader('Access-Control-Allow-Origin');
            $response->removeHeader('Access-Control-Allow-Credentials');
        }

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function baselineHeaders(): array
    {
        return [
            'X-Content-Type-Options'         => 'nosniff',
            'X-Frame-Options'                => 'DENY',
            'Referrer-Policy'                => 'no-referrer',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Cross-Origin-Opener-Policy'     => 'same-origin',
            'Cross-Origin-Resource-Policy'   => 'same-origin',
            'Content-Security-Policy'        => "default-src 'none'; frame-ancestors 'none'; base-uri 'none'",
        ];
    }

    private function isBodyless(string $requestMethod): bool
    {
        return in_array($requestMethod, self::BODYLESS_METHODS, true);
    }

    private function declaredBodySize(RequestInterface $request): ?int
    {
        $declaredLength = $request->getHeaderLine('Content-Length');
        if ($declaredLength === '' || ! ctype_digit($declaredLength)) {
            return null;
        }

        return (int) $declaredLength;
    }

    private function isMultipartUploadPath(RequestInterface $request): bool
    {
        $requestPath = '/' . trim($request->getUri()->getPath(), '/');
        foreach (self::MULTIPART_ALLOWED_PATH_SUFFIXES as $allowedSuffix) {
            if (str_ends_with($requestPath, $allowedSuffix)) {
                return true;
            }
        }

        return false;
    }

    private function hasAcceptableContentType(RequestInterface $request): bool
    {
        $contentType = strtolower(trim($request->getHeaderLine('Content-Type')));
        if ($contentType === '') {
            return false;
        }

        $mediaType = trim(explode(';', $contentType)[0]);

        if ($mediaType === self::JSON_MEDIA_TYPE) {
            return true;
        }

        return $mediaType === self::MULTIPART_MEDIA_TYPE && $this->isMultipartUploadPath($request);
    }

    /**
     * A browser sends Origin on cross-site requests. Same-origin `fetch` from the
     * admin UI also sends it, so only a foreign host is rejected.
     */
    private function hasForeignOrigin(RequestInterface $request): bool
    {
        $originHeader = trim($request->getHeaderLine('Origin'));
        if ($originHeader === '') {
            return false;
        }

        $originHost = parse_url($originHeader, PHP_URL_HOST);
        if (! is_string($originHost) || $originHost === '') {
            return true;
        }

        return strcasecmp($originHost, $request->getUri()->getHost()) !== 0;
    }

    private function reject(
        RequestInterface $request,
        string $responseCode,
        string $responseMessage,
        int $httpStatusCode,
    ): ResponseInterface {
        $response = service('response');
        $response->setStatusCode($httpStatusCode);
        $signedEnvelope = (new FMSApiResponse())->error(
            $httpStatusCode,
            $responseMessage,
            null,
            $request->getHeaderLine('X-Request-ID') ?: null,
            gmdate(DATE_ATOM),
        );
        $response->setHeader('Content-Type', 'application/json; charset=UTF-8');
        $response->setHeader('Cache-Control', 'no-store');
        $response->setHeader('Pragma', 'no-cache');
        $response->setBody(json_encode(
            $signedEnvelope,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        return $response;
    }
}
