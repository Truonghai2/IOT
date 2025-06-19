<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\SubscriptionController;

// Device API Routes
$router->group(['prefix' => 'devices'], function ($router) {
    $router->get('/', [DeviceController::class, 'apiList']);
    $router->post('/', [DeviceController::class, 'store']);
    $router->get('/{device}', [DeviceController::class, 'show']);
    $router->put('/{device}', [DeviceController::class, 'update']);
    $router->delete('/{device}', [DeviceController::class, 'destroy']);
    $router->post('/{device}/control', [DeviceController::class, 'apiControl']);
    $router->get('/{device}/sensor-data', [DeviceController::class, 'getSensorData']);
    $router->post('/{device}/sensor-data', [DeviceController::class, 'updateSensorData']);
});

// Training API Routes
$router->group(['prefix' => 'training'], function ($router) {
    $router->post('/', [TrainingController::class, 'store']);
    $router->get('/', [TrainingController::class, 'index']);
    $router->get('/{device}', [TrainingController::class, 'getDeviceTraining']);
});
    
return [
    '/api/devices' => ['App\\Http\\Controllers\\DeviceController', 'apiList'],
    '/api/device-control' => ['App\\Http\\Controllers\\DeviceController', 'apiControl'],
]; 