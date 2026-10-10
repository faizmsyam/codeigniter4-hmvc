<?php

use App\Modules\LogMonitor\Controllers\Api\FMSLogMonitorApiController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('log-monitor', ['filter' => 'fms-api-authentication'], static function (RouteCollection $routes): void {
    $routes->get('files', [FMSLogMonitorApiController::class, 'files'], ['as' => 'api.v1.log-monitor.files']);
    $routes->get('read', [FMSLogMonitorApiController::class, 'read'], ['as' => 'api.v1.log-monitor.read']);
    $routes->get('stats', [FMSLogMonitorApiController::class, 'stats'], ['as' => 'api.v1.log-monitor.stats']);
    $routes->get('download', [FMSLogMonitorApiController::class, 'download'], ['as' => 'api.v1.log-monitor.download']);
});
