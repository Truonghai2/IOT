<?php

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Workerman\Worker;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use App\Http\Controllers\DeviceController;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Slim\Psr7\Factory\ResponseFactory;

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize database
require __DIR__ . '/bootstrap/database.php';

// Create App
$app = AppFactory::create();

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Create Twig
$twig = Twig::create(__DIR__ . '/app/Views', ['cache' => false]);

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));

// Add CORS middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Add routes
$app->get('/', function ($request, $response) {
    return $response->withHeader('Location', '/devices')->withStatus(302);
});

// Device routes
$app->group('/devices', function ($group) {
    $group->get('', [DeviceController::class, 'index']);
    $group->post('', [DeviceController::class, 'store']);
    $group->get('/{id}', [DeviceController::class, 'show']);
    $group->put('/{id}', [DeviceController::class, 'update']);
    $group->delete('/{id}', [DeviceController::class, 'destroy']);
});

// API routes
$app->group('/api', function ($group) {
    // List devices
    $group->map(['GET', 'POST'], '/devices', [DeviceController::class, 'apiList']);
    
    // Device control
    $group->post('/devices/{id}/control', [DeviceController::class, 'apiControl']);
    
    // Sensor data
    $group->get('/devices/{id}/sensor-data', [DeviceController::class, 'getSensorData']);
});

// Create Workerman HTTP server
$worker = new Worker('http://0.0.0.0:8000');

// Set the number of processes
$worker->count = 1;

// Handle HTTP requests
$worker->onMessage = function($connection, Request $request) use ($app) {
    // Debug request
    error_log("Request Method: " . $request->method());
    error_log("Request URI: " . $request->uri());
    error_log("Request Headers: " . json_encode($request->header()));
    error_log("Request Body: " . $request->rawBody());

    // Handle OPTIONS requests directly
    if ($request->method() === 'OPTIONS') {
        $response = new Response(
            200,
            [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Headers' => 'X-Requested-With, Content-Type, Accept, Origin, Authorization',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
                'Content-Type' => 'application/json'
            ],
            json_encode(['status' => 'ok'])
        );
        $connection->send($response);
        return;
    }

    // Convert Workerman request to PSR-7 request
    $uri = (new UriFactory())->createUri($request->uri());
    $headers = $request->header();
    $cookies = $request->cookie();
    $serverParams = [
        'REQUEST_METHOD' => $request->method(),
        'REQUEST_URI' => $request->uri(),
        'QUERY_STRING' => parse_url($request->uri(), PHP_URL_QUERY) ?? '',
        'HTTP_HOST' => $headers['host'] ?? 'localhost:8000',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
        'REQUEST_TIME' => time(),
        'REQUEST_TIME_FLOAT' => microtime(true),
    ];

    // Handle POST data
    $parsedBody = [];
    if ($request->method() === 'POST' || $request->method() === 'PUT') {
        $contentType = $headers['content-type'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $parsedBody = json_decode($request->rawBody(), true) ?? [];
        } else {
            $parsedBody = $request->post();
        }
        error_log("Parsed Body: " . json_encode($parsedBody));
    }

    $stream = (new StreamFactory())->createStream($request->rawBody());
    $uploadedFiles = [];
    $queryParams = $request->get ?? [];

    // Create PSR-7 request
    $psrRequest = (new ServerRequestFactory())->createServerRequest(
        $request->method(),
        $uri,
        $serverParams
    );

    // Add all headers
    foreach ($headers as $name => $value) {
        $psrRequest = $psrRequest->withHeader($name, $value);
    }

    // Add other request components
    $psrRequest = $psrRequest
        ->withCookieParams($cookies)
        ->withQueryParams($queryParams)
        ->withBody($stream)
        ->withUploadedFiles($uploadedFiles);

    // Add parsed body if exists
    if (!empty($parsedBody)) {
        $psrRequest = $psrRequest->withParsedBody($parsedBody);
    }

    try {
        // Handle request with Slim
        $psrResponse = $app->handle($psrRequest);

        // Convert PSR-7 response to Workerman response
        $response = new Response(
            $psrResponse->getStatusCode(),
            $psrResponse->getHeaders(),
            (string) $psrResponse->getBody()
        );

        $connection->send($response);
    } catch (\Exception $e) {
        error_log("Error handling request: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Send error response
        $response = new Response(
            500,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => $e->getMessage()])
        );
        $connection->send($response);
    }
};

// Run worker
Worker::runAll(); 