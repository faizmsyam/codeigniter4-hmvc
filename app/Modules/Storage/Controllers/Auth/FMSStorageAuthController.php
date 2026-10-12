<?php
namespace App\Modules\Storage\Controllers\Auth;

use App\Core\FMSController;
use App\Modules\Storage\Models\FMSStorageUserModel;
use App\Modules\Storage\Services\FMSStorageObjectService;
use App\Modules\Storage\Services\FMSStorageSessionService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class FMSStorageAuthController extends FMSController
{
    private function authLayout(string $view, array $data = []): string
    {
        $this->fmsLink($this->assetBasePath.'/libs/bootstrap/css/bootstrap.min.css');
        $this->fmsLink($this->assetBasePath.'/css/styles.css');
        $this->fmsLink($this->assetBasePath.'/css/icons.css');
        $this->fmsLink($this->assetBasePath.'/css/phosphor-duotone.css');
        $this->fmsLink($this->assetBasePath.'/css/storage.css');
        $this->fmsScript($this->assetBasePath.'/libs/bootstrap/js/bootstrap.bundle.min.js');
        $this->fmsScript($this->assetBasePath.'/js/fms.js');
        $this->fmsBottomScript($this->assetBasePath.'/js/storage.js');

        $data['appBrand'] = $this->appBrand;
        $data['meta'] = $this->getMeta();
        $data['content'] = view('App\\Modules\\Storage\\Views\\auth\\'.$view, $data);
        $data['fmsLinks'] = $this->getFmsLinks();
        $data['fmsScripts'] = $this->getFmsScripts();
        $data['fmsBottomScripts'] = $this->getFmsBottomScripts();

        return view('App\\Modules\\Storage\\Views\\auth\\layout', $data);
    }

    public function login(): string|ResponseInterface
    {
        $token = (string) $this->request->getCookie(FMSStorageSessionService::COOKIE_NAME);
        if ((new FMSStorageSessionService())->resolve($token) !== null) {
            return redirect()->to(site_url('fms-storage'));
        }

        $next = (string) $this->request->getGet('next');
        $this->fmsMeta(['title' => 'FMS Storage Login']);
        return $this->authLayout('login', [
            'next' => $next,
            'error' => (string) $this->request->getGet('error'),
            'success' => (string) $this->request->getGet('success'),
        ]);
    }

    public function authenticate(): ResponseInterface
    {
        $model = new FMSStorageUserModel();
        $user = $model->findByIdentifier(trim((string) $this->request->getPost('identifier')));
        $password = (string) $this->request->getPost('password');

        if (! is_array($user) || (string) ($user['status'] ?? '') !== 'active' || password_get_info((string) ($user['password_hash'] ?? ''))['algo'] === 0 || ! password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            $next = (string) $this->request->getPost('next');
            $suffix = $next !== '' ? '?next=' . rawurlencode($next) : '';
            return redirect()->to(site_url('fms-storage/login') . $suffix)->with('storage_error', 'Username atau password salah.');
        }

        $model->update((int) $user['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip_hash' => hash('sha256', (string) $this->request->getIPAddress()),
        ]);

        $sessionService = new FMSStorageSessionService();
        $token = $sessionService->create(
            (int) $user['id'],
            (string) $this->request->getIPAddress(),
            (string) $this->request->getUserAgent()->getAgentString(),
        );

        $next = (string) $this->request->getPost('next');
        $intended = site_url('fms-storage');
        if ($next !== '') {
            $candidate = filter_var($next, FILTER_SANITIZE_URL);
            $candidatePath = parse_url($candidate, PHP_URL_PATH);
            $candidateHost = parse_url($candidate, PHP_URL_HOST);
            $siteHost = parse_url(site_url('/'), PHP_URL_HOST);
            if ($candidate !== false && $candidateHost !== false && $candidateHost === $siteHost && is_string($candidatePath) && str_starts_with($candidatePath, '/fms-storage')) {
                $intended = $candidate;
            } elseif ($candidate !== false && str_starts_with((string) $candidate, '/fms-storage')) {
                $intended = site_url(ltrim((string) $candidate, '/'));
            }
        }
        return $sessionService->attachCookie(
            redirect()->to($intended)->with('storage_success', 'Selamat datang, ' . esc($user['full_name'] ?? $user['username'])),
            $token
        );
    }

    public function logout(): ResponseInterface
    {
        $sessionService = new FMSStorageSessionService();
        $sessionService->revoke((string) $this->request->getCookie(FMSStorageSessionService::COOKIE_NAME));
        return $sessionService->clearCookie(
            redirect()->to(site_url('fms-storage/login'))->with('storage_success', 'Anda telah keluar.')
        );
    }

    /**
     * Token share tidak pernah ditulis utuh ke response/log.
     */
    private function maskToken(string $token): string
    {
        return $token === '' ? '' : substr($token, 0, 4) . str_repeat('*', 8);
    }

    private function bucketName(): string
    {
        $config = config(\App\Config\FMSUploads::class);
        foreach (['bucketName', 'storageBucket', 'bucket', 'objectBucket'] as $prop) {
            if (isset($config->{$prop}) && is_string($config->{$prop}) && $config->{$prop} !== '') {
                return $config->{$prop};
            }
        }

        return 'fms-storage';
    }

    /**
     * Error response bergaya XML S3 (Code/Message/Resource/RequestId/HostId)
     * agar share link expired / not found punya bentuk yang sama dengan S3.
     *
     * @param array<string, string> $extra elemen tambahan (Expires, Key, dst)
     */
    private function s3Error(int $status, string $code, string $message, array $extra = []): ResponseInterface
    {
        $requestId = strtoupper(bin2hex(random_bytes(8)));
        $hostId    = base64_encode(hash('sha256', $requestId . '|' . microtime(true), true));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<Error>';
        $xml .= '<Code>' . htmlspecialchars($code, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</Code>';
        $xml .= '<Message>' . htmlspecialchars($message, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</Message>';

        foreach ($extra as $tag => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $xml .= '<' . $tag . '>' . htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</' . $tag . '>';
        }

        $xml .= '<RequestId>' . $requestId . '</RequestId>';
        $xml .= '<HostId>' . $hostId . '</HostId>';
        $xml .= '</Error>';

        return $this->response
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'application/xml; charset=utf-8')
            ->setHeader('x-amz-request-id', $requestId)
            ->setHeader('x-amz-id-2', $hostId)
            ->setBody($xml);
    }

    public function share(string $token): ResponseInterface
    {
        $resource = '/fms-storage/share/' . $this->maskToken($token);
        $bucket   = $this->bucketName();

        if ($token === '' || preg_match('/^[a-f0-9]{32,128}$/i', $token) !== 1) {
            return $this->s3Error(400, 'InvalidToken', 'The provided token is malformed or otherwise invalid.', [
                'Resource' => $resource,
                'BucketName' => $bucket,
            ]);
        }

        $row = db_connect()->table('storage_shares')
            ->where('token_hash', hash('sha256', $token))
            ->get(1)->getRowArray();

        if (! is_array($row)) {
            return $this->s3Error(404, 'NoSuchKey', 'The specified key does not exist.', [
                'Resource' => $resource,
                'BucketName' => $bucket,
                'Key' => 'share/' . $this->maskToken($token),
            ]);
        }

        $objectKey = (string) ($row['object_key'] ?? '');

        if (! empty($row['revoked_at'])) {
            return $this->s3Error(403, 'AccessDenied', 'The share link has been revoked.', [
                'Resource' => '/' . $bucket . '/' . $objectKey,
                'BucketName' => $bucket,
                'Key' => $objectKey,
            ]);
        }

        $expiresAt = strtotime((string) ($row['expires_at'] ?? ''));
        if ($expiresAt !== false && $expiresAt <= time()) {
            return $this->s3Error(403, 'AccessDenied', 'Request has expired', [
                'X-Amz-Expires' => (string) max(0, $expiresAt - strtotime((string) ($row['created_at'] ?? ''))),
                'Expires' => gmdate('Y-m-d\TH:i:s\Z', $expiresAt),
                'ServerTime' => gmdate('Y-m-d\TH:i:s\Z'),
                'Resource' => '/' . $bucket . '/' . $objectKey,
                'BucketName' => $bucket,
                'Key' => $objectKey,
            ]);
        }

        try {
            $object = (new FMSStorageObjectService())->download($objectKey);
        } catch (Throwable) {
            return $this->s3Error(404, 'NoSuchKey', 'The specified key does not exist.', [
                'Resource' => '/' . $bucket . '/' . $objectKey,
                'BucketName' => $bucket,
                'Key' => $objectKey,
            ]);
        }

        $requestId = strtoupper(bin2hex(random_bytes(8)));

        return $this->response
            ->setStatusCode(200)
            ->setContentType($object['content_type'])
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($objectKey) . '"')
            ->setHeader('x-amz-request-id', $requestId)
            ->setHeader('x-amz-id-2', base64_encode(hash('sha256', $requestId, true)))
            ->setHeader('x-amz-meta-key', rawurlencode($objectKey))
            ->setBody($object['body']);
    }
}
