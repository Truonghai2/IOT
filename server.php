<?php

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Workerman\Worker;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SubscriptionController;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Slim\Psr7\Factory\ResponseFactory;
use DI\Container;

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize database
require __DIR__ . '/bootstrap/database.php';

// Create Container
$container = new Container();

// Set view in container
$container->set('view', function() {
    return Twig::create(__DIR__ . '/app/Views', ['cache' => false]);
});

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app));

// Add CORS middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Load routes from routes/web.php
$routes = require __DIR__ . '/routes/web.php';
$routes($app);

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

    $publicPath = __DIR__ . '/public';
    $requestUri = parse_url($request->uri(), PHP_URL_PATH);
    $filePath = realpath($publicPath . $requestUri);

    // Handle OneSignal service worker files specifically
    if (strpos($requestUri, 'OneSignalSDKWorker.js') !== false || 
        strpos($requestUri, 'OneSignalSDK.sw.js') !== false) {
        
        $oneSignalFile = __DIR__ . '/public/OneSignalSDKWorker.js';
        if (file_exists($oneSignalFile)) {
            $body = file_get_contents($oneSignalFile);
            $response = new Response(
                200,
                ['Content-Type' => 'application/javascript'],
                $body
            );
            $connection->send($response);
            return;
        }
    }

    if ($filePath && strpos($filePath, $publicPath) === 0 && is_file($filePath)) {
        $mimeType = mime_content_type($filePath);
        $body = file_get_contents($filePath);
        $response = new Response(
            200,
            ['Content-Type' => $mimeType],
            $body
        );
        $connection->send($response);
        return;
    }

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

$publicPath = __DIR__ . '/public';
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = realpath($publicPath . $requestUri);

if ($filePath && strpos($filePath, $publicPath) === 0 && is_file($filePath)) {
    return readfile($filePath);
} 