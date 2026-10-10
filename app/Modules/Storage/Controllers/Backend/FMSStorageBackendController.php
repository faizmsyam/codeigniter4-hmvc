<?php
namespace App\Modules\Storage\Controllers\Backend;

use App\Core\FMSController;

final class FMSStorageBackendController extends FMSController
{
    public function index(): string
    {
        $this->fmsMeta(['title' => 'FMS Storage']);
        return $this->fmsLayout('index', ['storageUser' => session()->get()]);
    }

    public function settings(): string
    {
        $this->fmsMeta(['title' => 'FMS Storage Settings']);
        return $this->fmsLayout('settings', ['storageUser' => session()->get()]);
    }
}
