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
    public function login(): string|ResponseInterface
    {
        $token = (string) $this->request->getCookie(FMSStorageSessionService::COOKIE_NAME);
        if ((new FMSStorageSessionService())->resolve($token) !== null) {
            return redirect()->to(site_url('fms-storage'));
        }

        $this->fmsMeta(['title' => 'FMS Storage Login']);
        return $this->fmsLayout('login');
    }

    public function authenticate(): ResponseInterface
    {
        $model = new FMSStorageUserModel();
        $user = $model->findByIdentifier(trim((string) $this->request->getPost('identifier')));
        $password = (string) $this->request->getPost('password');

        if (! is_array($user) || ! password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            return redirect()->back()->withInput()->with('storage_error', 'Username atau password salah.');
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

        $next = (string) $this->request->getGet('next');
        $intended = filter_var($next, FILTER_SANITIZE_URL) ?: site_url('fms-storage');        
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

    public function share(string $token): ResponseInterface
    {
        $row = db_connect()->table('storage_shares')
            ->where('token_hash', hash('sha256', $token))
            ->where('revoked_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get(1)->getRowArray();

        if (! is_array($row)) {
            return $this->response->setStatusCode(404)->setBody('Share link expired or not found.');
        }

        try {
            $object = (new FMSStorageObjectService())->readLocal((string) $row['object_key']);
            return $this->response
                ->setStatusCode(200)
                ->setContentType($object['content_type'])
                ->setHeader('Content-Disposition', 'inline; filename="' . basename((string) $row['object_key']) . '"')
                ->setBody($object['body']);
        } catch (Throwable) {
            return $this->response->setStatusCode(404)->setBody('Object not found.');
        }
    }
}
