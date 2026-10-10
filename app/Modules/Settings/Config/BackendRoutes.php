<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

use App\Modules\Settings\Controllers\Backend\FMSSettingsBackendController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('settings', [FMSSettingsBackendController::class, 'index'], ['as' => 'fms.admin.settings']);
