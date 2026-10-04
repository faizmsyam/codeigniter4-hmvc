<?php

namespace App\Filters;

use App\Modules\Authentication\Repositories\FMSDatabaseApiKeyRepository;
use App\Modules\Authentication\Services\FMSApiKeyService;
use Throwable;

final class FMSDatabaseApiKeyVerifier implements FMSAuthenticationVerifierInterface
{
    private FMSApiKeyService $apiKeyService;

    public function __construct(?FMSApiKeyService $apiKeyService = null)
    {
        if ($apiKeyService !== null) {
            $this->apiKeyService = $apiKeyService;
        } else {
            $pepper = (string) (getenv('fms.api_key_pepper') ?: getenv('API_KEY_PEPPER') ?: 'fms-starter-secret-pepper-32bytes-key');
            $this->apiKeyService = new FMSApiKeyService(new FMSDatabaseApiKeyRepository(), $pepper);
        }
    }

    /**
     * @param string $presentedCredential Format: {key_identifier}.{key_secret}
     * @return array{valid: bool, reason: string|null, subject: array<string, mixed>|null}
     */
    public function verify(string $presentedCredential): array
    {
        $parts = explode('.', $presentedCredential, 2);
        if (count($parts) !== 2) {
            return ['valid' => false, 'reason' => 'API_KEY_MALFORMED', 'subject' => null];
        }

        [$keyIdentifier, $keySecret] = $parts;
        $presentedKey = 'fms_' . $keyIdentifier . '_' . $keySecret;

        try {
            $now = date('Y-m-d H:i:s');
            $result = $this->apiKeyService->verifyKey($presentedKey, [], ENVIRONMENT, $now);

            if ($result['status'] === FMSApiKeyService::STATUS_VERIFIED && is_array($result['record'])) {
                $record = $result['record'];

                return [
                    'valid'   => true,
                    'reason'  => null,
                    'subject' => [
                        'auth_type'   => 'api_key',
                        'key_id'      => (string) ($record['key_id'] ?? $keyIdentifier),
                        'label'       => (string) ($record['label'] ?? ''),
                        'scopes'      => json_decode((string) ($record['scopes_json'] ?? '[]'), true) ?: [],
                        'environment' => (string) ($record['environment'] ?? ''),
                    ],
                ];
            }

            return ['valid' => false, 'reason' => 'API_KEY_' . strtoupper($result['status']), 'subject' => null];
        } catch (Throwable $e) {
            log_message('error', 'FMSDatabaseApiKeyVerifier exception: {msg}', ['msg' => $e->getMessage()]);

            return ['valid' => false, 'reason' => 'API_KEY_EXCEPTION', 'subject' => null];
        }
    }
}
