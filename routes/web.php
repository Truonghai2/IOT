<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\TrainingController;
use App\Models\Device;
use App\Core\View;
use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

return function (App $app) {
    // Web routes
    $app->get('/', function (Request $request, Response $response) {
        return $this->get('view')->render($response, 'home.twig', [
            'title' => 'IoT Monitoring System'
        ]);
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
    });

    // Training routes
    $app->post('/training', [TrainingController::class, 'store']);
    $app->get('/training', [TrainingController::class, 'index']);
};