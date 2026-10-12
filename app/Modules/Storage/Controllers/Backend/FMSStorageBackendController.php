<?php
namespace App\Modules\Storage\Controllers\Backend;

use App\Core\FMSController;
use App\Modules\Storage\Services\FMSStorageAuthorizationService;
use App\Modules\Storage\Services\FMSStorageSessionService;
use CodeIgniter\HTTP\RedirectResponse;

final class FMSStorageBackendController extends FMSController
{
    private function storageLayout(string $view, array $data = []): string
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
        $auth = new FMSStorageAuthorizationService();
        $storageUser = $data['storageUser'] ?? null;
        $data['canRead'] = $auth->allows($storageUser, FMSStorageAuthorizationService::READ);
        $data['canManageSettings'] = $auth->allows($storageUser, FMSStorageAuthorizationService::SETTINGS);
        $data['canManageUsers'] = $auth->allows($storageUser, FMSStorageAuthorizationService::USERS);
        /* Kirim daftar permission user ke JS agar tombol aksi di Object Browser di-gate. */
        $data['userPermissions'] = array_filter([
            FMSStorageAuthorizationService::READ      => $auth->allows($storageUser, FMSStorageAuthorizationService::READ),
            FMSStorageAuthorizationService::UPLOAD    => $auth->allows($storageUser, FMSStorageAuthorizationService::UPLOAD),
            FMSStorageAuthorizationService::EDIT      => $auth->allows($storageUser, FMSStorageAuthorizationService::EDIT),
            FMSStorageAuthorizationService::DELETE    => $auth->allows($storageUser, FMSStorageAuthorizationService::DELETE),
            FMSStorageAuthorizationService::SHARE     => $auth->allows($storageUser, FMSStorageAuthorizationService::SHARE),
        ]);
        $data['content'] = view('App\\Modules\\Storage\\Views\\backend\\'.$view, $data);
        $data['fmsLinks'] = $this->getFmsLinks();
        $data['fmsScripts'] = $this->getFmsScripts();
        $data['fmsBottomScripts'] = $this->getFmsBottomScripts();

        return view('App\\Modules\\Storage\\Views\\layout', $data);
    }

    public function index(): string|RedirectResponse
    {
        $user = (new FMSStorageSessionService())->resolve((string) $this->request->getCookie(FMSStorageSessionService::COOKIE_NAME));
        if (!(new FMSStorageAuthorizationService())->allows($user, FMSStorageAuthorizationService::READ)) {
            $target = (new FMSStorageAuthorizationService())->allows($user, FMSStorageAuthorizationService::SETTINGS)
                ? site_url('fms-storage/settings')
                : site_url('fms-storage/logout');
            return redirect()->to($target);
        }
        $this->fmsMeta(['title' => 'FMS Storage']);
        return $this->storageLayout('index', ['storageUser' => $user, 'storagePage' => 'browser']);
    }

    public function settings(): string|RedirectResponse
    {
        $user = (new FMSStorageSessionService())->resolve((string) $this->request->getCookie(FMSStorageSessionService::COOKIE_NAME));
        if (!(new FMSStorageAuthorizationService())->allows($user, FMSStorageAuthorizationService::SETTINGS)) {
            $target = (new FMSStorageAuthorizationService())->allows($user, FMSStorageAuthorizationService::READ)
                ? site_url('fms-storage')
                : site_url('fms-storage/logout');
            return redirect()->to($target);
        }
        $this->fmsMeta(['title' => 'FMS Storage Settings']);
        return $this->storageLayout('settings', ['storageUser' => $user, 'storagePage' => 'settings']);
    }

    public function users(): string|RedirectResponse
    {
        $user = (new FMSStorageSessionService())->resolve((string) $this->request->getCookie(FMSStorageSessionService::COOKIE_NAME));
        if (!(new FMSStorageAuthorizationService())->allows($user, FMSStorageAuthorizationService::USERS)) {
            return redirect()->to(site_url('fms-storage'));
        }
        $this->fmsMeta(['title' => 'FMS Storage Users']);
        return $this->storageLayout('users', ['storageUser' => $user, 'storagePage' => 'users']);
    }
}
