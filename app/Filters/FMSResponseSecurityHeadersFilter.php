<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Adds the FMS API response security headers after the request is handled.
 *
 * Never removes framework headers; it only sets defensive values that are
 * missing or weaker than the baseline.
 */
final class FMSResponseSecurityHeadersFilter implements FilterInterface
{
    public function before(RequestInterface $request, $filterArguments = null)
    {
        return null;
    }

    /**
     * @param list<string>|null $filterArguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $filterArguments = null)
    {
        $this->setHeaderWhenMissing($response, 'X-Content-Type-Options', 'nosniff');
        $this->setHeaderWhenMissing($response, 'X-Frame-Options', 'DENY');
        $this->setHeaderWhenMissing($response, 'Referrer-Policy', 'no-referrer');
        $this->setHeaderWhenMissing($response, 'X-Permitted-Cross-Domain-Policies', 'none');
        $this->setHeaderWhenMissing($response, 'Cross-Origin-Opener-Policy', 'same-origin');
        $this->setHeaderWhenMissing($response, 'Cross-Origin-Resource-Policy', 'same-origin');
        $this->setHeaderWhenMissing($response, 'Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'; base-uri 'none'");

        $response->setHeader('Cache-Control', 'no-store, max-age=0, no-cache');
        $response->setHeader('Pragma', 'no-cache');

        return $response;
    }

    private function setHeaderWhenMissing(ResponseInterface $response, string $headerName, string $headerValue): void
    {
        if (! $response->hasHeader($headerName)) {
            $response->setHeader($headerName, $headerValue);
        }
    }
}
