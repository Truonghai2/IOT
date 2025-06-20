<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Models\Device;
use App\Models\SensorData;
use App\Models\Subscription;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use Illuminate\Database\Capsule\Manager as Capsule;
use Minishlink\WebPush\WebPush;
use Pusher\Pusher;

class DeviceController extends Controller
{
    private function log($message, $data = null)
    {
        $logMessage = date('Y-m-d H:i:s') . " - " . $message;
        if ($data !== null) {
            $logMessage .= " - Data: " . json_encode($data);
        }
        
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        file_put_contents($logDir . '/device.log', $logMessage . PHP_EOL, FILE_APPEND);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response)
    {
        $devices = Device::all();
        
        // Debug information
        error_log("Debug devices in controller:");
        foreach ($devices as $device) {
            error_log("Device data: " . json_encode($device->toArray()));
        }
        
        return $this->view('devices/index.twig', ['devices' => $devices]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            $data = $request->getParsedBody();
            $this->log("Received data for device creation", $data);
            $errors = [];
            if (empty($data['name'])) {
                $errors[] = 'Name is required';
            }
            if (empty($data['type'])) {
                $errors[] = 'Type is required';
            }
            if (empty($data['esp_ip'])) {
                $errors[] = 'ESP IP is required';
            }

            if (!empty($errors)) {
                $this->log("Validation errors", $errors);
                return $this->jsonResponse($response, [
                    'error' => 'Validation failed',
                    'errors' => $errors,
                    'received_data' => $data
                ], 400);
            }

            // Prepare device data
            $deviceData = [
                'name' => $data['name'],
                'type' => $data['type'],
                'esp_ip' => $data['esp_ip'],
                'status' => $data['status'] ?? 'offline',
                'location' => $data['location'] ?? null,
                'description' => $data['description'] ?? null
            ];
            $this->log("Prepared device data", $deviceData);

            // Create device
            $device = new Device($deviceData);
            $this->log("Created device instance", $device->toArray());
            
            try {
                $saved = $device->save();
                $this->log("Save result", ['success' => $saved]);
                
                if (!$saved) {
                    $this->log("Failed to save device");
                    return $this->jsonResponse($response, [
                        'error' => 'Failed to save device',
                        'device_data' => $deviceData
                    ], 500);
                }

                $this->log("Device created successfully", $device->toArray());
                return $this->jsonResponse($response, [
                    'message' => 'Device created successfully',
                    'device' => $device
                ]);
            } catch (\Exception $e) {
                $this->log("Database error", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return $this->jsonResponse($response, [
                    'error' => 'Database error: ' . $e->getMessage(),
                    'device_data' => $deviceData
                ], 500);
            }
        } catch (\Exception $e) {
            $this->log("Error creating device", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->jsonResponse($response, [
                'error' => 'Failed to create device: ' . $e->getMessage(),
                'received_data' => $data ?? null
            ], 500);
        }
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $device = Device::find($args['id']);
        if (!$device) {
            return $this->jsonResponse($response, ['error' => 'Device not found'], 404);
        }
        return $this->view('devices/show.twig', ['device' => $device]);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $device = Device::find($args['id']);
        if (!$device) {
            return $this->jsonResponse($response, ['error' => 'Device not found'], 404);
        }

        $data = $request->getParsedBody();
        $device->update($data);
        return $this->jsonResponse($response, ['message' => 'Device updated successfully', 'device' => $device]);
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $device = Device::find($args['id']);
        if (!$device) {
            return $this->jsonResponse($response, ['error' => 'Device not found'], 404);
        }

        $device->delete();
        return $this->jsonResponse($response, ['message' => 'Device deleted successfully']);
    }

    public function apiList(ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            $devices = Device::all();
            $this->log("Retrieved devices", array_map(function($device) {
                return $device->toArray();
            }, $devices));
            return $this->jsonResponse($response, ['devices' => array_map(function($device) {
                return $device->toArray();
            }, $devices)]);
        } catch (\Exception $e) {
            $this->log("Error retrieving devices", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->jsonResponse($response, [
                'error' => 'Failed to retrieve devices: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiControl(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $device = Device::find($args['id']);
        if (!$device) {
            return $this->jsonResponse($response, ['error' => 'Device not found'], 404);
        }

        $data = $request->getParsedBody();
        error_log('Parsed body: ' . json_encode($data));
        if (!is_array($data) || empty($data)) {
            $raw = (string)$request->getBody();
            error_log('Raw body: ' . $raw);
            $data = json_decode($raw, true);
            error_log('Parsed from raw: ' . json_encode($data));
        }
        if (!is_array($data) || empty($data)) {
            return $this->jsonResponse($response, ['error' => 'No data provided'], 400);
        }

        $device->update($data);
        return $this->jsonResponse($response, ['message' => 'Device control updated successfully', 'device' => $device]);
    }

    public function getSensorData(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $device = Device::find($args['id']);
        if (!$device) {
            return $this->jsonResponse($response, ['error' => 'Device not found'], 404);
        }

        $sensorData = $device->sensorData();
        return $this->jsonResponse($response, ['sensor_data' => $sensorData]);
    }

    public function updateSensorData(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        try {
            $data = $request->getParsedBody();
            $this->log("Received sensor data", $data);

            // Validate data structure
            if (!isset($data['device_info']) || !isset($data['data']) || !is_array($data['data'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Invalid data format',
                    'message' => 'Request must include device_info and data array'
                ], 400);
            }

            // Find device by ESP IP address
            $deviceInfo = $data['device_info'];
            $device = Device::findByEspIp($deviceInfo['ip']);
            
            // If device not found, try to find by ID from URL
            if (!$device && isset($args['id'])) {
                $device = Device::find($args['id']);
            }

            // If still not found, create new device
            if (!$device) {
                $device = new Device([
                    'name' => 'ESP32_' . str_replace(':', '', $deviceInfo['mac']),
                    'type' => 'ESP32',
                    'esp_ip' => $deviceInfo['ip'],
                    'status' => 1,
                    'description' => json_encode([
                        'mac' => $deviceInfo['mac'],
                        'rssi' => $deviceInfo['rssi'],
                        'wifi_ssid' => $deviceInfo['wifi_ssid']
                    ])
                ]);
                $device->save();
            }

            // Process each data point
            $savedData = [];
            foreach ($data['data'] as $reading) {
                // Check for fire condition based on sensor data and AI prediction
                $fireDetected = false;
                if (
                    floatval($reading['temperature']) > 50 || // Temperature > 50°C
                    floatval($reading['gas_value']) > 1000 || // Gas concentration too high
                    floatval($reading['dust_value']) > 1000 || // Dust concentration too high
                    $reading['fire_sensor_status'] == 1 || // Hardware fire sensor detected
                    $reading['ai_prediction'] > 0 // AI predicted fire risk
                ) {
                    $fireDetected = true;
                }

                // Create sensor data record
                $sensorData = new SensorData([
                    'device_id' => $device->id,
                    'temperature' => floatval($reading['temperature']),
                    'humidity' => floatval($reading['humidity']),
                    'gas' => floatval($reading['gas_value']),
                    'smoke' => floatval($reading['dust_value']),
                    'fire_detected' => $fireDetected,
                    'created_at' => (intval($reading['timestamp']) > 946684800 ? date('Y-m-d H:i:s', $reading['timestamp']) : date('Y-m-d H:i:s'))
                ]);

                $sensorData->save();
                $savedData[] = $sensorData->toArray();

                $pusherData = array_merge(
                    $sensorData->toArray(),
                    [
                        'device_id' => $device->id,
                        'device_name' => $device->name,
                        'ai_prediction' => $reading['ai_prediction'] ?? 0
                    ]
                );
                $this->pushToPusher('sensor_update', $pusherData);

                if ($reading['ai_prediction'] == 2) {
                    $this->log("FIRE ALERT: AI prediction = 2 for device {$device->id}", $reading);
                    $this->sendFireNotification($device, $reading);
                }
            }

            $device->update([
                'last_seen_at' => date('Y-m-d H:i:s'),
                'status' => 1
            ]);

            return $this->jsonResponse($response, [
                'message' => 'Sensor data updated successfully',
                'device' => $device->toArray(),
                'sensor_data' => $savedData
            ]);

        } catch (\Exception $e) {
            $this->log("Error updating sensor data", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->jsonResponse($response, [
                'error' => 'Failed to update sensor data: ' . $e->getMessage()
            ], 500);
        }
    }

    private function sendFireNotification($device, $reading)
    {
        // Lấy tất cả subscription
        $subscriptions = Capsule::table('subscriptions')->get();
        if (!$subscriptions || count($subscriptions) == 0) {
            $this->log("No subscriptions to send notification");
            return;
        }

        // Chuẩn bị nội dung thông báo
        $payload = [
            'title' => 'FIRE ALERT!',
            'body' => "CẢNH BÁO CHÁY tại thiết bị {$device->name} (IP: {$device->esp_ip})! Nhiệt độ: {$reading['temperature']}°C, Gas: {$reading['gas_value']}, Khói: {$reading['dust_value']}",
            'url' => 'https://d957-2405-4802-1b20-10c0-bd67-f4b9-a6ae-f56f.ngrok-free.app/devices/' . $device->id
        ];

        // Gửi đến từng user_id (OneSignal)
        foreach ($subscriptions as $sub) {
            if (empty($sub->user_id)) {
                $this->log("Skip notification: missing user_id");
                continue;
            }
            $this->sendOneSignalNotification($sub->user_id, $payload);
        }
    }

    private function sendOneSignalNotification($userId, $payload)
    {
        $onesignalAppId = 'f41bdea2-508a-4082-9951-e77411fa9f53'; // Thay bằng appId của bạn
        $onesignalApiKey = 'ZGRlNjQ1M2UtOGRjMS00MjcwLTllZGItMTYzZDI5OTg2ZWNh'; // Thay bằng REST API Key của bạn

        $body = [
            'app_id' => $onesignalAppId,
            'include_player_ids' => [$userId],
            'headings' => ['en' => $payload['title']],
            'contents' => ['en' => $payload['body']],
            'url' => $payload['url'],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . $onesignalApiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->log("OneSignal response for user_id {$userId}", [
            'http_code' => $httpCode,
            'result' => $result
        ]);
    }

    private function pushToPusher($event, $data)
    {
        $options = [
            'cluster' => 'ap1',
            'useTLS' => true
        ];
        $pusher = new Pusher(
            '57030bb72296d60cd497', // key
            'a273dac9f32518844f6e', // secret
            '1801599',              
            $options
        );
        $pusher->trigger('device-channel', $event, $data);
    }

    protected function jsonResponse(ResponseInterface $response, $data, $status = 200)
    {
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status);
    }

    // API: Lấy sensor data mới nhất cho device
    public function getLatestSensorData(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $device = Device::find($args['id']);
        if (!$device) {
            return $this->jsonResponse($response, ['error' => 'Device not found'], 404);
        }
        $row = \Illuminate\Database\Capsule\Manager::table('sensor_data')
            ->where('device_id', $device->id)
            ->orderBy('created_at', 'desc')
            ->first();
        if (!$row) {
            return $this->jsonResponse($response, ['sensor_data' => null]);
        }
        $sensorData = (new \App\Models\SensorData((array)$row))->toArray();
        return $this->jsonResponse($response, ['sensor_data' => $sensorData]);
    }

    public function resendFireNotification(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $device = Device::find($args['id']);
        if (!$device) {
            return $this->jsonResponse($response, ['error' => 'Device not found'], 404);
        }

        // Chuẩn bị payload đơn giản để gửi lại
        $payload = [
            'title' => 'CẢNH BÁO CHÁY (NHẮC LẠI)',
            'body' => "Vẫn đang có cảnh báo cháy tại thiết bị {$device->name}! Yêu cầu xử lý ngay.",
            'url' => 'https://d957-2405-4802-1b20-10c0-bd67-f4b9-a6ae-f56f.ngrok-free.app/devices/' . $device->id
        ];

        // Lấy tất cả người dùng đã đăng ký và gửi thông báo
        $subscriptions = Capsule::table('subscriptions')->get();
        if ($subscriptions && count($subscriptions) > 0) {
            foreach ($subscriptions as $sub) {
                if (!empty($sub->user_id)) {
                    $this->sendOneSignalNotification($sub->user_id, $payload);
                }
            }
        }
        
        return $this->jsonResponse($response, ['message' => 'Resent notification successfully']);
    }
} 