<?php
require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Worker;

// Đường dẫn file tạm để truyền message giữa TCP và WebSocket worker
$broadcastFile = __DIR__ . '/broadcast_queue.txt';

$tcpWorker = new Worker('tcp://127.0.0.1:12345');
$tcpWorker->onMessage = function($connection, $data) use ($broadcastFile) {
    $message = trim($data);
    if ($message) {
        // Ghi message vào file tạm để WebSocket worker đọc và broadcast
        file_put_contents($broadcastFile, $message . "\n", FILE_APPEND);
        echo "[TCP] Queued message for broadcast\n";
    }
    $connection->send("ok\n");
};

Worker::runAll(); 