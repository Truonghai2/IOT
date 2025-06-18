<?php

namespace App\Models;

use App\Core\Model;

class SensorData extends Model
{
    protected $table = 'sensor_data';
    protected $fillable = [
        'device_id',
        'temperature',
        'humidity',
        'gas_value',
        'dust_value',
        'fire_sensor_status'
    ];

    protected $casts = [
        'temperature' => 'float',
        'humidity' => 'float',
        'gas_value' => 'float',
        'dust_value' => 'float',
        'fire_sensor_status' => 'boolean'
    ];

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->$key = $value;
            }
        }
    }

    public static function create(array $attributes)
    {
        $model = new static($attributes);
        return $model;
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
} 