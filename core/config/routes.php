<?php
/** @var FastRoute\RouteCollector  $router */
/** @var modX  $modx */

require_once MODX_CORE_PATH . 'app/modules/ajax/routes.php';

/**
 * Авторизация
 */
$router->get('auth[/{key}]', function($key = 'login') use ($modx) {
    if ($key == 'logout') {
        $modx->runProcessor('/security/logout');
    }
    if ($modx->user->isAuthenticated($modx->context->key)) {
        // redirectx('profile');
        redirectx('dashboard/reports');
    }
    if (!in_array($key, ['login', 'register', 'forgot'])) {
        redirectx('auth');
    }
    $modx->resource = zoomx()->getResource($key == 'login' ? 'auth' : "auth-{$key}");
    return viewx("user-auth.tpl", ['section' => $key]);
});

/**
 * Личный кабинет
 */
$router->get('dashboard/reports[/{key}]', ['DashboardController', 'reports']);
$router->get('dashboard[/{key}]', ['DashboardController', 'index']);

/**
 * Api
 */
$router->post('api/reports/{id}/change', ['ReportApiController', 'change']);
$router->post('api/reports/{id}/change-step', ['ReportApiController', 'changeStep']);
$router->post('api/reports/{id}/decode-vin', ['ReportApiController', 'decodeVin']);
$router->post('api/reports/{id}/replace-offer', ['ReportApiController', 'replaceOffer']);
$router->post('api/reports/{id}/upload-file', ['ReportApiController', 'uploadFile']);
$router->get('api/reports/{id}/download-file', ['ReportApiController', 'downloadFile']);
$router->post('api/reports/{id}/send-email', ['ReportApiController', 'sendEmail']);

$router->get('api/auto', ['AutoApiController', 'getVehicleData']);

$router->post('api/user/update', ['UserApiController', 'update']);
$router->post('api/user/upload-file', ['UserApiController', 'uploadFile']);
$router->post('api/user/delete-file', ['UserApiController', 'deleteFile']);

$router->get('api/payments', ['PaymentController', 'create']);
$router->post('api/payments/callback', ['PaymentController', 'callback']);

// $router->post('api/evaluation/city', ['ReportApiController', 'getCities']);
// $router->post('api/evaluation/models', ['ReportApiController', 'getModels']);


