<?php

namespace Tests\Unit\Modules\Authentication;

use CodeIgniter\Test\CIUnitTestCase;

final class FMSBackendAuthenticationIntegrationTest extends CIUnitTestCase
{
    public function testBackendAuthenticationRoutesAndFilterAreRegistered(): void
    {
        $publicRoutes = (string) file_get_contents(APPPATH . 'Modules/Authentication/Config/PublicRoutes.php');
        $filters = (string) file_get_contents(APPPATH . 'Config/Filters.php');
        $routes = (string) file_get_contents(APPPATH . 'Config/Routes.php');

        $this->assertStringContainsString("\$routes->get('in'", $publicRoutes);
        $this->assertStringNotContainsString("\$routes->get('out'", $publicRoutes);
        $this->assertStringNotContainsString("\$routes->post('in'", $publicRoutes);
        $this->assertStringNotContainsString("\$routes->post('fms-auth/in'", $publicRoutes);
        $this->assertStringContainsString('fms-backend-authentication', $filters);
        $this->assertStringContainsString("'filter' => 'fms-backend-authentication'", $routes);
    }

    public function testLoginViewUsesAjaxLoginAndShowsFailures(): void
    {
        $loginView = (string) file_get_contents(APPPATH . 'Modules/Authentication/Views/auth/login.php');

        $this->assertStringContainsString('<form', $loginView);
        $this->assertStringContainsString('csrf_field()', $loginView);
        $this->assertStringContainsString('name="identifier"', $loginView);
        $this->assertStringContainsString('name="password"', $loginView);
        $this->assertStringContainsString('FMS.ajax', $loginView);
        $this->assertStringContainsString("site_url('api/v1/auth/login')", $loginView);
        $this->assertStringContainsString('signin-alert', $loginView);
        $this->assertStringNotContainsString('<?=', $loginView);
    }

    public function testBackendControllerUsesPermissionFilteredMenuAccessService(): void
    {
        $backendController = (string) file_get_contents(APPPATH . 'Core/FMSBackendController.php');

        $this->assertStringContainsString('FMSAdminMenuAccessService', $backendController);
        $this->assertStringContainsString('sidebarForUserIdentifier', $backendController);
        $this->assertStringNotContainsString('getActiveMenus()', $backendController);
    }

    public function testProfileDropdownUsesAjaxLogoutAndNoLegacyOutLink(): void
    {
        $headerView = (string) file_get_contents(APPPATH . 'Views/backend/_partials/header.php');

        $this->assertStringContainsString('$backendUser', $headerView);
        $this->assertStringContainsString("site_url('api/v1/auth/logout')", $headerView);
        $this->assertStringContainsString('data-fms-logout', $headerView);
        $this->assertStringNotContainsString("site_url('fms-auth/out')", $headerView);
        $this->assertStringNotContainsString('Tom Phillip', $headerView);
        $this->assertStringNotContainsString('sign-in-cover.html', $headerView);
    }
}
