<?php

namespace Tests\Unit\Modules\Brand;

use App\Modules\Brand\Services\FMSBrandService;
use App\Modules\Brand\Validation\FMSBrandValidation;
use CodeIgniter\Test\CIUnitTestCase;

final class FMSBrandDomainTest extends CIUnitTestCase
{
    public function testCreatePreparationNormalizesFieldsRejectsMassAssignmentAndAutoUuid(): void
    {
        $preparedBrandData = (new FMSBrandService())->prepareCreateData([
            'name'            => '  FMS Brand  ',
            'tagline'         => '  Solusi digital terpadu ',
            'description'     => ' Deskripsi perusahaan. ',
            'website_url'     => ' https://example.com ',
            'whatsapp'        => ' 628120000 ',
            'social_links'    => ['instagram' => 'https://instagram.com/fms'],
            'email'           => '  Support@Example.COM ',
            'address'         => '  Jl Merdeka 1 ',
            'phone'           => '  +62 812 0000 ',
            'logo_path'       => 'fms-brand/fms-0123456789abcdef.webp',
            'logo_light_path' => 'fms-brand/fms-abcdef0123456789.webp',
            'favicon_path'    => 'fms-brand/fms-AAAAAAAAAAAAAAAA.webp',
            'uuid'            => '0195e887-19df-7b10-913b-7315a7051a62',
            'is_active'       => '1',
            'id'              => 99,
            'created_by'      => 99,
        ], 7);

        $this->assertSame('FMS Brand', $preparedBrandData['name']);
        $this->assertSame('Support@Example.COM', $preparedBrandData['email']);
        $this->assertSame('support@example.com', $preparedBrandData['email_normalized']);
        $this->assertSame('Jl Merdeka 1', $preparedBrandData['address']);
        $this->assertSame('+62 812 0000', $preparedBrandData['phone']);
        $this->assertSame('Solusi digital terpadu', $preparedBrandData['tagline']);
        $this->assertSame('Deskripsi perusahaan.', $preparedBrandData['description']);
        $this->assertSame('https://example.com', $preparedBrandData['website_url']);
        $this->assertSame('628120000', $preparedBrandData['whatsapp']);
        $this->assertSame('{"instagram":"https://instagram.com/fms"}', $preparedBrandData['social_links']);
        $this->assertSame(7, $preparedBrandData['created_by']);
        $this->assertSame(7, $preparedBrandData['updated_by']);
        $this->assertSame('0195e887-19df-7b10-913b-7315a7051a62', $preparedBrandData['uuid']);
        $this->assertArrayNotHasKey('id', $preparedBrandData);
    }

    public function testUpdatePreparationReplacesChangedFieldsAndClearsUnchangedNormalizedEmail(): void
    {
        $preparedBrandData = (new FMSBrandService())->prepareUpdateData([
            'email' => 'new@example.com',
            'name'  => 'New Name',
        ], [
            'email_normalized' => 'old@example.com',
            'uuid'             => '0195e887-19df-7b10-913b-7315a7051a62',
        ], 9);

        $this->assertSame('new@example.com', $preparedBrandData['email_normalized']);
        $this->assertSame('New Name', $preparedBrandData['name']);
        $this->assertSame(9, $preparedBrandData['updated_by']);
        $this->assertArrayNotHasKey('uuid', $preparedBrandData);
        $this->assertArrayNotHasKey('id', $preparedBrandData);
    }

    public function testValidationRejectsInvalidBrandFields(): void
    {
        $validationErrors = FMSBrandValidation::validateCreate([
            'name'         => '',
            'email'        => 'not-an-email',
            'logo_path'    => '../evil.php',
            'favicon_path' => '/etc/passwd',
        ]);

        $this->assertArrayHasKey('email', $validationErrors);
        $this->assertArrayHasKey('logo_path', $validationErrors);
        $this->assertArrayHasKey('favicon_path', $validationErrors);
        $this->assertFalse(FMSBrandValidation::isValidUuid('not-a-uuid'));
        $this->assertTrue(FMSBrandValidation::isValidUuid('0195e887-19df-7b10-913b-7315a7051a62'));
    }

