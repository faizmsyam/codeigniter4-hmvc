<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

use App\Modules\UserGroups\Controllers\Backend\FMSUserGroupsBackendController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('user-groups', [FMSUserGroupsBackendController::class, 'index'], ['as' => 'fms.admin.user-groups']);
