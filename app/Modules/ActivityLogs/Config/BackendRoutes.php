<?php

use App\Modules\ActivityLogs\Controllers\Backend\FMSActivityLogsController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('activity-logs', [FMSActivityLogsController::class, 'index'], [
    'as' => 'admin.activity-logs.index',
]);
