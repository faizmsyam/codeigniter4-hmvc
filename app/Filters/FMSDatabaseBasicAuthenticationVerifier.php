<?php

namespace App\Filters;

use App\Modules\Authentication\Repositories\FMSDatabaseBasicAuthClientRepository;
use App\Modules\Authentication\Services\FMSBasicAuthService;
use Throwable;

final class FMSDatabaseBasicAuthenticationVerifier implements FMSAuthenticationVerifierInterface
{
    private FMSBasicAuthService $basicAuthService;

    public function __construct(?FMSBasicAuthService $basicAuthService = null)
    {
        $this->basicAuthService = $basicAuthService ?? new FMSBasicAuthService(
            new FMSDatabaseBasicAuthClientRepository()
        );
    }

    /**
     * @param string $presentedCredential Format: {username}\0{password}
     * @return array{valid: bool, reason: string|null, subject: array<string, mixed>|null}
     */
    public function verify(string $presentedCredential): array
    {
        $parts = explode("\0", $presentedCredential, 2);
        if (count($parts) !== 2) {
            return ['valid' => false, 'reason' => 'BASIC_AUTH_MALFORMED', 'subject' => null];
        }

        [$username, $password] = $parts;
        $authHeader = 'Basic ' . base64_encode($username . ':' . $password);

        try {
            $now = date('Y-m-d H:i:s');
            $result = $this->basicAuthService->verifyCredentials($authHeader, [], ENVIRONMENT, $now);

            if ($result['status'] === FMSBasicAuthService::STATUS_AUTHENTICATED && is_array($result['record'])) {
                $record = $result['record'];

                return [
                    'valid'   => true,
                    'reason'  => null,
                    'subject' => [
                        'auth_type'   => 'basic_auth',
                        'username'    => (string) ($record['username'] ?? $username),
                        'label'       => (string) ($record['label'] ?? ''),
                        'scopes'      => json_decode((string) ($record['scopes_json'] ?? '[]'), true) ?: [],
                        'environment' => (string) ($record['environment'] ?? ''),
                    ],
                ];
            }

            return ['valid' => false, 'reason' => 'BASIC_AUTH_' . strtoupper($result['status']), 'subject' => null];
        } catch (Throwable $e) {
            log_message('error', 'FMSDatabaseBasicAuthenticationVerifier exception: {msg}', ['msg' => $e->getMessage()]);

            return ['valid' => false, 'reason' => 'BASIC_AUTH_EXCEPTION', 'subject' => null];
        }
    }
}