    public function testBackendControllerOnlyRendersBrandView(): void
    {
        $controller = new \App\Modules\Brand\Controllers\Backend\FMSBrandController();

        $this->assertTrue(method_exists($controller, 'index'));
        foreach (['data', 'update', 'presigned'] as $removedMethod) {
            $this->assertFalse(
                method_exists($controller, $removedMethod),
                'FMSBrandController tidak boleh memproses AJAX melalui method ' . $removedMethod . '.'
            );
        }
    }

    public function testBackendViewSubmitsToApiV1BrandOnly(): void
    {
        $viewPath = APPPATH . 'Modules/Brand/Views/backend/index.php';
        $this->assertFileExists($viewPath);

        $source = (string) file_get_contents($viewPath);

        $this->assertDoesNotMatchRegularExpression('/<\?=(?!php|xml)/', $source);
        $this->assertStringContainsString('DOMContentLoaded', $source);
        $this->assertStringContainsString('brandLogo', $source);
        $this->assertStringContainsString('brandLogoLight', $source);
        $this->assertStringContainsString('brandFavicon', $source);

        /* Satu kontrak API: view membaca & menyimpan lewat /api/v1/brand saja */
        $this->assertStringContainsString("site_url('api/v1/brand/')", $source);
        $this->assertStringContainsString('DATA_URL', $source);
        $this->assertStringContainsString("const UPDATE_URL = DATA_URL;", $source);
        $this->assertStringNotContainsString('brand/data', $source);
        $this->assertStringNotContainsString('brand/update', $source);
        $this->assertStringNotContainsString('$brandDataUrl', $source);
        $this->assertStringNotContainsString('$brandUpdateUrl', $source);
    }

    public function testHeadPartialAndFmsJsContainBrandNameMetadataContract(): void
    {
        $headFile = APPPATH . 'Views/_partials/head.php';
        $this->assertFileExists($headFile);
        $headContent = (string) file_get_contents($headFile);
        $this->assertStringContainsString('name="fms-brand-name"', $headContent);

        $fmsJsFile = ROOTPATH . 'public/assets/fms/js/fms.js';
        $this->assertFileExists($fmsJsFile);
        $jsContent = (string) file_get_contents($fmsJsFile);
        $this->assertStringContainsString('meta[name="fms-brand-name"]', $jsContent);
        $this->assertStringContainsString('FMS.escapeHtml =', $jsContent);
    }

    public function testThemeDefaultsToSystemAndPersistsManualLightChoice(): void
    {
        $headFile = APPPATH . 'Views/_partials/head.php';
        $headContent = (string) file_get_contents($headFile);

        $this->assertStringContainsString("window.localStorage.getItem('fmsthemepreference')", $headContent);
        $this->assertStringContainsString("window.localStorage.setItem('fmsthemepreference', normalized)", $headContent);
        $this->assertStringContainsString("return 'system';", $headContent);
        $this->assertStringContainsString("prefers-color-scheme: dark", $headContent);
        $this->assertStringContainsString("data-fms-theme-preference", $headContent);

        $switcherFile = ROOTPATH . 'public/assets/fms/js/custom-switcher.min.js';
        $switcherContent = (string) file_get_contents($switcherFile);
        $this->assertStringContainsString("FMSTheme.setPreference('light')", $switcherContent);
        $this->assertStringContainsString("FMSTheme.setPreference('dark')", $switcherContent);
    }

