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
        $data['canManageUsers'] = (new FMSStorageAuthorizationService())->allows($data['storageUser'] ?? null, FMSStorageAuthorizationService::USERS);
        $data['content'] = view('App\\Modules\\Storage\\Views\\backend\\'.$view, $data);
        $data['fmsLinks'] = $this->getFmsLinks();
        $data['fmsScripts'] = $this->getFmsScripts();
        $data['fmsBottomScripts'] = $this->getFmsBottomScripts();

        return view('App\\Modules\\Storage\\Views\\layout', $data);
    }

    public function index(): string
    {
        $user = (new FMSStorageSessionService())->resolve((string) $this->request->getCookie(FMSStorageSessionService::COOKIE_NAME));
        $this->fmsMeta(['title' => 'FMS Storage']);
        return $this->storageLayout('index', ['storageUser' => $user, 'storagePage' => 'browser']);
    }

    public function settings(): string
    {
        $user = (new FMSStorageSessionService())->resolve((string) $this->request->getCookie(FMSStorageSessionService::COOKIE_NAME));
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
