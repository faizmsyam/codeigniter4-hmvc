<?php
namespace App\Modules\Storage\Controllers\Backend;

use App\Core\FMSController;
use App\Modules\Storage\Services\FMSStorageSessionService;

final class FMSStorageBackendController extends FMSController
{
    public function index(): string
    {
        $user = (new FMSStorageSessionService())->resolve((string) $this->request->getCookie(FMSStorageSessionService::COOKIE_NAME));
        $this->fmsMeta(['title' => 'FMS Storage']);
        return $this->fmsLayout('index', ['storageUser' => $user]);
    }

    public function settings(): string
    {
        $user = (new FMSStorageSessionService())->resolve((string) $this->request->getCookie(FMSStorageSessionService::COOKIE_NAME));
        $this->fmsMeta(['title' => 'FMS Storage Settings']);
        return $this->fmsLayout('settings', ['storageUser' => $user]);
    }
}
