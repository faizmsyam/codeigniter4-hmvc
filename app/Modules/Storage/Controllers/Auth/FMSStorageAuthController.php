<?php
namespace App\Modules\Storage\Controllers\Auth;

use App\Core\FMSController;
use App\Modules\Storage\Models\FMSStorageUserModel;
use App\Modules\Storage\Services\FMSStorageObjectService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class FMSStorageAuthController extends FMSController
{
    public function login(): string|ResponseInterface
    {
        if (session()->get('fms_storage_authenticated') === true) {
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

        session()->regenerate(true);
        session()->set([
            'fms_storage_authenticated' => true,
            'fms_storage_user_id' => (int) $user['id'],
            'fms_storage_username' => (string) $user['username'],
            'fms_storage_full_name' => (string) ($user['full_name'] ?? $user['username']),
            'fms_storage_super_admin' => (int) ($user['is_super_admin'] ?? 0) === 1,
            'fms_storage_logged_in_at' => time(),
        ]);

        $model->update((int) $user['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip_hash' => hash('sha256', (string) $this->request->getIPAddress()),
        ]);

        $intended = (string) session()->get('fms_storage_intended_url');
        session()->remove('fms_storage_intended_url');

        return redirect()->to($intended !== '' ? $intended : site_url('fms-storage'));
    }

    public function logout(): ResponseInterface
    {
        foreach (array_keys(session()->get()) as $key) {
            if (str_starts_with((string) $key, 'fms_storage_')) {
                session()->remove($key);
            }
        }

        session()->regenerate(true);
        return redirect()->to(site_url('fms-storage/login'))->with('storage_success', 'Anda telah keluar.');
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
