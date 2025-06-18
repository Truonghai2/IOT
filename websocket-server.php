<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Start WebSocket server
$wsServer = new Workerman\Worker('websocket://0.0.0.0:8080');
$wsServer->count = 1;

$wsServer->onConnect = function($connection) {
    echo "New WebSocket connection\n";
};

$wsServer->onMessage = function($connection, $data) {
    try {
        $message = json_decode($data, true);
        if (!$message || !isset($message['esp_ip'])) {
            throw new \Exception('Invalid message format');
        }
        
        // Process WebSocket message
        $connection->send(json_encode([
            'status' => 'success',
            'message' => 'Data received'
        ]));
    } catch (\Exception $e) {
        $connection->send(json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]));
    }
};

$wsServer->onClose = function($connection) {
    echo "WebSocket connection closed\n";
};

// Run worker
Workerman\Worker::runAll(); 