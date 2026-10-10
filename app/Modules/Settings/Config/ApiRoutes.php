<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

use App\Filters\FMSApiAuthenticationFilter;
use App\Modules\Settings\Controllers\Api\FMSSettingsApiController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$apiFilterAlias = 'fms-api-authentication';
$registeredFilters = config(Config\Filters::class)->aliases;
$settingsApiFilter = array_key_exists($apiFilterAlias, $registeredFilters)
    ? $apiFilterAlias
    : FMSApiAuthenticationFilter::class;

$routes->group('settings', ['filter' => $settingsApiFilter], static function (RouteCollection $routes): void {
    $routes->get('overview', [FMSSettingsApiController::class, 'overview'], ['as' => 'fms.api.v1.settings.overview']);

    // Auth Settings
    $routes->get('auth', [FMSSettingsApiController::class, 'getAuthSettings'], ['as' => 'fms.api.v1.settings.auth.get']);
    $routes->put('auth', [FMSSettingsApiController::class, 'updateAuthSettings'], ['as' => 'fms.api.v1.settings.auth.update']);
    $routes->post('auth', [FMSSettingsApiController::class, 'updateAuthSettings'], ['as' => 'fms.api.v1.settings.auth.post']);

    // Email / SMTP Settings
    $routes->get('email', [FMSSettingsApiController::class, 'getEmailSettings'], ['as' => 'fms.api.v1.settings.email.get']);
    $routes->put('email', [FMSSettingsApiController::class, 'updateEmailSettings'], ['as' => 'fms.api.v1.settings.email.update']);
    $routes->post('email/test', [FMSSettingsApiController::class, 'testEmailSettings'], ['as' => 'fms.api.v1.settings.email.test']);

    // API Keys
    $routes->get('api-keys', [FMSSettingsApiController::class, 'listApiKeys'], ['as' => 'fms.api.v1.settings.api-keys.index']);
    $routes->post('api-keys', [FMSSettingsApiController::class, 'createApiKey'], ['as' => 'fms.api.v1.settings.api-keys.create']);
    $routes->get('api-keys/(:segment)', [FMSSettingsApiController::class, 'showApiKey'], ['as' => 'fms.api.v1.settings.api-keys.show']);
    $routes->put('api-keys/(:segment)', [FMSSettingsApiController::class, 'updateApiKey'], ['as' => 'fms.api.v1.settings.api-keys.update']);
    $routes->patch('api-keys/(:segment)', [FMSSettingsApiController::class, 'updateApiKey'], ['as' => 'fms.api.v1.settings.api-keys.patch']);
    $routes->post('api-keys/(:segment)/revoke', [FMSSettingsApiController::class, 'revokeApiKey'], ['as' => 'fms.api.v1.settings.api-keys.revoke']);
    $routes->post('api-keys/(:segment)/activate', [FMSSettingsApiController::class, 'activateApiKey'], ['as' => 'fms.api.v1.settings.api-keys.activate']);
    $routes->post('api-keys/(:segment)/roll', [FMSSettingsApiController::class, 'rollApiKey'], ['as' => 'fms.api.v1.settings.api-keys.roll']);
    $routes->delete('api-keys/(:segment)', [FMSSettingsApiController::class, 'deleteApiKey'], ['as' => 'fms.api.v1.settings.api-keys.delete']);

    // Basic Auth Clients
    $routes->get('basic-auth', [FMSSettingsApiController::class, 'listBasicAuthClients'], ['as' => 'fms.api.v1.settings.basic-auth.index']);
    $routes->post('basic-auth', [FMSSettingsApiController::class, 'createBasicAuthClient'], ['as' => 'fms.api.v1.settings.basic-auth.create']);
    $routes->get('basic-auth/(:segment)', [FMSSettingsApiController::class, 'showBasicAuthClient'], ['as' => 'fms.api.v1.settings.basic-auth.show']);
    $routes->put('basic-auth/(:segment)', [FMSSettingsApiController::class, 'updateBasicAuthClient'], ['as' => 'fms.api.v1.settings.basic-auth.update']);
    $routes->patch('basic-auth/(:segment)', [FMSSettingsApiController::class, 'updateBasicAuthClient'], ['as' => 'fms.api.v1.settings.basic-auth.patch']);
    $routes->post('basic-auth/(:segment)/reset-password', [FMSSettingsApiController::class, 'resetBasicAuthPassword'], ['as' => 'fms.api.v1.settings.basic-auth.reset-password']);
    $routes->post('basic-auth/(:segment)/unlock', [FMSSettingsApiController::class, 'unlockBasicAuthClient'], ['as' => 'fms.api.v1.settings.basic-auth.unlock']);
    $routes->post('basic-auth/(:segment)/revoke', [FMSSettingsApiController::class, 'revokeBasicAuthClient'], ['as' => 'fms.api.v1.settings.basic-auth.revoke']);
    $routes->post('basic-auth/(:segment)/activate', [FMSSettingsApiController::class, 'activateBasicAuthClient'], ['as' => 'fms.api.v1.settings.basic-auth.activate']);
    $routes->delete('basic-auth/(:segment)', [FMSSettingsApiController::class, 'deleteBasicAuthClient'], ['as' => 'fms.api.v1.settings.basic-auth.delete']);
});
