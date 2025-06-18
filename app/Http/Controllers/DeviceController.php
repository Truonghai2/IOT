<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Models\Device;
use App\Models\SensorData;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

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
        return $this->view('devices/index.twig', ['devices' => $devices]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            $data = $request->getParsedBody();
            $this->log("Received data for device creation", $data);

            // Validate required fields
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
        $device->update($data);
        return $this->jsonResponse($response, ['message' => 'Device control updated successfully', 'device' => $device]);
    }

    public function getSensorData(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $device = Device::find($args['id']);
        if (!$device) {
            return $this->jsonResponse($response, ['error' => 'Device not found'], 404);
        }

        $sensorData = $device->sensorData()->latest()->first();
        return $this->jsonResponse($response, ['sensor_data' => $sensorData]);
    }

    protected function jsonResponse(ResponseInterface $response, $data, $status = 200)
    {
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status);
    }
} 