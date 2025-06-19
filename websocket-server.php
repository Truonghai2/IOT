<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Start WebSocket server
$wsServer = new Workerman\Worker('websocket://0.0.0.0:8080');
$wsServer->count = 1;

// Global array to store connections by device_id
$deviceConnections = [];

// Đường dẫn file tạm để truyền message giữa TCP và WebSocket worker
$broadcastFile = __DIR__ . '/broadcast_queue.txt';

$wsServer->onConnect = function($connection) {
    echo "New WebSocket connection\n";
};

$wsServer->onMessage = function($connection, $data) use (&$deviceConnections) {
    $message = json_decode($data, true);

    if (isset($message['action']) && $message['action'] === 'subscribe' && isset($message['device_id'])) {
        $connection->device_id = $message['device_id'];
        $deviceConnections[$message['device_id']][] = $connection;
        echo "[WebSocket] Client subscribed to device_id: {$message['device_id']}\n";
        $connection->send(json_encode([
            'status' => 'subscribed',
            'device_id' => $message['device_id']
        ]));
        return;
    }

    if (isset($message['esp_ip']) || isset($message['device_id'])) {
        $deviceId = $message['device_id'] ?? null;
        echo "[WebSocket] Sensor data for device_id: " . ($deviceId ?: 'null') . "\n";
        if ($deviceId && isset($deviceConnections[$deviceId])) {
            $count = count($deviceConnections[$deviceId]);
            echo "[WebSocket] Broadcast sensor_update to device_id: $deviceId (clients: $count)\n";
            foreach ($deviceConnections[$deviceId] as $conn) {
                $conn->send(json_encode([
                    'event' => 'sensor_update',
                    'device_id' => $deviceId,
                    'data' => $message
                ]));
            }
        }
        $connection->send(json_encode([
            'status' => 'success',
            'message' => 'Data received',
            'device_id' => $deviceId
        ]));
        return;
    }

    $connection->send(json_encode([
        'status' => 'error',
        'message' => 'Invalid message format'
    ]));
};

$wsServer->onClose = function($connection) use (&$deviceConnections) {
    if (isset($connection->device_id)) {
        $deviceId = $connection->device_id;
        echo "WebSocket connection closed (device_id: $deviceId)\n";
        if (isset($deviceConnections[$deviceId])) {
            $deviceConnections[$deviceId] = array_filter(
                $deviceConnections[$deviceId],
                fn($conn) => $conn !== $connection
            );
            if (empty($deviceConnections[$deviceId])) {
                unset($deviceConnections[$deviceId]);
            }
        }
    } else {
        echo "WebSocket connection closed (device_id: unknown)\n";
    }
};

// Đọc file queue và broadcast định kỳ (tương thích Windows)
$wsServer->onWorkerStart = function() use (&$deviceConnections, $broadcastFile) {
    echo "[WebSocket] Start broadcast file watcher\n";
    while (true) {
        if (file_exists($broadcastFile)) {
            $lines = file($broadcastFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines) {
                file_put_contents($broadcastFile, ''); // Xóa file sau khi đọc
                foreach ($lines as $line) {
                    $message = json_decode($line, true);
                    if (isset($message['event']) && $message['event'] === 'sensor_update' && isset($message['device_id'])) {
                        $deviceId = $message['device_id'];
                        $count = isset($deviceConnections[$deviceId]) ? count($deviceConnections[$deviceId]) : 0;
                        echo "[BroadcastFile] Broadcast sensor_update to device_id: $deviceId (clients: $count)\n";
                        if ($count > 0) {
                            foreach ($deviceConnections[$deviceId] as $conn) {
                                $conn->send(json_encode($message));
                            }
                        }
                    }
                }
            }
        }
        usleep(500000); // 0.5s
    }
};

Workerman\Worker::runAll(); 