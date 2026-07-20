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
use Illuminate\Support\Facades\Cache;
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

    public function exportPdf(Request $request)
    {
        // Increase memory and time limits temporarily for PDF generation
        ini_set('memory_limit', '256M');
        set_time_limit(120);

        $range = $request->query('range', 'weekly');
        $query = SensorData::query()->toBase()->select('suhu', 'kelembaban', 'created_at');

        $title = 'Laporan Data Sensor';
        $periodLabel = '';

        if ($range === 'weekly') {
            $startDate = now()->subDays(7);
            $query->where('created_at', '>=', $startDate);
            $title = 'Laporan Mingguan Data Sensor';
            $periodLabel = $startDate->format('d M Y') . ' - ' . now()->format('d M Y');
        } elseif ($range === 'monthly') {
            $startDate = now()->subDays(30);
            $query->where('created_at', '>=', $startDate);
            $title = 'Laporan Bulanan Data Sensor';
            $periodLabel = $startDate->format('d M Y') . ' - ' . now()->format('d M Y');
        } else {
            $title = 'Laporan Keseluruhan Data Sensor';
            $firstRecord = SensorData::query()->orderBy('created_at', 'asc')->first();
            $startLabel = $firstRecord && $firstRecord->created_at ? \Illuminate\Support\Carbon::parse($firstRecord->created_at)->format('d M Y') : now()->format('d M Y');
            $periodLabel = $startLabel . ' - ' . now()->format('d M Y');
        }

        // Fetch records ordered by created_at desc (newest first)
        $allReadings = $query->orderBy('created_at', 'desc')->get();

        // Calculate statistics based on the full dataset in the range
        $totalCount = $allReadings->count();
        
        $avgSuhu = $totalCount > 0 ? round($allReadings->avg('suhu'), 1) : 0;
        $avgKelembaban = $totalCount > 0 ? round($allReadings->avg('kelembaban'), 1) : 0;
        
        $minSuhu = $totalCount > 0 ? round($allReadings->min('suhu'), 1) : 0;
        $maxSuhu = $totalCount > 0 ? round($allReadings->max('suhu'), 1) : 0;
        
        $minKelembaban = $totalCount > 0 ? round($allReadings->min('kelembaban'), 1) : 0;
        $maxKelembaban = $totalCount > 0 ? round($allReadings->max('kelembaban'), 1) : 0;

        // Sample the data to prevent memory issues (max 500 rows in PDF table)
        $maxPoints = 500;
        if ($totalCount > $maxPoints) {
            $step = (int) ceil($totalCount / $maxPoints);
            $readings = $allReadings->filter(function ($value, $key) use ($step) {
                return $key % $step === 0;
            })->values();
            $sampled = true;
        } else {
            $readings = $allReadings;
            $sampled = false;
        }

        // Sort ascending for chronological view
        // Since $readings are standard objects, we convert created_at to Carbon when displaying
        $readings = $readings->map(function ($r) {
            $r->created_at_parsed = \Illuminate\Support\Carbon::parse($r->created_at);
            return $r;
        })->sortBy('created_at_parsed');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report', [
            'title' => $title,
            'periodLabel' => $periodLabel,
            'readings' => $readings,
            'totalCount' => $totalCount,
            'renderedCount' => $readings->count(),
            'avgSuhu' => $avgSuhu,
            'avgKelembaban' => $avgKelembaban,
            'minSuhu' => $minSuhu,
            'maxSuhu' => $maxSuhu,
            'minKelembaban' => $minKelembaban,
            'maxKelembaban' => $maxKelembaban,
            'range' => $range,
            'sampled' => $sampled,
        ]);

        $filename = 'laporan_sensor_' . $range . '_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($filename);
    }


    public function index(): View
    {
        DeviceControl::ensureDefaults();
        $screen = request()->route('screen', 'ringkasan');
        $screen = in_array($screen, ['ringkasan', 'kontrol', 'jadwal'], true)
            ? $screen
            : 'ringkasan';

        $latest = SensorData::query()->latest('created_at')->first();
        $connection = $this->deviceConnectionStatus($latest);

        return view('dashboard.index', [
            'activeScreen' => $screen,
            'controls' => DeviceControl::snapshot(),
            'manualControls' => DeviceControl::manualSnapshot(),
            'devices' => DeviceControl::DEVICES,
            'lampDevices' => DeviceControl::LAMP_DEVICES,
            'dayLabels' => PumpSchedule::DAY_LABELS,
            'lampTargets' => LampSchedule::TARGET_LABELS,
            'latest' => $latest,
            'pumpSchedules' => PumpSchedule::query()->orderBy('start_time')->get(),
            'pumpStatus' => $this->pumpStatus(),
            'lampSchedules' => LampSchedule::query()->orderBy('start_time')->get(),
            'lampStatus' => LampSchedule::status(),
            'deviceConnected' => $connection['device_connected'],
            'lastSeen' => $connection['last_seen_object'],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        \App\Services\DeviceConnectionDetector::check();

        $sensorRange = $this->sensorRange($request);
        $connection = $this->deviceConnectionStatus();

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
            'device_connected' => $connection['device_connected'],
            'device_last_seen' => $connection['device_last_seen'],
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

        $connection = $this->deviceConnectionStatus();

        return response()->json([
            'device' => $device,
            'label' => DeviceControl::DEVICES[$device],
            'status' => (int) $control->status,
            'controls' => DeviceControl::snapshot(),
            'manual_controls' => DeviceControl::manualSnapshot(),
            'pump' => $this->pumpStatus(),
            'lamp' => LampSchedule::status(),
            'device_connected' => $connection['device_connected'],
            'device_last_seen' => $connection['device_last_seen'],
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

        $connection = $this->deviceConnectionStatus();

        return response()->json([
            'controls' => DeviceControl::snapshot(),
            'manual_controls' => DeviceControl::manualSnapshot(),
            'pump' => $this->pumpStatus(),
            'lamp' => LampSchedule::status(),
            'device_connected' => $connection['device_connected'],
            'device_last_seen' => $connection['device_last_seen'],
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

        // Map IoT raw status to display labels (FULL → Tinggi, SEDANG → Sedang, HABIS → Rendah)
        $statusAirRaw = $latest->status_air ? strtoupper($latest->status_air) : null;
        $statusAirLabel = match ($statusAirRaw) {
            'FULL'        => 'Tinggi',
            'SEDANG'      => 'Sedang',
            'HABIS'       => 'Rendah',
            'TIDAK TERBACA' => 'Tidak Terbaca',
            default       => null,
        };

        return [
            'suhu'           => round($latest->suhu, 1),
            'kelembaban'     => round($latest->kelembaban, 1),
            'jarak_air'      => $latest->jarak_air ? round($latest->jarak_air, 1) : null,
            'status_air'     => $statusAirRaw,
            'status_air_label' => $statusAirLabel,
            'created_at'     => $latest->created_at?->toIso8601String(),
            'label'          => $latest->created_at?->timezone(config('app.timezone'))->format('d M Y H:i:s'),
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
        $smartWateringActive = (bool) \Illuminate\Support\Facades\Cache::get('smart_watering_active', false);

        // Determine source label
        if ($manualControls['pompa'] ?? false) {
            $source = 'Manual';
        } elseif ($scheduleStatus['automatic_active']) {
            $source = 'Otomatis';
        } elseif ($smartWateringActive) {
            $source = 'Smart Watering';
        } else {
            $source = 'OFF';
        }

        return [
            ...$scheduleStatus,
            'manual_active' => (bool) ($manualControls['pompa'] ?? false),
            'effective_active' => (bool) ($resolvedControls['pompa'] ?? false),
            'smart_watering_active' => $smartWateringActive,
            'source' => $source,
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

    /**
     * Get device connection status based on the latest activity timestamp (DB or Cache).
     * If the most recent activity is older than the timeout threshold → TERPUTUS.
     *
     * @return array{device_connected: bool, device_last_seen: ?string, last_seen_object: ?\Illuminate\Support\Carbon}
     */
    private function deviceConnectionStatus(?SensorData $latest = null): array
    {
        $latest = $latest ?: SensorData::query()->latest('created_at')->first();
        $cacheLastSeen = Cache::get('device_last_seen');

        $lastSeenCarbon = null;
        if ($cacheLastSeen) {
            try {
                $parsed = \Illuminate\Support\Carbon::parse($cacheLastSeen);
                $lastSeenCarbon = $parsed;
            } catch (\Throwable $e) {
                $lastSeenCarbon = null;
            }
        }

        if ($latest?->created_at) {
            if (! $lastSeenCarbon || $latest->created_at->greaterThan($lastSeenCarbon)) {
                $lastSeenCarbon = $latest->created_at;
            }
        }

        $timeout = (int) config('firebase.device_connection_timeout', 10);
        $deviceConnected = false;

        if ($lastSeenCarbon) {
            $diff = abs(now()->diffInSeconds($lastSeenCarbon));
            $deviceConnected = ($diff <= $timeout);
        }

        return [
            'device_connected' => $deviceConnected,
            'device_last_seen' => $lastSeenCarbon
                ? $lastSeenCarbon->timezone(config('app.timezone', 'Asia/Jakarta'))->format('H:i:s')
                : null,
            'last_seen_object' => $lastSeenCarbon,
        ];
    }
}

