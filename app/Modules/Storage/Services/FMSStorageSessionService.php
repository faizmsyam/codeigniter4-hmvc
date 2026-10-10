<?php
namespace App\Modules\Storage\Services;

use App\Modules\Storage\Models\FMSStorageUserModel;
use CodeIgniter\HTTP\ResponseInterface;

final class FMSStorageSessionService
{
    public const COOKIE_NAME = 'fms_storage_session';
    private const TOKEN_TTL = 28800;

    public function create(int $userId, string $ipAddress = '', string $userAgent = ''): string
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $token = $selector . '.' . $validator;
        $now = date('Y-m-d H:i:s');
        $expires = date('Y-m-d H:i:s', time() + $this->ttl());

        db_connect()->table('storage_sessions')->insert([
            'selector' => $selector,
            'token_hash' => hash('sha256', $validator),
            'user_id' => $userId,
            'ip_hash' => $ipAddress !== '' ? hash('sha256', $ipAddress) : null,
            'user_agent_hash' => $userAgent !== '' ? hash('sha256', $userAgent) : null,
            'expires_at' => $expires,
            'last_seen_at' => $now,
            'created_at' => $now,
        ]);

        return $token;
    }

    public function resolve(string $token): ?array
    {
        [$selector, $validator] = $this->split($token);
        if ($selector === '' || $validator === '') return null;

        $row = db_connect()->table('storage_sessions')
            ->where('selector', $selector)
            ->where('revoked_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get(1)->getRowArray();

        if (! is_array($row) || ! hash_equals((string) $row['token_hash'], hash('sha256', $validator))) {
            return null;
        }

        db_connect()->table('storage_sessions')
            ->where('id', (int) $row['id'])
            ->update(['last_seen_at' => date('Y-m-d H:i:s')]);

        return (new FMSStorageUserModel())->find((int) $row['user_id']) ?: null;
    }

    public function revoke(string $token): void
    {
        [$selector] = $this->split($token);
        if ($selector === '') return;

        db_connect()->table('storage_sessions')
            ->where('selector', $selector)
            ->where('revoked_at', null)
            ->update(['revoked_at' => date('Y-m-d H:i:s')]);
    }

    public function attachCookie(ResponseInterface $response, string $token): ResponseInterface
    {
        return $response->setCookie([
            'name' => self::COOKIE_NAME,
            'value' => $token,
            'expire' => time() + $this->ttl(),
            'path' => '/',
            'secure' => $this->isSecureRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public function clearCookie(ResponseInterface $response): ResponseInterface
    {
        return $response->deleteCookie(self::COOKIE_NAME, '/', '', $this->isSecureRequest(), true, 'Lax');
    }

    private function ttl(): int
    {
        return max(300, (int) config(\App\Modules\Storage\Config\FMSStorage::class)->sessionTtl ?: self::TOKEN_TTL);
    }

    private function isSecureRequest(): bool
    {
        return function_exists('service') && service('request')->isSecure();
    }

    /** @return array{0:string,1:string} */
    private function split(string $token): array
    {
        $parts = explode('.', trim($token), 2);
        return [
            preg_match('/\A[a-f0-9]{24}\z/', $parts[0] ?? '') === 1 ? $parts[0] : '',
            preg_match('/\A[a-f0-9]{64}\z/', $parts[1] ?? '') === 1 ? $parts[1] : '',
        ];
    }
}
