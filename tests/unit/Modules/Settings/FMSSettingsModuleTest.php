<?php

namespace Tests\Unit\Modules\Settings;

use App\Modules\Settings\Controllers\Api\FMSSettingsApiController;
use App\Modules\Settings\Controllers\Backend\FMSSettingsBackendController;
use App\Modules\Settings\Validation\FMSSettingsValidation;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @internal
 */
final class FMSSettingsModuleTest extends CIUnitTestCase
{
    private FMSSettingsValidation $validation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validation = new FMSSettingsValidation();
    }

    public function testAuthSettingsValidationNormalizesBooleansAndRanges(): void
    {
        $payload = [
            'public_registration_enabled'               => '1',
            'public_email_verification_required'        => true,
            'admin_created_email_verification_required' => 0,
            'verification_ttl_minutes'                  => '720',
            'resend_cooldown_seconds'                   => '60',
        ];

        $result = $this->validation->validateAuthSettings($payload);

        $this->assertSame(1, $result['public_registration_enabled']);
        $this->assertSame(1, $result['public_email_verification_required']);
        $this->assertSame(0, $result['admin_created_email_verification_required']);
        $this->assertSame(720, $result['verification_ttl_minutes']);
        $this->assertSame(60, $result['resend_cooldown_seconds']);
    }

    public function testAuthSettingsValidationRejectsInvalidTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validation->validateAuthSettings([
            'verification_ttl_minutes' => 2, // < 5 is invalid
        ]);
    }

    public function testApiKeyValidationGeneratesCorrectDefaults(): void
    {
        $payload = [
            'label'        => 'Test Gateway Integration',
            'environment'  => 'production',
            'scopes'       => ['users.read', 'brand.read'],
            'ip_allowlist' => "192.168.1.1\n10.0.0.1",
        ];

        $result = $this->validation->validateApiKey($payload, false);

        $this->assertSame('Test Gateway Integration', $result['label']);
        $this->assertSame('production', $result['environment']);
        $this->assertSame(json_encode(['users.read', 'brand.read']), $result['scopes_json']);
        $this->assertSame(json_encode(['192.168.1.1', '10.0.0.1']), $result['ip_allowlist_json']);
        $this->assertNull($result['expires_at']);
    }

    public function testApiKeyValidationRejectsEmptyLabelOnCreate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validation->validateApiKey([
            'label' => '',
        ], false);
    }

    public function testBasicAuthValidationNormalizesUsernameAndScopes(): void
    {
        $payload = [
            'username'    => 'Test_Client-01',
            'label'       => 'Test Client Label',
            'environment' => 'staging',
            'scopes'      => '*',
            'password'    => 'secret12345',
        ];

        $result = $this->validation->validateBasicAuthClient($payload, false);

        $this->assertSame('test_client-01', $result['username']);
        $this->assertSame('Test Client Label', $result['label']);
        $this->assertSame('staging', $result['environment']);
        $this->assertSame(json_encode(['*']), $result['scopes_json']);
        $this->assertSame('secret12345', $result['password']);
    }

    public function testBasicAuthValidationRejectsInvalidUsername(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validation->validateBasicAuthClient([
            'username' => 'invalid username with spaces!',
            'label'    => 'Invalid',
        ], false);
    }

    public function testBackendRouteFileRegistersSettingsIndex(): void
    {
        $backendRoutesPath = APPPATH . 'Modules/Settings/Config/BackendRoutes.php';
        $this->assertFileExists($backendRoutesPath);

        $content = (string) file_get_contents($backendRoutesPath);
        $this->assertStringContainsString("get('settings'", $content);
        $this->assertStringContainsString(FMSSettingsBackendController::class, $content);
    }

    public function testApiRouteFileRegistersAllRequiredEndpoints(): void
    {
        $apiRoutesPath = APPPATH . 'Modules/Settings/Config/ApiRoutes.php';
        $this->assertFileExists($apiRoutesPath);

        $content = (string) file_get_contents($apiRoutesPath);
        $this->assertStringContainsString("group('settings'", $content);
        $this->assertStringContainsString('overview', $content);
        $this->assertStringContainsString('auth', $content);
        $this->assertStringContainsString('api-keys', $content);
        $this->assertStringContainsString('basic-auth', $content);
        $this->assertStringContainsString(FMSSettingsApiController::class, $content);
    }
}
