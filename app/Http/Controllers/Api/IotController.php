<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceControl;
use App\Models\SensorData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IotController extends Controller
{
    public function storeSensor(Request $request): JsonResponse
    {
        $this->authorizeDevice($request);

        $validated = $request->validate([
            'suhu' => ['required', 'numeric'],
            'kelembaban' => ['required', 'numeric', 'between:0,100'],
        ]);

        $sensorData = SensorData::query()->create([
            'suhu' => $validated['suhu'],
            'kelembaban' => $validated['kelembaban'],
        ]);

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

        return response()->json(DeviceControl::snapshot());
    }

    private function authorizeDevice(Request $request): void
    {
        $token = config('services.iot.token');

        if (! $token) {
            return;
        }

        $incomingToken = $request->header('X-IOT-TOKEN', $request->query('token'));

        abort_unless(hash_equals($token, (string) $incomingToken), 401, 'Token IoT tidak valid.');
    }
}
