<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\TrainingController;
use App\Models\Device;
use App\Core\View;
use App\Http\Controllers\SubscriptionController;
use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

return function (App $app) {
    // Web routes
    $app->get('/', function (Request $request, Response $response) {
        return $response->withHeader('Location', '/devices')->withStatus(302);
    });

    // Device routes
    $app->get('/devices', [DeviceController::class, 'index']);
    $app->get('/devices/{id}', [DeviceController::class, 'show']);

    // API routes
    $app->group('/api', function ($group) {
        $group->get('/devices', [DeviceController::class, 'apiList']);
        $group->post('/devices', [DeviceController::class, 'store']);
        $group->get('/devices/{id}', [DeviceController::class, 'show']);
        $group->put('/devices/{id}', [DeviceController::class, 'update']);
        $group->delete('/devices/{id}', [DeviceController::class, 'destroy']);
        $group->post('/devices/{id}/control', [DeviceController::class, 'apiControl']);
        $group->get('/devices/{id}/sensor-data', [DeviceController::class, 'getSensorData']);
        $group->get('/devices/{id}/latest-sensor-data', [DeviceController::class, 'getLatestSensorData']);
        $group->post('/devices/{device}/sensor-data', [DeviceController::class, 'updateSensorData']);
        $group->post('/devices/{id}/resend-notification', [DeviceController::class, 'resendFireNotification']);
        $group->post('/subscriptions', [SubscriptionController::class, 'store']);
    });

    // Training routes
    $app->post('/training', [TrainingController::class, 'store']);
    $app->get('/training', [TrainingController::class, 'index']);

    // Debug catch-all route for 404s
    $app->any('/{routes:.+}', function ($request, $response, $args) {
        error_log('404 for path: ' . $request->getUri()->getPath());
        $response->getBody()->write('Not found (debug catch-all)');
        return $response->withStatus(404);
    });
};