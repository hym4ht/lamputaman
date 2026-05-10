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
    ];

    protected function casts(): array
    {
        return [
            'suhu' => 'float',
            'kelembaban' => 'float',
            'created_at' => 'datetime',
        ];
    }
}
