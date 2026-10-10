<?php

namespace App\Filters;

use App\Config\FMSApiSecurity;
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
        $userIdentifier = (int) $backendSession->get('fms_backend_user_id');
        if ($backendSession->get('fms_backend_authenticated') === true && $userIdentifier > 0) {
            $tokenFamilyIdentifier = $this->resolveTokenFamilyIdentifier($request, $backendSession);
            if ($tokenFamilyIdentifier !== '' && $this->isTokenFamilyActive($userIdentifier, $tokenFamilyIdentifier)) {
                // Hard gate: users who must change password may access ONLY
                // the change-password page. Direct dashboard/menu URLs are blocked.
                $path = trim($request->getUri()->getPath(), '/');
                $changePasswordPath = trim(ROUTE_ADMIN . '/change-password', '/');
                if ($backendSession->get('fms_backend_must_change_password') === true
                    && $path !== $changePasswordPath) {
                    return redirect()->to(site_url($changePasswordPath));
                }

                return null;
            }

            $this->clearBackendSession($backendSession);
        }

        $backendSession->set('fms_backend_intended_url', current_url());

        return redirect()->to(site_url('fms-auth/in'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function resolveTokenFamilyIdentifier(RequestInterface $request, object $backendSession): string
    {
        $storedFamilyIdentifier = trim((string) $backendSession->get('fms_backend_token_family_id'));
        if ($storedFamilyIdentifier !== '') {
            return $storedFamilyIdentifier;
        }

        if (! method_exists($request, 'getCookie')) {
            return '';
        }

        $cookieName = config(FMSApiSecurity::class)->refreshCookieName;
        $presentedRefreshToken = trim((string) $request->getCookie($cookieName));
        $selector = explode('.', $presentedRefreshToken, 2)[0] ?? '';
        if ($selector === '') {
            return '';
        }

        $tokenRow = db_connect()
            ->table('t_api_refresh_tokens')
            ->select('token_family_id')
            ->where('selector', $selector)
            ->get(1)
            ->getRowArray();
        $resolvedFamilyIdentifier = is_array($tokenRow)
            ? trim((string) ($tokenRow['token_family_id'] ?? ''))
            : '';

        if ($resolvedFamilyIdentifier !== '') {
            $backendSession->set('fms_backend_token_family_id', $resolvedFamilyIdentifier);
        }

        return $resolvedFamilyIdentifier;
    }

    private function isTokenFamilyActive(int $userIdentifier, string $tokenFamilyIdentifier): bool
    {
        $database = db_connect();
        $currentTimestamp = date('Y-m-d H:i:s');
        $sessionRow = $database
            ->table('t_user_sessions')
            ->select('revoked_at, expires_at')
            ->where('user_id', $userIdentifier)
            ->where('token_family_id', $tokenFamilyIdentifier)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        if (is_array($sessionRow)) {
            return empty($sessionRow['revoked_at'])
                && (string) ($sessionRow['expires_at'] ?? '') > $currentTimestamp;
        }

        $fallbackSession = $database
            ->table('t_api_refresh_tokens')
            ->select('issued_at, expires_at')
            ->where('user_id', $userIdentifier)
            ->where('token_family_id', $tokenFamilyIdentifier)
            ->where('used_at', null)
            ->where('revoked_at', null)
            ->where('expires_at >', $currentTimestamp)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();
        if (! is_array($fallbackSession)) {
            return false;
        }

        $backendLoginTimestamp = (int) session()->get('fms_backend_logged_in_at');
        $sessionExpiresTimestamp = $backendLoginTimestamp + config(\Config\Session::class)->expiration;

        return $backendLoginTimestamp > 0 && $sessionExpiresTimestamp > time();
    }

    private function clearBackendSession(object $backendSession): void
    {
        foreach (array_keys($backendSession->get()) as $sessionKey) {
            if (str_starts_with((string) $sessionKey, 'fms_backend_')) {
                $backendSession->remove((string) $sessionKey);
            }
        }
        $backendSession->regenerate(true);
    }
}