    public function testFmsJsProvidesBrandSplashBlockUiOnEveryAjaxCall(): void
    {
        $fmsJsFile = ROOTPATH . 'public/assets/fms/js/fms.js';
        $this->assertFileExists($fmsJsFile);
        $jsContent = (string) file_get_contents($fmsJsFile);

        /* API BlockUI seperti jQuery BlockUI */
        $this->assertStringContainsString('FMS.blockUI =', $jsContent);
        $this->assertStringContainsString('FMS.unblockUI =', $jsContent);
        $this->assertStringContainsString('fms-blockui-overlay', $jsContent);

        /* Logo brand di tengah seperti splash screen */
        $this->assertStringContainsString('meta[name="fms-logo"]', $jsContent);
        $this->assertStringContainsString('fms-blockui-logo', $jsContent);

        /* Setiap aksi FMS.ajax otomatis memunculkan BlockUI */
        $this->assertStringContainsString('FMS.blockUI()', $jsContent);
        $this->assertStringContainsString('FMS.unblockUI()', $jsContent);

        /* Sedang memproses memakai efek ketik dengan caret, tanpa dots dan lingkaran besar */
        $this->assertStringContainsString('fms-blockui-status-text', $jsContent);
        $this->assertStringContainsString('startBlockUITyping', $jsContent);
        $this->assertStringContainsString('fullText.slice(0, index)', $jsContent);
        $this->assertStringContainsString('if (blockUITypingTimer) return;', $jsContent);
        $this->assertStringContainsString("status.dataset.typingComplete === 'true'", $jsContent);
        $this->assertStringContainsString('status.textContent = fullText;', $jsContent);
        $this->assertStringContainsString('var startedAt = Date.now();', $jsContent);
        $this->assertStringContainsString('Date.now() - startedAt', $jsContent);
        $this->assertStringContainsString('Math.floor(elapsed / TYPE_INTERVAL_MS)', $jsContent);
        $this->assertStringContainsString("status.dataset.typingComplete = 'true';", $jsContent);
        $this->assertStringNotContainsString("phase = 'deleting';", $jsContent);
        $this->assertStringNotContainsString('scheduleNext(1600);', $jsContent);
        $this->assertStringContainsString('fmsBlockUICaret', $jsContent);
        $this->assertStringNotContainsString('fmsBlockUITextShimmer', $jsContent);
        $this->assertStringNotContainsString('fmsBlockUITextBreathe', $jsContent);
        $this->assertStringContainsString('.fms-blockui-status-text::after', $jsContent);
        $this->assertStringContainsString('width: 100%;', $jsContent);
        $this->assertStringContainsString('text-align: center;', $jsContent);
        $this->assertStringNotContainsString('width: 18ch;', $jsContent);
        $this->assertStringNotContainsString('justify-content: flex-start;', $jsContent);
        $this->assertStringNotContainsString('border-right: 1px solid var(--fms-blockui-primary);', $jsContent);
        $this->assertStringNotContainsString('fms-blockui-dots', $jsContent);
        $this->assertStringNotContainsString('.fms-blockui-ring::after', $jsContent);
        $this->assertSame(0, substr_count($jsContent, '<span aria-hidden="true"></span>'));
        $this->assertStringContainsString('fmsBlockUISpin', $jsContent);
        $this->assertStringContainsString('fmsBlockUIGlow', $jsContent);
        $this->assertStringContainsString('cursor: wait !important', $jsContent);

        /* BlockUI dipanggil tepat sekali sebelum persiapan dan pemuatan data */
        $this->assertSame(1, substr_count($jsContent, 'if (shouldBlockUI) FMS.blockUI();'));
        $this->assertStringContainsString("if (shouldBlockUI) FMS.blockUI();\n\n    var method = (options.method", $jsContent);
        $this->assertStringNotContainsString('function requestAfterBlockUIPaint()', $jsContent);
        $this->assertStringNotContainsString('waitForBlockUIPaint().then', $jsContent);
        $this->assertStringContainsString('var request = window.fetch(url, fetchOptions);', $jsContent);
        $this->assertStringContainsString('FMS.blockUI();', $jsContent);
        $this->assertStringContainsString("document.addEventListener('DOMContentLoaded', ensureBlockUI, { once: true });", $jsContent);
        $this->assertStringNotContainsString('void overlay.offsetWidth;', $jsContent);
        $this->assertStringNotContainsString('transition: opacity .18s ease', $jsContent);

        /* Loader tanpa kotak: glass penuh, tanpa border dan shadow panel berat */
        $this->assertStringContainsString('background: transparent;', $jsContent);
        $this->assertStringContainsString('backdrop-filter: blur(16px) saturate(135%)', $jsContent);
        $this->assertStringNotContainsString('box-shadow: 0 28px 80px', $jsContent);
        $this->assertStringNotContainsString('drop-shadow(', $jsContent);

        /* Force-unblock dipakai probe/recovery dan harus melepas tanpa delay */
        $this->assertStringContainsString('force === true ? 0 : Math.max(0, BLOCK_UI_MINIMUM_MS - elapsed)', $jsContent);
    }

