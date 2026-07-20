<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'sensor_data';

    protected $fillable = [
        'suhu',
        'kelembaban',
        'jarak_air',
        'status_air',
    ];

    protected function casts(): array
    {
        return [
            'suhu' => 'float',
            'kelembaban' => 'float',
            'jarak_air' => 'float',
            'created_at' => 'datetime',
        ];
    }
}
