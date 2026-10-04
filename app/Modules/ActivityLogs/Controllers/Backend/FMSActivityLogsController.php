<?php

namespace App\Modules\ActivityLogs\Controllers\Backend;

use App\Core\FMSBackendController;

final class FMSActivityLogsController extends FMSBackendController
{
    public function index(): string
    {
        $this->requireBackendPermission('activity_logs.read');
        $this->fmsMeta(['title' => 'Activity Logs']);

        return $this->fmsLayout('index');
    }
}
