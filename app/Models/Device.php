<?php

namespace App\Models;

use Illuminate\Database\Capsule\Manager as Capsule;

class Device
{
    protected $attributes = [];
    protected $table = 'devices';
    
    protected $fillable = [
        'name',
        'type',
        'esp_ip',
        'status',
        'location',
        'description',
        'last_seen_at'
    ];

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }
    }

    public static function all()
    {
        try {
            $results = Capsule::table('devices')->get();
            $devices = [];
            foreach ($results as $row) {
                $devices[] = new static((array)$row);
            }
            return $devices;
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch devices: " . $e->getMessage());
        }
    }

    public static function find($id)
    {
        try {
            $row = Capsule::table('devices')->find($id);
            return $row ? new static((array)$row) : null;
        } catch (\Exception $e) {
            throw new \Exception("Failed to find device: " . $e->getMessage());
        }
    }

    public function save()
    {
        try {
            if (isset($this->attributes['id'])) {
                // Update
                return Capsule::table('devices')
                    ->where('id', $this->attributes['id'])
                    ->update($this->attributes);
            } else {
                // Insert
                $id = Capsule::table('devices')->insertGetId($this->attributes);
                $this->attributes['id'] = $id;
                return true;
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to save device: " . $e->getMessage());
        }
    }

    public function update(array $attributes = [])
    {
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
            return false;
        }

        try {
            return Capsule::table('devices')
                ->where('id', $this->attributes['id'])
                ->delete();
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete device: " . $e->getMessage());
        }
    }

    public function sensorData()
    {
        try {
            $row = Capsule::table('sensor_data')
                ->where('device_id', $this->attributes['id'])
                ->orderBy('created_at', 'desc')
                ->first();
            return $row ? new SensorData((array)$row) : null;
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch sensor data: " . $e->getMessage());
        }
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value)
    {
        if (in_array($key, $this->fillable)) {
            $this->attributes[$key] = $value;
        }
    }
} 