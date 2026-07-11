<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DeviceConnectionDetector
{
    public static function check(): void
    {
        $lastSeen = Cache::get('device_last_seen');
        $isCurrentlyConnected = false;

        if ($lastSeen) {
            // If seen within 130 seconds, we consider it connected
            $isCurrentlyConnected = Carbon::parse($lastSeen)->diffInSeconds(now()) < 130;
        }

        $lastConnectionStatus = Cache::get('device_connection_status_last');

        // If it's the very first check, initialize status in cache and return
        if ($lastConnectionStatus === null) {
            Cache::put('device_connection_status_last', $isCurrentlyConnected ? 'connected' : 'disconnected', 86400);
            return;
        }

        $firebase = app(FirebaseService::class);
        if (!$firebase->isConfigured()) {
            return;
        }

        if ($lastConnectionStatus === 'connected' && !$isCurrentlyConnected) {
            // Transition: Connected -> Disconnected
            $lastSeenLabel = $lastSeen 
                ? Carbon::parse($lastSeen)->timezone(config('app.timezone', 'Asia/Jakarta'))->format('H:i:s') 
                : 'Tidak diketahui';

            $title = "⚠️ Koneksi Alat Terputus";
            $body = "Alat NodeMCU tidak lagi terhubung ke server. Terakhir aktif: {$lastSeenLabel}.";

            $firebase->broadcast($title, $body, [
                'event' => 'device_disconnected',
                'last_seen' => $lastSeenLabel,
            ]);

            Cache::put('device_connection_status_last', 'disconnected', 86400);
        } elseif ($lastConnectionStatus === 'disconnected' && $isCurrentlyConnected) {
            // Transition: Disconnected -> Connected
            $title = "✅ Koneksi Alat Terhubung";
            $body = "Alat NodeMCU telah terhubung kembali ke server.";

            $firebase->broadcast($title, $body, [
                'event' => 'device_connected',
            ]);

            Cache::put('device_connection_status_last', 'connected', 86400);
        }
    }
}
