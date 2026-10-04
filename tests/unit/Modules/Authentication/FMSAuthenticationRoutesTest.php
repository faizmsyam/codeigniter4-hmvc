<?php

namespace Tests\Unit\Modules\Authentication;

use App\Config\FMSApiSecurity as FMSApiSecurityConfig;
use App\Filters\FMSApiAuthenticationFilter;
use App\Filters\FMSFiltersRegistry;
use App\Modules\Authentication\Controllers\Api\FMSAuthenticationApiController;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Filters;
use Config\App;
use ReflectionMethod;

final class FMSAuthenticationRoutesTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testPublicRoutesOnlyExposeGetLogin(): void
    {
        $publicRoutes = (string) file_get_contents(APPPATH . 'Modules/Authentication/Config/PublicRoutes.php');

        $this->assertStringContainsString("\$routes->get('fms-auth/in'", $publicRoutes);
        $this->assertStringContainsString("\$routes->get('in'", $publicRoutes);
        $this->assertStringNotContainsString("fms-auth/out", $publicRoutes);
        $this->assertStringNotContainsString("\$routes->post('fms-auth/in'", $publicRoutes);
        $this->assertStringNotContainsString("::class, 'authenticate'", $publicRoutes);
        $this->assertStringNotContainsString("::class, 'logout'", $publicRoutes);
    }

    public function testBackendAuthFilterRedirectsToFmsAuthIn(): void
    {
        $filterSource = (string) file_get_contents(APPPATH . 'Filters/FMSBackendAuthenticationFilter.php');

        $this->assertStringContainsString("site_url('fms-auth/in')", $filterSource);
        $this->assertStringNotContainsString("site_url('in')", $filterSource);
    }

    public function testAuthControllerOnlyRendersLoginViewWithoutProcessing(): void
    {
        $controllerSource = (string) file_get_contents(APPPATH . 'Modules/Authentication/Controllers/FMSAuthenticationController.php');

        $this->assertStringContainsString("fmsLayout('login')", $controllerSource);
        $this->assertStringContainsString("site_url(ROUTE_ADMIN . '/dashboard')", $controllerSource);
        $this->assertStringNotContainsString("site_url('fms-auth/in')", $controllerSource);
        $this->assertStringNotContainsString("site_url('in')", $controllerSource);
        $this->assertStringNotContainsString('function authenticate', $controllerSource);
        $this->assertStringNotContainsString('function logout', $controllerSource);
    }

    public function testLoginViewSubmitsViaAjaxToApiLoginAndShowsFailure(): void
    {
        $viewSource = (string) file_get_contents(APPPATH . 'Modules/Authentication/Views/auth/login.php');

        $this->assertStringContainsString("site_url('api/v1/auth/login')", $viewSource);
        $this->assertStringContainsString('FMS.ajax', $viewSource);
        $this->assertStringContainsString('signin-alert', $viewSource);
        $this->assertStringNotContainsString("site_url('fms-auth/in')", $viewSource);
    }

    public function testHeaderAndSidebarLogoutThroughApiWithoutLegacyBackendPost(): void
    {
        $headerSource = (string) file_get_contents(APPPATH . 'Views/backend/_partials/header.php');
        $sidebarSource = (string) file_get_contents(APPPATH . 'Views/backend/_partials/sidebar.php');

        $this->assertStringContainsString("site_url('api/v1/auth/logout')", $headerSource);
        $this->assertStringContainsString("site_url('api/v1/auth/logout')", $sidebarSource);
        $this->assertStringContainsString('data-fms-logout', $headerSource);
        $this->assertStringContainsString('data-fms-logout', $sidebarSource);
        $this->assertStringNotContainsString("site_url('fms-auth/out')", $headerSource);
        $this->assertStringNotContainsString("site_url('fms-auth/out')", $sidebarSource);
    }

    public function testApiAuthenticationGateAcceptsAllCredentialTypes(): void
    {
        $filtersConfig = new Filters();
        $this->assertArrayHasKey('fms-api-authentication', $filtersConfig->aliases);
        $this->assertSame(FMSApiAuthenticationFilter::class, $filtersConfig->aliases['fms-api-authentication']);
        $this->assertSame(FMSApiAuthenticationFilter::class, FMSFiltersRegistry::aliases()['fms-api-authentication']);

        foreach (['Brand', 'Uploads', 'Privileges', 'Users', 'AdminMenus', 'ActivityLogs'] as $moduleName) {
            $routesSource = (string) file_get_contents(APPPATH . 'Modules/' . $moduleName . '/Config/ApiRoutes.php');
            $this->assertStringContainsString('fms-api-authentication', $routesSource);
            $this->assertStringNotContainsString('fms-jwt-authentication', $routesSource);
        }

        $apiMeRoute = (string) file_get_contents(APPPATH . 'Modules/Authentication/Config/ApiRoutes.php');
        $this->assertStringContainsString('fms-api-authentication', $apiMeRoute);
    }

    public function testApiLogoutHasNoJwtFilterAndClearsBackendSession(): void
    {
        $apiRoutes = (string) file_get_contents(APPPATH . 'Modules/Authentication/Config/ApiRoutes.php');
        $logoutController = (string) file_get_contents(APPPATH . 'Modules/Authentication/Controllers/Api/FMSAuthenticationApiController.php');

        $logoutRouteLine = '';
        foreach (explode("\n", $apiRoutes) as $routeLine) {
            if (str_contains($routeLine, "'auth/logout'")) {
                $logoutRouteLine = $routeLine;
            }
        }

        $this->assertStringContainsString("\$routes->post('auth/logout'", $logoutRouteLine);
        $this->assertStringNotContainsString('filter', $logoutRouteLine);
        $this->assertStringContainsString("->regenerate(true)", $logoutController);
        $this->assertStringContainsString("'auth.logout'", $logoutController);
        $this->assertStringContainsString('$this->request->isSecure()', $logoutController);
        $this->assertStringContainsString("'secure'   => \$secureCookie", $logoutController);
    }

    public function testLogoutWithoutBearerClearsBackendSession(): void
    {
        $response = $this
            ->withSession([
                'fms_backend_authenticated' => true,
                'fms_backend_user_id' => 99,
                'fms_backend_username' => 'feature-test',
            ])
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post('api/v1/auth/logout', []);

        $response->assertStatus(200);
        $response->assertSessionMissing('fms_backend_authenticated');
        $response->assertSessionMissing('fms_backend_user_id');
        $response->assertSessionMissing('fms_backend_username');
    }

    public function testRefreshCookiesAreNotMarkedSecureOnPlainHttp(): void
    {
        $controller = new FMSAuthenticationApiController();
        $request = service('request');
        $response = new Response(config(App::class));
        $controller->initController($request, $response, service('logger'));

        $this->assertFalse($request->isSecure(), 'Test request should simulate a plain HTTP connection.');

        $issueRefreshCookie = new ReflectionMethod($controller, 'issueRefreshCookie');
        $issueRefreshCookie->setAccessible(true);
        $issueRefreshCookie->invoke($controller, 'selector.validator');

        $cookieConfiguration = config(FMSApiSecurityConfig::class);
        $refreshCookie = $response->getCookie($cookieConfiguration->refreshCookieName);
        $csrfCookie = $response->getCookie($cookieConfiguration->csrfCookieName);

        $this->assertNotNull($refreshCookie);
        $this->assertNotNull($csrfCookie);
        $this->assertFalse($refreshCookie->isSecure());
        $this->assertFalse($csrfCookie->isSecure());
        $this->assertTrue($refreshCookie->isHttpOnly());
    }
}
