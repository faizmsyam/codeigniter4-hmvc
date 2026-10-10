<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Modules\Settings\Services;

use App\Modules\Authentication\Models\FMSApiKeyModel;
use App\Modules\Authentication\Models\FMSBasicAuthClientModel;
use App\Modules\Settings\Models\FMSAuthSettingModel;
use App\Modules\Settings\Validation\FMSSettingsValidation;

final class FMSSettingsService
{
    public function __construct(
        private readonly FMSAuthSettingModel $authSettingModel = new FMSAuthSettingModel(),
        private readonly FMSApiKeyModel $apiKeyModel = new FMSApiKeyModel(),
        private readonly FMSBasicAuthClientModel $basicAuthModel = new FMSBasicAuthClientModel(),
        private readonly FMSSettingsValidation $validation = new FMSSettingsValidation(),
    ) {
    }

    public function getAuthSettings(): array
    {
        return $this->authSettingModel->currentSettings();
    }

    public function updateAuthSettings(array $payload, int $actorId): array
    {
        $validated = $this->validation->validateAuthSettings($payload);

        $current = $this->authSettingModel->currentSettings();
        $currentId = (int) ($current['id'] ?? 1);
        $newVersion = ((int) ($current['version'] ?? 1)) + 1;

        $updateData = array_merge($validated, [
            'version'    => $newVersion,
            'updated_by' => $actorId > 0 ? $actorId : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->authSettingModel->update($currentId, $updateData);

        return $this->authSettingModel->find($currentId);
    }

    public function getSystemOverview(): array
    {
        $db = db_connect();

        $totalApiKeys   = $this->apiKeyModel->countAllResults();
        $activeApiKeys  = $this->apiKeyModel->where('revoked_at IS NULL')->countAllResults();
        $revokedApiKeys = $this->apiKeyModel->where('revoked_at IS NOT NULL')->countAllResults();

        $totalBasicClients   = $this->basicAuthModel->countAllResults();
        $activeBasicClients  = $this->basicAuthModel->where('revoked_at IS NULL')->countAllResults();
        $lockedBasicClients  = $this->basicAuthModel->where('locked_until IS NOT NULL')->where('locked_until >', date('Y-m-d H:i:s'))->countAllResults();
        $revokedBasicClients = $this->basicAuthModel->where('revoked_at IS NOT NULL')->countAllResults();

        $authSettings = $this->authSettingModel->currentSettings();

        $pepperConfigured = (bool) (getenv('fms.api_key_pepper') ?: getenv('API_KEY_PEPPER'));

        return [
            'environment' => ENVIRONMENT,
            'api_keys' => [
                'total'   => $totalApiKeys,
                'active'  => $activeApiKeys,
                'revoked' => $revokedApiKeys,
                'pepper_configured' => $pepperConfigured,
            ],
            'basic_auth' => [
                'total'   => $totalBasicClients,
                'active'  => $activeBasicClients,
                'locked'  => $lockedBasicClients,
                'revoked' => $revokedBasicClients,
            ],
            'auth_settings' => [
                'public_registration_enabled'               => (int) ($authSettings['public_registration_enabled'] ?? 0),
                'public_email_verification_required'        => (int) ($authSettings['public_email_verification_required'] ?? 1),
                'admin_created_email_verification_required' => (int) ($authSettings['admin_created_email_verification_required'] ?? 0),
                'admin_must_change_password'                => (int) ($authSettings['admin_must_change_password'] ?? 0),
                'verification_ttl_minutes'                  => (int) ($authSettings['verification_ttl_minutes'] ?? 1440),
                'resend_cooldown_seconds'                   => (int) ($authSettings['resend_cooldown_seconds'] ?? 120),
                'login_rate_limit_enabled'                 => (int) ($authSettings['login_rate_limit_enabled'] ?? 1),
                'version'                                   => (int) ($authSettings['version'] ?? 1),
                'updated_at'                                => $authSettings['updated_at'] ?? null,
            ],
        ];
    }
}
