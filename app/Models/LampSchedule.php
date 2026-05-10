<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

class LampSchedule extends Model
{
    public const TARGET_ALL = 'all_lamps';

    public const TARGET_LABELS = [
        self::TARGET_ALL => 'Semua Lampu',
        'lampu1' => 'Lampu 1',
        'lampu2' => 'Lampu 2',
        'lampu3' => 'Lampu 3',
    ];

    protected $fillable = [
        'name',
        'target',
        'days',
        'start_time',
        'end_time',
        'duration_minutes',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'array',
            'duration_minutes' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    public function startsAtLabel(): string
    {
        return substr((string) $this->start_time, 0, 5);
    }

    public function endsAtLabel(): string
    {
        if (filled($this->end_time)) {
            return substr((string) $this->end_time, 0, 5);
        }

        [$hour, $minute] = array_pad(explode(':', (string) $this->start_time), 2, 0);
        $totalMinutes = (((int) $hour * 60) + (int) $minute + max(1, (int) $this->duration_minutes)) % 1440;

        return sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
    }

    public function timeRangeLabel(): string
    {
        return $this->startsAtLabel().' - '.$this->endsAtLabel();
    }

    public function daysLabel(): string
    {
        $days = collect($this->days ?? [])
            ->map(fn (mixed $day): int => (int) $day)
            ->filter(fn (int $day): bool => array_key_exists($day, PumpSchedule::DAY_LABELS))
            ->unique()
            ->sort()
            ->values();

        if ($days->count() === 7) {
            return 'Setiap hari';
        }

        return $days
            ->map(fn (int $day): string => PumpSchedule::DAY_LABELS[$day])
            ->implode(', ');
    }

    public function targetLabel(): string
    {
        return self::TARGET_LABELS[$this->target] ?? 'Lampu';
    }

    /**
     * @return array<int, string>
     */
    public function deviceNames(): array
    {
        if ($this->target === self::TARGET_ALL) {
            return array_keys(DeviceControl::LAMP_DEVICES);
        }

        return array_key_exists((string) $this->target, DeviceControl::LAMP_DEVICES)
            ? [(string) $this->target]
            : [];
    }

    public function isActiveAt(?CarbonInterface $at = null): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        $days = collect($this->days ?? [])
            ->map(fn (mixed $day): int => (int) $day)
            ->filter(fn (int $day): bool => array_key_exists($day, PumpSchedule::DAY_LABELS))
            ->unique()
            ->all();

        if ($days === []) {
            return false;
        }

        [$hour, $minute] = array_pad(explode(':', (string) $this->start_time), 2, 0);
        $now = CarbonImmutable::instance($at ?? now())->timezone(config('app.timezone'));
        $duration = max(1, (int) $this->duration_minutes);

        $candidateStarts = [
            $now->setTime((int) $hour, (int) $minute),
            $now->subDay()->setTime((int) $hour, (int) $minute),
        ];

        foreach ($candidateStarts as $startsAt) {
            if (! in_array($startsAt->isoWeekday(), $days, true)) {
                continue;
            }

            $endsAt = $this->endTimeForStart($startsAt) ?? $startsAt->addMinutes($duration);

            if ($now->greaterThanOrEqualTo($startsAt) && $now->lessThan($endsAt)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return EloquentCollection<int, self>
     */
    public static function activeAt(?CarbonInterface $at = null): EloquentCollection
    {
        return self::query()
            ->where('is_enabled', true)
            ->orderBy('start_time')
            ->get()
            ->filter(fn (self $schedule): bool => $schedule->isActiveAt($at))
            ->values();
    }

    /**
     * @return array<string, int>
     */
    public static function scheduledSnapshot(?CarbonInterface $at = null): array
    {
        $snapshot = array_fill_keys(array_keys(DeviceControl::LAMP_DEVICES), 0);

        foreach (self::activeAt($at) as $schedule) {
            foreach ($schedule->deviceNames() as $deviceName) {
                $snapshot[$deviceName] = 1;
            }
        }

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    public static function status(?CarbonInterface $at = null): array
    {
        $activeSchedules = self::activeAt($at);

        return [
            'automatic_active' => $activeSchedules->isNotEmpty(),
            'active_devices' => self::scheduledSnapshot($at),
            'active_schedules' => $activeSchedules
                ->map(fn (self $schedule): array => [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'target' => $schedule->targetLabel(),
                    'days' => $schedule->daysLabel(),
                    'start_time' => $schedule->startsAtLabel(),
                    'end_time' => $schedule->endsAtLabel(),
                    'time_range' => $schedule->timeRangeLabel(),
                    'duration_minutes' => $schedule->duration_minutes,
                ])
                ->values()
                ->all(),
        ];
    }

    private function endTimeForStart(CarbonImmutable $startsAt): ?CarbonImmutable
    {
        if (blank($this->end_time)) {
            return null;
        }

        [$hour, $minute] = array_pad(explode(':', (string) $this->end_time), 2, 0);
        $endsAt = $startsAt->setTime((int) $hour, (int) $minute);

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            return $endsAt->addDay();
        }

        return $endsAt;
    }
}
