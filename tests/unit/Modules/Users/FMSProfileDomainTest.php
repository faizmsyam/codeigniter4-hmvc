<?php

namespace Tests\Unit\Modules\Users;

use App\Modules\Users\Controllers\Backend\FMSProfileBackendController;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSProfileDomainTest extends CIUnitTestCase
{
    public function testProfileBackendControllerRendersApiDrivenShell(): void
    {
        $controller = new FMSProfileBackendController();

        $this->assertTrue(method_exists($controller, 'index'));
        $source = (string) file_get_contents(APPPATH . 'Modules/Users/Controllers/Backend/FMSProfileBackendController.php');
        $this->assertStringContainsString('activityLogDetailUrl', $source);
        $this->assertStringContainsString('api/v1/profile/activity-logs', $source);
    }

    public function testProfileViewImplementsNavTabsLayoutWithHakAksesAndGantiRole(): void
    {
        $viewFile = APPPATH . 'Modules/Users/Views/backend/profile.php';
        $this->assertFileExists($viewFile);
        $source = (string) file_get_contents($viewFile);

        /* Kelompok aktif difilter melalui API profile. */
        $this->assertStringContainsString('switchableGroups', $source);
        $this->assertStringContainsString('switchable_groups', $source);

        /* Memastikan ada struktur Nav Tabs Bootstrap */
        $this->assertStringContainsString('nav nav-tabs', $source);
        $this->assertStringContainsString('tab-content', $source);
        $this->assertStringContainsString('tab-pane', $source);

        /* Memastikan ada tab Informasi Profil, Ganti Role / Kelompok, dan Hak Akses */
        $this->assertStringContainsString('tab-profile-info', $source);
        $this->assertStringContainsString('tab-profile-roles', $source);
        $this->assertStringContainsString('tab-profile-permissions', $source);
        $this->assertStringContainsString('activityChangeMarkup', $source);
        $this->assertStringContainsString('renderActivityPayload', $source);
        $this->assertStringContainsString('js-profile-activity-detail', $source);
        $this->assertStringContainsString('data-detail-url', $source);

        $apiController = (string) file_get_contents(APPPATH . 'Modules/Users/Controllers/Api/FMSProfileApiController.php');
        $this->assertStringContainsString('isSuperAdministrator($authenticatedSubject)', $apiController);
        $this->assertStringNotContainsString("=== 'faizmsyam'", $apiController);

        /* Memastikan tidak ada short tag <?= */
        $this->assertDoesNotMatchRegularExpression('/<\\?=(?!php|xml)/', $source);

        /* Memastikan komentar JS menggunakan block comments /* ... * / */
        $this->assertStringNotContainsString('//', $source);
    }

    public function testProfileControllerExportsSwitchableGroupsExcludingCurrentSession(): void
    {
        $controllerFile = APPPATH . 'Modules/Users/Controllers/Backend/FMSProfileBackendController.php';
        $source = (string) file_get_contents($controllerFile);

        $this->assertStringContainsString('activityLogDetailUrl', $source);
        $this->assertStringContainsString('activityLogsUrl', $source);
    }

    public function testProfileRolesTabNeverRendersDisabledButtonForActiveGroup(): void
    {
        $viewFile = APPPATH . 'Modules/Users/Views/backend/profile.php';
        $source = (string) file_get_contents($viewFile);

        /* Badge/tombol disabled untuk group aktif tidak boleh ada di tab switch role */
        $this->assertStringNotContainsString('Peran Sedang Digunakan', $source);
        $this->assertStringNotContainsString('Sedang Aktif', $source);
    }

    public function testProfileViewDoesNotRenderSearchPermissionsInput(): void
    {
        $viewFile = APPPATH . 'Modules/Users/Views/backend/profile.php';
        $source = (string) file_get_contents($viewFile);

        /* Tab hak akses aktif tidak memerlukan input cari hak akses */
        $this->assertStringNotContainsString('searchPermissions', $source);
        $this->assertStringNotContainsString('Cari hak akses', $source);
    }

    public function testProfileApiRoutesRegisterActivityDetailEndpoint(): void
    {
        $routesFile = APPPATH . 'Modules/Users/Config/ApiRoutes.php';
        $this->assertFileExists($routesFile);
        $source = (string) file_get_contents($routesFile);

        $this->assertStringContainsString("profile',", $source);
        $this->assertStringContainsString("activity-logs/(:segment)", $source);
        $this->assertStringContainsString('activityLogDetail', $source);
    }
}
