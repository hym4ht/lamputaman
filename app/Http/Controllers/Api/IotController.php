<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceControl;
use App\Models\SensorData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IotController extends Controller
{
    public function storeSensor(Request $request): JsonResponse
    {
        $this->authorizeDevice($request);
        \App\Services\DeviceConnectionDetector::check();

        // Check if sensor is reported as null (broken sensor)
        if ($request->has('suhu') && $request->has('kelembaban') &&
            ($request->json('suhu') === null || $request->json('kelembaban') === null)) {
            
            $this->notifySensorBroken();

            return response()->json([
                'message' => 'Status sensor rusak berhasil diterima.',
            ], 200);
        }

        // Clear broken sensor alert cache when valid reading is received
        Cache::forget('fcm_notify_sensor_broken');

        $validated = $request->validate([
            'suhu' => ['required', 'numeric'],
            'kelembaban' => ['required', 'numeric', 'between:0,100'],
        ]);

        $sensorData = SensorData::query()->create([
            'suhu' => $validated['suhu'],
            'kelembaban' => $validated['kelembaban'],
        ]);

        // Check sensor thresholds and notify if values are extreme
        $this->checkSensorThresholdsAndNotify((float) $sensorData->suhu, (float) $sensorData->kelembaban);

        return response()->json([
            'message' => 'Data sensor berhasil disimpan.',
            'data' => [
                'id' => $sensorData->id,
                'suhu' => round($sensorData->suhu, 1),
                'kelembaban' => round($sensorData->kelembaban, 1),
                'created_at' => $sensorData->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function control(Request $request): JsonResponse
    {
        $this->authorizeDevice($request);
        \App\Services\DeviceConnectionDetector::check();

        $snapshot = DeviceControl::snapshot();

        // Inject smart watering flag so IoT knows to run the pump
        $snapshot['smart_watering'] = (int) (bool) Cache::get('smart_watering_enabled', false);

        // Check state transitions for notifications
        $this->detectAndNotifyTransitions($snapshot);

        return response()->json($snapshot);
    }

    /**
     * GET /api/iot/smart-watering
     * IoT polls this endpoint to check if smart watering is enabled from the web dashboard.
     */
    public function smartWateringStatus(Request $request): JsonResponse
    {
        $this->authorizeDevice($request);

        $enabled = (bool) Cache::get('smart_watering_enabled', false);

        return response()->json([
            'smart_watering' => (int) $enabled,
            'message' => $enabled ? 'Smart Watering aktif – pompa harus menyala.' : 'Smart Watering nonaktif.',
        ]);
    }

    /**
     * POST /api/iot/smart-watering
     * IoT reports that it has executed (or stopped) the smart watering pump.
     * Body: { "active": 1|0 }
     */
    public function smartWateringReport(Request $request): JsonResponse
    {
        $this->authorizeDevice($request);

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $validated['active'];

        // Store the reported pump state so the dashboard can display it
        Cache::put('smart_watering_pump_active', $isActive, 300); // 5 min TTL

        // If the IoT reports pump is now ON due to smart watering, reflect that in DeviceControl
        if ($isActive) {
            $control = DeviceControl::query()->firstOrCreate(['device_name' => 'pompa']);
            // Only override manual if smart watering triggered it
            // We do NOT persist to DB – just set via smart_watering_pump_active cache above.
            // The dashboard reads the cache flag to show status.
        }

        return response()->json([
            'message' => $isActive ? 'Laporan pompa Smart Watering AKTIF diterima.' : 'Laporan pompa Smart Watering MATI diterima.',
            'active' => (int) $isActive,
        ]);
    }

    public function registerFcmToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $tokens = Cache::get('fcm_tokens', []);
        if (!in_array($validated['token'], $tokens, true)) {
            $tokens[] = $validated['token'];
            Cache::put('fcm_tokens', $tokens, 86400 * 30); // Store for 30 days
        }

        return response()->json([
            'message' => 'Token FCM berhasil didaftarkan.',
        ]);
    }

    private function detectAndNotifyTransitions(array $currentSnapshot): void
    {
        $lastSnapshot = Cache::get('device_state_last');

        // If there's no last snapshot, store current state and skip to avoid spam
        if ($lastSnapshot === null) {
            Cache::put('device_state_last', $currentSnapshot, 3600);
            return;
        }

        $firebase = app(\App\Services\FirebaseService::class);
        if (!$firebase->isConfigured()) {
            Cache::put('device_state_last', $currentSnapshot, 3600);
            return;
        }

        // Check lamps
        $lampNames = ['lampu1', 'lampu2', 'lampu3'];
        $activeSchedules = \App\Models\LampSchedule::activeAt();

        foreach ($lampNames as $lampKey) {
            $lastState = (int) ($lastSnapshot[$lampKey] ?? 0);
            $currentState = (int) ($currentSnapshot[$lampKey] ?? 0);

            // Transition from OFF (0) to ON (1)
            if ($lastState === 0 && $currentState === 1) {
                // Find matching schedule
                $matchingSchedule = null;
                foreach ($activeSchedules as $schedule) {
                    if (in_array($lampKey, $schedule->deviceNames(), true)) {
                        $matchingSchedule = $schedule;
                        break;
                    }
                }

                $lampLabel = DeviceControl::LAMP_DEVICES[$lampKey] ?? ucfirst($lampKey);

                if ($matchingSchedule) {
                    $title = "💡 {$lampLabel} Menyala Otomatis";
                    $body = "{$lampLabel} telah menyala secara otomatis sesuai jadwal \"{$matchingSchedule->name}\".";
                } else {
                    $title = "💡 {$lampLabel} Menyala";
                    $body = "{$lampLabel} telah dinyalakan secara manual.";
                }

                $firebase->broadcast($title, $body, [
                    'device' => $lampKey,
                    'status' => '1',
                    'triggered_by' => $matchingSchedule ? 'schedule' : 'manual',
                ]);
            }
        }

        // Check pump
        $lastPumpState = (int) ($lastSnapshot['pompa'] ?? 0);
        $currentPumpState = (int) ($currentSnapshot['pompa'] ?? 0);

        if ($lastPumpState === 0 && $currentPumpState === 1) {
            $activePumpSchedule = \App\Models\PumpSchedule::activeAt();

            if ($activePumpSchedule) {
                $title = "💧 Pompa Menyala Otomatis";
                $body = "Pompa penyiraman telah menyala secara otomatis sesuai jadwal \"{$activePumpSchedule->name}\".";
            } else {
                $title = "💧 Pompa Menyala";
                $body = "Pompa penyiraman telah dinyalakan secara manual.";
            }

            $firebase->broadcast($title, $body, [
                'device' => 'pompa',
                'status' => '1',
                'triggered_by' => $activePumpSchedule ? 'schedule' : 'manual',
            ]);
        }

        Cache::put('device_state_last', $currentSnapshot, 3600);
    }

    private function checkSensorThresholdsAndNotify(float $suhu, float $kelembaban): void
    {
        $firebase = app(\App\Services\FirebaseService::class);
        if (!$firebase->isConfigured()) {
            return;
        }

        $tempHotLimit = (float) env('SENSOR_TEMP_HOT', 35.0);
        $humidityLowLimit = (float) env('SENSOR_HUMIDITY_LOW', 45.0);
        $humidityHighLimit = (float) env('SENSOR_HUMIDITY_HIGH', 85.0);

        // 1. Check Temperature
        if ($suhu >= $tempHotLimit) {
            $cacheKey = 'fcm_notify_temp_hot';
            if (!Cache::has($cacheKey)) {
                $firebase->broadcast(
                    "⚠️ Suhu Taman Terlalu Panas",
                    "Peringatan: Suhu saat ini mencapai {$suhu}°C (melebihi batas aman {$tempHotLimit}°C)."
                );
                Cache::put($cacheKey, true, 3600); // Throttle 1 hour
            }
        }

        // 2. Check Humidity Low
        if ($kelembaban <= $humidityLowLimit) {
            $cacheKey = 'fcm_notify_humidity_low';
            if (!Cache::has($cacheKey)) {
                $firebase->broadcast(
                    "⚠️ Kelembaban Taman Terlalu Rendah",
                    "Peringatan: Kelembaban saat ini {$kelembaban}% (di bawah batas aman {$humidityLowLimit}%). Tanaman membutuhkan penyiraman."
                );
                Cache::put($cacheKey, true, 3600);
            }
        }
        // 3. Check Humidity High
        elseif ($kelembaban >= $humidityHighLimit) {
            $cacheKey = 'fcm_notify_humidity_high';
            if (!Cache::has($cacheKey)) {
                $firebase->broadcast(
                    "⚠️ Kelembaban Taman Terlalu Tinggi",
                    "Peringatan: Kelembaban saat ini {$kelembaban}% (melebihi batas {$humidityHighLimit}%)."
                );
                Cache::put($cacheKey, true, 3600);
            }
        }
    }

    private function authorizeDevice(Request $request): void
    {
        $token = config('services.iot.token');

        if ($token) {
            $incomingToken = $request->header('X-IOT-TOKEN', $request->query('token'));
            abort_unless(hash_equals($token, (string) $incomingToken), 401, 'Token IoT tidak valid.');
        }

        Cache::put('device_last_seen', now()->toIso8601String(), 120);
    }

    private function notifySensorBroken(): void
    {
        $firebase = app(\App\Services\FirebaseService::class);
        if (!$firebase->isConfigured()) {
            return;
        }

        $cacheKey = 'fcm_notify_sensor_broken';
        if (!Cache::has($cacheKey)) {
            $firebase->broadcast(
                "⚠️ Peringatan: Sensor DHT Rusak / Terputus",
                "Sistem mendeteksi bahwa sensor suhu dan kelembaban (DHT) pada alat NodeMCU mengalami kegagalan pembacaan atau terputus."
            );
            Cache::put($cacheKey, true, 3600); // Throttle 1 hour
        }
    }
}
