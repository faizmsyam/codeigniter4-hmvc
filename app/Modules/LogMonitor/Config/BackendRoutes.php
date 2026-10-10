<?php

use App\Modules\LogMonitor\Controllers\Backend\FMSLogMonitorController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('log-monitor', [FMSLogMonitorController::class, 'index'], [
    'as' => 'admin.log-monitor.index',
]);
