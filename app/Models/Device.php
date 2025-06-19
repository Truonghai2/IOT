<?php

namespace App\Models;

use Illuminate\Database\Capsule\Manager as Capsule;

class Device
{
    protected $attributes = [];
    protected $table = 'devices';
    
    protected $fillable = [
        'id',
        'name',
        'type',
        'esp_ip',
        'status',
        'location',
        'description',
        'last_seen_at',
        'created_at',
        'updated_at'
    ];

    public function __construct(array $attributes = [])
    {
        error_log("Constructing Device with attributes: " . print_r($attributes, true));
        $this->attributes = $attributes; // Store all attributes directly
    }

    public static function all()
    {
        try {
            $results = Capsule::table('devices')->get();
            error_log("Raw database results: " . print_r($results, true));
            
            $devices = [];
            foreach ($results as $row) {
                // Convert stdClass to array and maintain all properties
                $device = new static((array)$row);
                error_log("Created device with attributes: " . print_r($device->toArray(), true));
                $devices[] = $device;
            }
            
            return $devices;
        } catch (\Exception $e) {
            error_log("Error in Device::all(): " . $e->getMessage());
            throw new \Exception("Failed to fetch devices: " . $e->getMessage());
        }
    }

    public static function find($id)
    {
        try {
            $row = Capsule::table('devices')->find($id);
            error_log("Finding device {$id}, result: " . print_r($row, true));
            return $row ? new static((array)$row) : null;
        } catch (\Exception $e) {
            error_log("Error in Device::find(): " . $e->getMessage());
            throw new \Exception("Failed to find device: " . $e->getMessage());
        }
    }

    public static function findByEspIp($ip)
    {
        try {
            $row = Capsule::table('devices')->where('esp_ip', $ip)->first();
            error_log("Finding device by IP {$ip}, result: " . print_r($row, true));
            return $row ? new static((array)$row) : null;
        } catch (\Exception $e) {
            error_log("Error in Device::findByEspIp(): " . $e->getMessage());
            throw new \Exception("Failed to find device by IP: " . $e->getMessage());
        }
    }

    public function save()
    {
        try {
            error_log("Saving device with attributes: " . print_r($this->attributes, true));
            if (isset($this->attributes['id'])) {
                // Update
                return Capsule::table('devices')
                    ->where('id', $this->attributes['id'])
                    ->update($this->attributes);
            } else {
                // Insert
                $id = Capsule::table('devices')->insertGetId($this->attributes);
                $this->attributes['id'] = $id;
                error_log("Inserted new device with ID: " . $id);
                return true;
            }
        } catch (\Exception $e) {
            error_log("Error saving device: " . $e->getMessage());
            throw new \Exception("Failed to save device: " . $e->getMessage());
        }
    }

    public function update(array $attributes = [])
    {
        error_log("Updating device with attributes: " . print_r($attributes, true));
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this->save();
    }

    public function delete()
    {
        if (!isset($this->attributes['id'])) {
            error_log("Cannot delete device: no ID set");
            return false;
        }

        try {
            $result = Capsule::table('devices')
                ->where('id', $this->attributes['id'])
                ->delete();
            error_log("Deleted device {$this->attributes['id']}, result: " . $result);
            return $result;
        } catch (\Exception $e) {
            error_log("Error deleting device: " . $e->getMessage());
            throw new \Exception("Failed to delete device: " . $e->getMessage());
        }
    }

    public function sensorData()
    {
        try {
            if (!isset($this->attributes['id'])) {
                error_log("Cannot get sensor data: no device ID set");
                return null;
            }
            
            $rows = Capsule::table('sensor_data')
                ->where('device_id', $this->attributes['id'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            error_log("Fetched sensor data for device {$this->attributes['id']}: " . print_r($rows, true));
            if (!$rows || count($rows) === 0) return null;
            $result = [];
            foreach ($rows as $row) {
                $result[] = (new SensorData((array)$row))->toArray();
            }
            return $result;
        } catch (\Exception $e) {
            error_log("Error fetching sensor data: " . $e->getMessage());
            throw new \Exception("Failed to fetch sensor data: " . $e->getMessage());
        }
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function __get($name)
    {
        error_log("Accessing property: {$name}");
        error_log("Available attributes: " . print_r($this->attributes, true));
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    public function __isset($name)
    {
        return isset($this->attributes[$name]);
        }

    public function __toString()
    {
        return json_encode($this->attributes);
    }
} 