<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Protects the HTML admin area with the server-side backend session.
 *
 * JWT remains the API credential; the backend session contains only the
 * authenticated user identity and presentation data, never an access token.
 */
final class FMSBackendAuthenticationFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $backendSession = session();
        if ($backendSession->get('fms_backend_authenticated') === true
            && (int) $backendSession->get('fms_backend_user_id') > 0) {
            return null;
        }

        $backendSession->set('fms_backend_intended_url', current_url());

        return redirect()->to(site_url('fms-auth/in'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
