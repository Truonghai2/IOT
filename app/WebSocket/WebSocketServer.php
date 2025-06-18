<?php

namespace App\WebSocket;

use Workerman\Worker;
use App\Models\Device;
use App\Models\SensorData;
use App\Events\SensorDataReceived;

class WebSocketServer
{
    protected $worker;

    public function __construct()
    {
        $this->worker = new Worker('websocket://0.0.0.0:8080');
        $this->worker->count = 1;

        $this->worker->onConnect = [$this, 'onConnect'];
        $this->worker->onMessage = [$this, 'onMessage'];
        $this->worker->onClose = [$this, 'onClose'];
    }

    public function onConnect($connection)
    {
        echo "New connection\n";
    }

    public function onMessage($connection, $data)
    {
        try {
            $message = json_decode($data, true);
            
            if (!$message || !isset($message['esp_ip'])) {
                throw new \Exception('Invalid message format');
            }

            // Update device status
            $device = Device::where('esp_ip', $message['esp_ip'])->first();
            if ($device) {
                $device->update([
                    'last_seen_at' => now(),
                    'status' => true
                ]);

                // Create sensor data
                $sensorData = SensorData::create([
                    'device_id' => $device->id,
                    'temperature' => $message['temp'] ?? 0,
                    'humidity' => $message['hum'] ?? 0,
                    'light_intensity' => $message['light'] ?? 0,
                    'motion_detected' => $message['pir'] ?? false,
                    'gas_value' => $message['gas'] ?? 0,
                    'dust_value' => $message['dust'] ?? 0,
                    'fire_sensor_status' => $message['fire'] ?? false
                ]);

                // Broadcast the event
                event(new SensorDataReceived($sensorData));

                // Send acknowledgment
                $connection->send(json_encode([
                    'status' => 'success',
                    'message' => 'Data received'
                ]));
            }
        } catch (\Exception $e) {
            $connection->send(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
        }
    }

    public function onClose($connection)
    {
        echo "Connection closed\n";
    }

    public function start()
    {
        Worker::runAll();
    }
} 