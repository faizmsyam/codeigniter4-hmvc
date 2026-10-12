<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter pendeteksi dan pemblokir otomatis scanner keamanan agresif / automated tools
 * (Burp Suite, SQLMap, Acunetix, Nikto, DirBuster, gobuster, masscan, dsb).
 */
final class FMSAntiScannerFilter implements FilterInterface
{
    /**
     * Pola User-Agent scanner / testing tool yang langsung diblokir.
     */
    private const BLOCKED_USER_AGENTS = [
        'sqlmap',
        'burpcollaborator',
        'burpsuite',
        'burp suite',
        'acunetix',
        'nikto',
        'dirbuster',
        'gobuster',
        'wpscan',
        'masscan',
        'nmap',
        'havij',
        'pangolin',
        'zaproxy',
        'owasp zap',
        'nessus',
        'openvas',
        'arachni',
        'netsparker',
    ];

    /**
     * Header HTTP khusus Burp Suite / automated proxy scan.
     */
    private const BLOCKED_HEADERS = [
        'X-Burp-Collaborator',
        'X-Burp-Origin',
        'X-Scan-Memo',
        'X-Sqlmap-Scan',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $userAgent = '';
        if ($request instanceof IncomingRequest) {
            $uaObj = $request->getUserAgent();
            $userAgent = $uaObj ? (string) $uaObj->getAgentString() : '';
        }
        if ($userAgent === '') {
            $userAgent = (string) ($request->getHeaderLine('User-Agent') ?? '');
        }

        $uaLower = strtolower($userAgent);

        // 1. Cek User-Agent
        foreach (self::BLOCKED_USER_AGENTS as $scanner) {
            if (str_contains($uaLower, $scanner)) {
                return $this->blockRequest($request, 'Scanner User-Agent: ' . $scanner);
            }
        }

        // 2. Cek Header khas Burp Suite / SQLMap
        foreach (self::BLOCKED_HEADERS as $headerName) {
            if ($request->hasHeader($headerName)) {
                return $this->blockRequest($request, 'Scanner Header: ' . $headerName);
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function blockRequest(RequestInterface $request, string $reason): ResponseInterface
    {
        $ip = $request->getIPAddress();
        log_message('critical', sprintf('[SECURITY BLOCK] Akses diblokir dari IP %s | Alasan: %s', $ip, $reason));

        $response = service('response');
        $response->setStatusCode(403);

        $path = $request->getUri()->getPath();
        $isApi = str_starts_with(ltrim($path, '/'), 'api/') || str_contains((string) $request->getHeaderLine('Accept'), 'application/json');

        if ($isApi) {
            return $response->setJSON([
                'status'    => false,
                'code'      => 'ACCESS_DENIED',
                'message'   => 'Akses ditolak oleh sistem keamanan.',
                'timestamp' => date('c'),
            ]);
        }

        return $response->setBody(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>403 Forbidden</title></head>'
            . '<body style="font-family:sans-serif;text-align:center;padding:50px;">'
            . '<h1>403 Forbidden</h1><p>Akses ditolak oleh sistem keamanan FMS.</p>'
            . '</body></html>'
        );
    }
}
