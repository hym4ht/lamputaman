<?php

namespace App\Http\Controllers;

use App\Models\DeviceControl;
use App\Models\LampSchedule;
use App\Models\PumpSchedule;
use App\Models\SensorData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const DEFAULT_SENSOR_RANGE = '25m';

    private const SENSOR_CHART_MAX_POINTS = 80;

    private const SENSOR_RANGES = [
        '1m' => ['label' => '1 Menit', 'minutes' => 1],
        '5m' => ['label' => '5 Menit', 'minutes' => 5],
        '25m' => ['label' => '25 Menit', 'minutes' => 25],
        '1h' => ['label' => '1 Jam', 'minutes' => 60],
        '1d' => ['label' => '1 Hari', 'minutes' => 1440],
    ];

    public function publicReport()
    {
    return view('public'); 
                            }


    public function index(): View
    {
        DeviceControl::ensureDefaults();
        $screen = request()->route('screen', 'ringkasan');
        $screen = in_array($screen, ['ringkasan', 'kontrol', 'jadwal'], true)
            ? $screen
            : 'ringkasan';

        return view('dashboard.index', [
            'activeScreen' => $screen,
            'controls' => DeviceControl::snapshot(),
            'manualControls' => DeviceControl::manualSnapshot(),
            'devices' => DeviceControl::DEVICES,
            'lampDevices' => DeviceControl::LAMP_DEVICES,
            'dayLabels' => PumpSchedule::DAY_LABELS,
            'lampTargets' => LampSchedule::TARGET_LABELS,
            'latest' => SensorData::query()->latest('created_at')->first(),
            'pumpSchedules' => PumpSchedule::query()->orderBy('start_time')->get(),
            'pumpStatus' => $this->pumpStatus(),
            'lampSchedules' => LampSchedule::query()->orderBy('start_time')->get(),
            'lampStatus' => LampSchedule::status(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $sensorRange = $this->sensorRange($request);

        return response()->json([
            'latest' => $this->latestReading(),
            'readings' => $this->sensorReadings($sensorRange),
            'sensor_range' => [
                'key' => $sensorRange,
                'label' => self::SENSOR_RANGES[$sensorRange]['label'],
            ],
            'controls' => DeviceControl::snapshot(),
            'manual_controls' => DeviceControl::manualSnapshot(),
            'pump' => $this->pumpStatus(),
            'lamp' => LampSchedule::status(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function updateControl(Request $request, string $device): JsonResponse
    {
        abort_unless(array_key_exists($device, DeviceControl::DEVICES), 404);

        $validated = $request->validate([
            'status' => ['required', 'boolean'],
        ]);

        $control = DeviceControl::query()->firstOrCreate([
            'device_name' => $device,
        ]);

        $control->forceFill([
            'status' => (bool) $validated['status'],
        ])->save();

        return response()->json([
            'device' => $device,
            'label' => DeviceControl::DEVICES[$device],
            'status' => (int) $control->status,
            'controls' => DeviceControl::snapshot(),
            'manual_controls' => DeviceControl::manualSnapshot(),
            'pump' => $this->pumpStatus(),
            'lamp' => LampSchedule::status(),
        ]);
    }

    public function updateLampControls(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'boolean'],
        ]);

        foreach (array_keys(DeviceControl::LAMP_DEVICES) as $device) {
            $control = DeviceControl::query()->firstOrCreate([
                'device_name' => $device,
            ]);

            $control->forceFill([
                'status' => (bool) $validated['status'],
            ])->save();
        }

        return response()->json([
            'controls' => DeviceControl::snapshot(),
            'manual_controls' => DeviceControl::manualSnapshot(),
            'pump' => $this->pumpStatus(),
            'lamp' => LampSchedule::status(),
        ]);
    }

    public function storePumpSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:80'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        PumpSchedule::query()->create([
            'name' => filled($validated['name'] ?? null) ? $validated['name'] : 'Jadwal Pompa',
            'days' => $this->normalizeDays($validated['days']),
            'start_time' => $validated['start_time'],
            'duration_minutes' => $validated['duration_minutes'],
            'is_enabled' => (bool) $validated['is_enabled'],
        ]);

        return redirect()
            ->route('dashboard.jadwal')
            ->with('status', 'Jadwal pompa berhasil ditambahkan.');
    }

    public function storeLampSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
        ]);

        LampSchedule::query()->create([
            'name' => 'Jadwal Lampu',
            'target' => LampSchedule::TARGET_ALL,
            'days' => array_keys(PumpSchedule::DAY_LABELS),
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'duration_minutes' => $this->minutesBetweenClockTimes($validated['start_time'], $validated['end_time']),
            'is_enabled' => true,
        ]);

        return redirect()
            ->route('dashboard.jadwal')
            ->with('status', 'Jadwal lampu berhasil ditambahkan.');
    }

    public function togglePumpSchedule(Request $request, PumpSchedule $pumpSchedule): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
        ]);

        $pumpSchedule->forceFill([
            'is_enabled' => (bool) $validated['is_enabled'],
        ])->save();

        return redirect()
            ->route('dashboard.jadwal')
            ->with('status', 'Status jadwal pompa diperbarui.');
    }

    public function toggleLampSchedule(Request $request, LampSchedule $lampSchedule): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
        ]);

        $lampSchedule->forceFill([
            'is_enabled' => (bool) $validated['is_enabled'],
        ])->save();

        return redirect()
            ->route('dashboard.jadwal')
            ->with('status', 'Status jadwal lampu diperbarui.');
    }

    public function destroyPumpSchedule(PumpSchedule $pumpSchedule): RedirectResponse
    {
        $pumpSchedule->delete();

        return redirect()
            ->route('dashboard.jadwal')
            ->with('status', 'Jadwal pompa dihapus.');
    }

    public function destroyLampSchedule(LampSchedule $lampSchedule): RedirectResponse
    {
        $lampSchedule->delete();

        return redirect()
            ->route('dashboard.jadwal')
            ->with('status', 'Jadwal lampu dihapus.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function latestReading(): ?array
    {
        $latest = SensorData::query()->latest('created_at')->first();

        if (! $latest) {
            return null;
        }

        return [
            'suhu' => round($latest->suhu, 1),
            'kelembaban' => round($latest->kelembaban, 1),
            'created_at' => $latest->created_at?->toIso8601String(),
            'label' => $latest->created_at?->timezone(config('app.timezone'))->format('d M Y H:i:s'),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function sensorReadings(string $range): Collection
    {
        $rangeConfig = self::SENSOR_RANGES[$range] ?? self::SENSOR_RANGES[self::DEFAULT_SENSOR_RANGE];

        $readings = SensorData::query()
            ->where('created_at', '>=', now()->subMinutes($rangeConfig['minutes']))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'suhu', 'kelembaban', 'created_at']);

        return $this->sampleSensorReadings($readings)
            ->values()
            ->map(fn (SensorData $reading): array => [
                'label' => $reading->created_at?->timezone(config('app.timezone'))->format('H:i'),
                'suhu' => round($reading->suhu, 1),
                'kelembaban' => round($reading->kelembaban, 1),
                'created_at' => $reading->created_at?->toIso8601String(),
            ]);
    }

    private function sensorRange(Request $request): string
    {
        $range = (string) $request->query('sensor_range', self::DEFAULT_SENSOR_RANGE);

        return array_key_exists($range, self::SENSOR_RANGES)
            ? $range
            : self::DEFAULT_SENSOR_RANGE;
    }

    /**
     * @param  Collection<int, SensorData>  $readings
     * @return Collection<int, SensorData>
     */
    private function sampleSensorReadings(Collection $readings): Collection
    {
        $readings = $readings->values();
        $count = $readings->count();

        if ($count <= self::SENSOR_CHART_MAX_POINTS) {
            return $readings;
        }

        $lastIndex = $count - 1;

        return collect(range(0, self::SENSOR_CHART_MAX_POINTS - 1))
            ->map(function (int $position) use ($readings, $lastIndex): SensorData {
                $index = (int) round($position * $lastIndex / (self::SENSOR_CHART_MAX_POINTS - 1));

                return $readings->get($index);
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function pumpStatus(): array
    {
        $manualControls = DeviceControl::manualSnapshot();
        $resolvedControls = DeviceControl::snapshot();
        $scheduleStatus = PumpSchedule::status();

        return [
            ...$scheduleStatus,
            'manual_active' => (bool) ($manualControls['pompa'] ?? false),
            'effective_active' => (bool) ($resolvedControls['pompa'] ?? false),
            'source' => ($manualControls['pompa'] ?? false)
                ? 'Manual'
                : ($scheduleStatus['automatic_active'] ? 'Otomatis' : 'OFF'),
        ];
    }

    /**
     * @param  array<int, mixed>  $days
     * @return array<int, int>
     */
    private function normalizeDays(array $days): array
    {
        return collect($days)
            ->map(fn (mixed $day): int => (int) $day)
            ->filter(fn (int $day): bool => array_key_exists($day, PumpSchedule::DAY_LABELS))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function minutesBetweenClockTimes(string $startTime, string $endTime): int
    {
        [$startHour, $startMinute] = array_map('intval', array_pad(explode(':', $startTime), 2, 0));
        [$endHour, $endMinute] = array_map('intval', array_pad(explode(':', $endTime), 2, 0));

        $start = ($startHour * 60) + $startMinute;
        $end = ($endHour * 60) + $endMinute;

        if ($end <= $start) {
            $end += 1440;
        }

        return $end - $start;
    }
}
