<?php

use App\Modules\Uploads\Controllers\Backend\FMSUploadsController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('uploads', [FMSUploadsController::class, 'index'], ['as' => 'fms.admin.uploads']);
