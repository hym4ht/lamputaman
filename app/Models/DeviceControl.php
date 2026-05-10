<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class DeviceControl extends Model
{
    public const CREATED_AT = null;

    public const DEVICES = [
        'lampu1' => 'Lampu 1',
        'lampu2' => 'Lampu 2',
        'lampu3' => 'Lampu 3',
        'pompa' => 'Pompa',
    ];

    protected $fillable = [
        'device_name',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public static function ensureDefaults(): void
    {
        foreach (array_keys(self::DEVICES) as $deviceName) {
            self::query()->firstOrCreate([
                'device_name' => $deviceName,
            ], [
                'status' => false,
            ]);
        }
    }

    /**
     * @return array<string, int>
     */
    public static function manualSnapshot(): array
    {
        self::ensureDefaults();

        $controls = self::query()
            ->whereIn('device_name', array_keys(self::DEVICES))
            ->pluck('status', 'device_name');

        $snapshot = [];

        foreach (array_keys(self::DEVICES) as $deviceName) {
            $snapshot[$deviceName] = (int) (bool) ($controls[$deviceName] ?? false);
        }

        return $snapshot;
    }

    /**
     * @return array<string, int>
     */
    public static function snapshot(?CarbonInterface $at = null): array
    {
        $snapshot = self::manualSnapshot();

        if (PumpSchedule::activeAt($at)) {
            $snapshot['pompa'] = 1;
        }

        return $snapshot;
    }
}
