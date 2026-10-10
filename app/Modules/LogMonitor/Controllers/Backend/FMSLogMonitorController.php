<?php

namespace App\Modules\LogMonitor\Controllers\Backend;

use App\Core\FMSBackendController;

final class FMSLogMonitorController extends FMSBackendController
{
    public function index(): string
    {
        $this->requireBackendPermission('log_monitor.read');
        $this->fmsMeta(['title' => 'Log Monitor']);

        return $this->fmsLayout('index');
    }
}
