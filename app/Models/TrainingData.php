<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingData extends Model
{
    protected $fillable = [
        'temperature',
        'humidity',
        'gas_value',
        'dust_value',
        'fire_sensor_status',
        'label',
        'timestamp',
        'device_id'
    ];

    protected $casts = [
        'temperature' => 'float',
        'humidity' => 'float',
        'gas_value' => 'float',
        'dust_value' => 'float',
        'fire_sensor_status' => 'boolean',
        'timestamp' => 'datetime'
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
} 