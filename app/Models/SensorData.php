<?php

namespace App\Models;

use Illuminate\Database\Capsule\Manager as Capsule;

class SensorData
{
    protected $attributes = [];
    protected $table = 'sensor_data';
    
    protected $fillable = [
        'device_id',
        'temperature',
        'humidity',
        'pressure',
        'gas',
        'created_at'
    ];

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }
    }

    public function save()
    {
        try {
            if (isset($this->attributes['id'])) {
                // Update
                return Capsule::table('sensor_data')
                    ->where('id', $this->attributes['id'])
                    ->update($this->attributes);
            } else {
                // Insert
                $id = Capsule::table('sensor_data')->insertGetId($this->attributes);
                $this->attributes['id'] = $id;
                return true;
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to save sensor data: " . $e->getMessage());
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