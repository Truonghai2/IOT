<?php

namespace App\Events;

use App\Models\SensorData;

class SensorDataReceived
{
    public $sensorData;

    public function __construct(SensorData $sensorData)
    {
        $this->sensorData = $sensorData;
    }

    public function broadcast()
    {
        // TODO: Implement WebSocket broadcasting
        $data = [
            'device_id' => $this->sensorData->device_id,
            'temperature' => $this->sensorData->temperature,
            'humidity' => $this->sensorData->humidity,
            'light_intensity' => $this->sensorData->light_intensity,
            'motion_detected' => $this->sensorData->motion_detected,
            'gas_value' => $this->sensorData->gas_value,
            'dust_value' => $this->sensorData->dust_value,
            'fire_sensor_status' => $this->sensorData->fire_sensor_status,
            'timestamp' => $this->sensorData->created_at
        ];

        // Broadcast to all connected WebSocket clients
        global $worker;
        foreach ($worker->connections as $connection) {
            $connection->send(json_encode([
                'event' => 'sensor.data',
                'data' => $data
            ]));
        }
    }
} 