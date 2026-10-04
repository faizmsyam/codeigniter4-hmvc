<?php

namespace Tests\Unit\Modules\Users;

use App\Modules\Users\Models\FMSUserModel;
use App\Modules\Users\Services\FMSUserService;
use App\Modules\Users\Validation\FMSUserValidation;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

final class FMSUserDomainTest extends CIUnitTestCase
{
    public function testModelUsesLogicalMasterTableAndExplicitAllowlist(): void
    {
        $userModel = new FMSUserModel();

        $this->assertSame('m_users', $userModel->tableName());
        $this->assertContains('username_normalized', $userModel->allowedFieldNames());
        $this->assertContains('session_version', $userModel->allowedFieldNames());
        $this->assertNotContains('id', $userModel->allowedFieldNames());
        $this->assertNotContains('uuid', $userModel->allowedFieldNames());
        $this->assertNotContains('created_by', $userModel->allowedFieldNames());
    }

    public function testCreatePreparationNormalizesIdentityHashesPasswordAndRejectsMassAssignment(): void
    {
        $preparedData = (new FMSUserService())->prepareCreateData([
            'username' => '  Alice.Example  ',
            'email' => '  ALICE@Example.COM ',
            'password' => 'Correct-Horse-Battery-Staple-2026',
            'full_name' => '  Alice Example ',
            'registration_source' => 'admin',
            'status' => 'active',
            'token_version' => 999,
            'created_by' => 999,
        ], 7);

        $this->assertSame('Alice.Example', $preparedData['username']);
        $this->assertSame('alice.example', $preparedData['username_normalized']);
        $this->assertSame('ALICE@Example.COM', $preparedData['email']);
        $this->assertSame('alice@example.com', $preparedData['email_normalized']);
        $this->assertSame('Alice Example', $preparedData['full_name']);
        $this->assertTrue(password_verify('Correct-Horse-Battery-Staple-2026', $preparedData['password_hash']));
        $this->assertSame(7, $preparedData['created_by']);
        $this->assertSame(7, $preparedData['updated_by']);
        $this->assertArrayNotHasKey('password', $preparedData);
        $this->assertArrayNotHasKey('token_version', $preparedData);
    }

    public function testEmailChangeClearsVerificationAndIncrementsVersions(): void
    {
        $preparedData = (new FMSUserService())->prepareUpdateData([
            'email' => 'new@example.com',
            'full_name' => 'Updated Name',
            'status' => 'inactive',
            'password_hash' => 'injected',
        ], [
            'email_normalized' => 'old@example.com',
            'token_version' => 3,
            'session_version' => 5,
        ], 9);

        $this->assertNull($preparedData['email_verified_at']);
        $this->assertSame(4, $preparedData['token_version']);
        $this->assertSame(6, $preparedData['session_version']);
        $this->assertSame(9, $preparedData['updated_by']);
        $this->assertArrayNotHasKey('password_hash', $preparedData);
    }

    public function testPasswordResetHashesPasswordAndRevokesExistingAuthenticationVersions(): void
    {
        $preparedData = (new FMSUserService())->preparePasswordResetData(
            'Another-Correct-Horse-Battery-Staple-2026',
            ['token_version' => 8, 'session_version' => 13],
            11,
        );

        $this->assertTrue(password_verify('Another-Correct-Horse-Battery-Staple-2026', $preparedData['password_hash']));
        $this->assertSame(9, $preparedData['token_version']);
        $this->assertSame(14, $preparedData['session_version']);
        $this->assertSame(1, $preparedData['must_change_password']);
        $this->assertSame(11, $preparedData['updated_by']);
    }

    public function testValidationRejectsInvalidUuidStatusSourceAndWeakPassword(): void
    {
        $validationErrors = FMSUserValidation::validateCreate([
            'username' => 'a',
            'email' => 'not-an-email',
            'password' => 'weak',
            'full_name' => '',
            'status' => 'root',
            'registration_source' => 'unknown',
        ]);

        $this->assertArrayHasKey('username', $validationErrors);
        $this->assertArrayHasKey('email', $validationErrors);
        $this->assertArrayHasKey('password', $validationErrors);
        $this->assertArrayHasKey('full_name', $validationErrors);
        $this->assertArrayHasKey('status', $validationErrors);
        $this->assertArrayHasKey('registration_source', $validationErrors);
        $this->assertFalse(FMSUserValidation::isValidUuid('not-a-uuid'));
        $this->assertTrue(FMSUserValidation::isValidUuid('0195e887-19df-7b10-913b-7315a7051a62'));
    }

    public function testPaginationAndGroupIdentifiersAreBoundedAndDeduplicated(): void
    {
        $userService = new FMSUserService();
        $pagination = $userService->normalizePagination(['page' => '3', 'per_page' => '500']);

        $this->assertSame(['page' => 3, 'per_page' => 100, 'offset' => 200], $pagination);
        $this->assertSame([2, 4, 8], $userService->normalizeGroupIdentifiers([4, '2', 4, 8]));
    }

    public function testInvalidGroupIdentifierIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new FMSUserService())->normalizeGroupIdentifiers([1, 0]);
    }
}
