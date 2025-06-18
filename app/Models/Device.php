<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
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

    protected $casts = [
        'status' => 'string',
        'last_seen_at' => 'datetime'
    ];

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->$key = $value;
            }
        }
    }

    public static function where($column, $value)
    {
        // TODO: Implement database query
        return new static(['esp_ip' => $value]);
    }

    public function sensorData(): HasMany
    {
        return $this->hasMany(SensorData::class);
    }

    public function trainingData(): HasMany
    {
        return $this->hasMany(TrainingData::class);
    }

    public static function create(array $attributes = [])
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public function update(array $attributes = [], array $options = [])
    {
        return parent::update($attributes, $options);
    }

    public function save(array $options = [])
    {
        return parent::save($options);
    }
} 