    public function testBackendFooterRendersDynamicBrandNameInsteadOfStaticSignature(): void
    {
        $footerHtml = view('backend/_partials/footer', [
            'appBrand' => ['name' => 'Nama Brand dari Database'],
        ]);

        $this->assertStringContainsString('Nama Brand dari Database', $footerHtml);
        $this->assertStringNotContainsString('FMS Signature', $footerHtml);
    }

    public function testBrandApiAndRoutesOwnAjaxContract(): void
    {
        $apiControllerSource = (string) file_get_contents(APPPATH . 'Modules/Brand/Controllers/Api/FMSBrandApiController.php');
        $backendRoutesSource = (string) file_get_contents(APPPATH . 'Modules/Brand/Config/BackendRoutes.php');
        $apiRoutesSource = (string) file_get_contents(APPPATH . 'Modules/Brand/Config/ApiRoutes.php');

        $this->assertStringContainsString("'brand'", $apiRoutesSource);
        $this->assertStringNotContainsString("'brand/data'", $backendRoutesSource);
        $this->assertStringNotContainsString("'brand/update'", $backendRoutesSource);
        $this->assertStringNotContainsString("'brand/presigned'", $backendRoutesSource);
        $this->assertStringContainsString('respondOk(', $apiControllerSource);
        $this->assertStringContainsString('respondCreated(', $apiControllerSource);
        $this->assertStringContainsString('respondServerError(', $apiControllerSource);
        $this->assertStringNotContainsString('respondSuccess(200', $apiControllerSource);
        $this->assertStringNotContainsString('respondError(500', $apiControllerSource);
    }

    public function testFmsAjaxAddsStoredBearerTokenToApiRequests(): void
    {
        $source = (string) file_get_contents(ROOTPATH . 'public/assets/fms/js/fms.js');

        $this->assertStringContainsString("sessionStorage.getItem('fms_access_token')", $source);
        $this->assertStringContainsString('headers.Authorization', $source);
        $this->assertStringContainsString("sessionStorage.getItem('fms_token_type')", $source);
    }


    public function testBrandPublicAssetRoutesExistWithStableUrls(): void
    {
        $routesFile = APPPATH . 'Modules/Brand/Config/PublicRoutes.php';
        $this->assertFileExists($routesFile);
        $src = (string) file_get_contents($routesFile);
        $this->assertStringContainsString("'brand/logo'", $src);
        $this->assertStringContainsString("'brand/logo-light'", $src);
        $this->assertStringContainsString("'brand/favicon'", $src);
    }

    public function testBrandPublicControllerServesAssetWithoutPresignedExpiry(): void
    {
        $controllerFile = APPPATH . 'Modules/Brand/Controllers/Public/FMSBrandPublicController.php';
        $this->assertFileExists($controllerFile);
        $src = (string) file_get_contents($controllerFile);

        /* tidak boleh ada presigned / expires / signature di path ini */
        $this->assertStringNotContainsString('createPresignedDownloadUrl', $src);
        $this->assertStringNotContainsString('signedReadUrl', $src);
        $this->assertStringContainsString('readObject', $src);
        $this->assertStringContainsString('Cache-Control', $src);
    }

    public function testBrandIdentityServiceUsesStablePublicUrlsNotPresignedUrls(): void
    {
        $serviceFile = APPPATH . 'Modules/Brand/Services/FMSBrandIdentityService.php';
        $src = (string) file_get_contents($serviceFile);

        /* resolveImageUrl tidak boleh ada — diganti resolvePublicBrandUrl */
        $this->assertStringNotContainsString('signedReadUrl', $src);
        $this->assertStringContainsString('brand/logo', $src);
    }
}
