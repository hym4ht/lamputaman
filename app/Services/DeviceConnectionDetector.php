<?php

namespace App\Services;

use App\Models\SensorData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DeviceConnectionDetector
{
    public static function check(): void
    {
        $cacheLastSeen = Cache::get('device_last_seen');
        if ($cacheLastSeen instanceof \__PHP_Incomplete_Class) {
            $cacheLastSeen = null;
        }

        $latestSensor = SensorData::query()->latest('created_at')->first();

        $lastSeenCarbon = null;
        if ($cacheLastSeen) {
            try {
                $lastSeenCarbon = Carbon::parse($cacheLastSeen);
            } catch (\Throwable $e) {
                $lastSeenCarbon = null;
            }
        }

        if ($latestSensor?->created_at) {
            if (! $lastSeenCarbon || $latestSensor->created_at->greaterThan($lastSeenCarbon)) {
                $lastSeenCarbon = $latestSensor->created_at;
            }
        }

        $timeout = (int) config('firebase.device_connection_timeout', 10);
        $isCurrentlyConnected = false;

        if ($lastSeenCarbon) {
            $isCurrentlyConnected = abs(now()->diffInSeconds($lastSeenCarbon)) <= $timeout;
        }

        $lastConnectionStatus = Cache::get('device_connection_status_last');

        // If it's the very first check, initialize status in cache and return
        if ($lastConnectionStatus === null) {
            Cache::put('device_connection_status_last', $isCurrentlyConnected ? 'connected' : 'disconnected', 86400);
            return;
        }

        $firebase = app(FirebaseService::class);
        if (! $firebase->isConfigured()) {
            return;
        }

        if ($lastConnectionStatus === 'connected' && ! $isCurrentlyConnected) {
            // Transition: Connected -> Disconnected
            $lastSeenLabel = $lastSeenCarbon
                ? $lastSeenCarbon->timezone(config('app.timezone', 'Asia/Jakarta'))->format('H:i:s')
                : 'Tidak diketahui';

            $title = '⚠️ Koneksi Alat Terputus';
            $body = "Alat NodeMCU tidak lagi terhubung ke server. Terakhir aktif: {$lastSeenLabel}.";

            $firebase->broadcast($title, $body, [
                'event' => 'device_disconnected',
                'last_seen' => $lastSeenLabel,
            ]);

            Cache::put('device_connection_status_last', 'disconnected', 86400);
        } elseif ($lastConnectionStatus === 'disconnected' && $isCurrentlyConnected) {
            // Transition: Disconnected -> Connected
            $title = '✅ Koneksi Alat Terhubung';
            $body = 'Alat NodeMCU telah terhubung kembali ke server.';

            $firebase->broadcast($title, $body, [
                'event' => 'device_connected',
            ]);

            Cache::put('device_connection_status_last', 'connected', 86400);
        }
    }
}

