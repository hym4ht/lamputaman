<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class PumpSchedule extends Model
{
    public const DAY_LABELS = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    protected $fillable = [
        'name',
        'days',
        'start_time',
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

    public function daysLabel(): string
    {
        $days = collect($this->days ?? [])
            ->map(fn (mixed $day): int => (int) $day)
            ->filter(fn (int $day): bool => array_key_exists($day, self::DAY_LABELS))
            ->unique()
            ->sort()
            ->values();

        if ($days->count() === 7) {
            return 'Setiap hari';
        }

        return $days
            ->map(fn (int $day): string => self::DAY_LABELS[$day])
            ->implode(', ');
    }

    public function isActiveAt(?CarbonInterface $at = null): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        $days = collect($this->days ?? [])
            ->map(fn (mixed $day): int => (int) $day)
            ->filter(fn (int $day): bool => array_key_exists($day, self::DAY_LABELS))
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

            if ($now->greaterThanOrEqualTo($startsAt) && $now->lessThan($startsAt->addMinutes($duration))) {
                return true;
            }
        }

        return false;
    }

    public static function activeAt(?CarbonInterface $at = null): ?self
    {
        return self::query()
            ->where('is_enabled', true)
            ->orderBy('start_time')
            ->get()
            ->first(fn (self $schedule): bool => $schedule->isActiveAt($at));
    }

    /**
     * @return array<string, mixed>
     */
    public static function status(?CarbonInterface $at = null): array
    {
        $activeSchedule = self::activeAt($at);

        return [
            'automatic_active' => (bool) $activeSchedule,
            'active_schedule' => $activeSchedule ? [
                'id' => $activeSchedule->id,
                'name' => $activeSchedule->name,
                'days' => $activeSchedule->daysLabel(),
                'start_time' => $activeSchedule->startsAtLabel(),
                'duration_minutes' => $activeSchedule->duration_minutes,
            ] : null,
        ];
    }
}
