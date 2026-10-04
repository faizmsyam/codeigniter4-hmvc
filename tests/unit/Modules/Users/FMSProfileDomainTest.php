<?php

namespace Tests\Unit\Modules\Users;

use App\Modules\Users\Controllers\Backend\FMSProfileBackendController;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSProfileDomainTest extends CIUnitTestCase
{
    public function testProfileControllerExposesAllRequiredMethods(): void
    {
        $controller = new FMSProfileBackendController();

        foreach (['index', 'switchGroup', 'updateProfile', 'updateAvatar', 'changePassword'] as $method) {
            $this->assertTrue(
                method_exists($controller, $method),
                'FMSProfileBackendController harus menyediakan method ' . $method . '.'
            );
        }
    }

    public function testProfileViewImplementsNavTabsLayoutWithHakAksesAndGantiRole(): void
    {
        $viewFile = APPPATH . 'Modules/Users/Views/backend/profile.php';
        $this->assertFileExists($viewFile);
        $source = (string) file_get_contents($viewFile);

        /* Kelompok aktif harus dikecualikan dari tab switch role */
        $this->assertStringContainsString('switchableGroups', $source);
        $this->assertStringContainsString('currentGroupId', $source);

        /* Memastikan ada struktur Nav Tabs Bootstrap */
        $this->assertStringContainsString('nav nav-tabs', $source);
        $this->assertStringContainsString('tab-content', $source);
        $this->assertStringContainsString('tab-pane', $source);

        /* Memastikan ada tab Informasi Profil, Ganti Role / Kelompok, dan Hak Akses */
        $this->assertStringContainsString('tab-profile-info', $source);
        $this->assertStringContainsString('tab-profile-roles', $source);
        $this->assertStringContainsString('tab-profile-permissions', $source);

        /* Memastikan tidak ada short tag <?= */
        $this->assertDoesNotMatchRegularExpression('/<\\?=(?!php|xml)/', $source);

        /* Memastikan komentar JS menggunakan block comments /* ... * / */
        $this->assertStringNotContainsString('//', $source);
    }

    public function testProfileControllerExportsSwitchableGroupsExcludingCurrentSession(): void
    {
        $controllerFile = APPPATH . 'Modules/Users/Controllers/Backend/FMSProfileBackendController.php';
        $source = (string) file_get_contents($controllerFile);

        /* Controller harus membuat variabel switchableGroups yang memfilter group aktif */
        $this->assertStringContainsString('switchableGroups', $source);
        $this->assertStringContainsString('fms_backend_active_group_id', $source);
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

    public function testProfileRoutesFileRegistersSwitchGroupEndpoint(): void
    {
        $routesFile = APPPATH . 'Modules/Users/Config/BackendRoutes.php';
        $this->assertFileExists($routesFile);
        $source = (string) file_get_contents($routesFile);

        $this->assertStringContainsString('profile/switch-group', $source);
        $this->assertStringContainsString('profile/change-password', $source);
    }
}
