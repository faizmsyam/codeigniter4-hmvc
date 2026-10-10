<?php
namespace App\Filters;

use App\Modules\Storage\Services\FMSStorageSessionService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class FMSStorageAuthenticationFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $token = (string) $request->getCookie(FMSStorageSessionService::COOKIE_NAME);
        $user = (new FMSStorageSessionService())->resolve($token);
        if (is_array($user) && (string) ($user['status'] ?? '') === 'active') {
            return null;
        }

        if (str_starts_with(trim($request->getUri()->getPath(), '/'), 'api/')) {
            return service('response')->setStatusCode(401)->setJSON([
                'status' => false,
                'message' => 'Storage authentication required.',
            ]);
        }

        $next = rawurlencode($request->getUri()->getPath());
        return redirect()->to(site_url('fms-storage/login') . '?next=' . $next);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
