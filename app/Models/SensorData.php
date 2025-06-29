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
        'gas',
        'smoke',
        'fire_detected',
        'created_at',
        'updated_at'
    ];

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }
    }

    public static function create(array $attributes)
    {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }

    public function save()
    {
        try {
            if (!isset($this->attributes['created_at'])) {
                $this->attributes['created_at'] = date('Y-m-d H:i:s');
            }
            $this->attributes['updated_at'] = date('Y-m-d H:i:s');

            if (isset($this->attributes['id'])) {
                // Update
                return Capsule::table($this->table)
                    ->where('id', $this->attributes['id'])
                    ->update($this->attributes);
            } else {
                // Insert
                $id = Capsule::table($this->table)->insertGetId($this->attributes);
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