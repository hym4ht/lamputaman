<?php

namespace Tests\Feature;

use App\Models\DeviceControl;
use App\Models\PumpSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensor_endpoint_stores_reading(): void
    {
        $response = $this->postJson('/api/iot/sensor', [
            'suhu' => 28.5,
            'kelembaban' => 72.4,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.suhu', 28.5)
            ->assertJsonPath('data.kelembaban', 72.4);

        $this->assertDatabaseHas('sensor_data', [
            'suhu' => 28.5,
            'kelembaban' => 72.4,
        ]);
    }

    public function test_control_endpoint_returns_default_device_statuses(): void
    {
        DeviceControl::query()->create([
            'device_name' => 'pompa',
            'status' => true,
        ]);

        $this->getJson('/api/iot/control')
            ->assertOk()
            ->assertJson([
                'lampu1' => 0,
                'lampu2' => 0,
                'lampu3' => 0,
                'pompa' => 1,
            ]);
    }

    public function test_control_endpoint_turns_pump_on_when_schedule_is_active(): void
    {
        PumpSchedule::query()->create([
            'name' => 'Penyiraman pagi',
            'days' => [1],
            'start_time' => '06:00',
            'duration_minutes' => 10,
            'is_enabled' => true,
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-05-11 06:05:00', config('app.timezone')));

        $this->getJson('/api/iot/control')
            ->assertOk()
            ->assertJsonPath('pompa', 1);

        $this->travelTo(CarbonImmutable::parse('2026-05-11 06:11:00', config('app.timezone')));

        $this->getJson('/api/iot/control')
            ->assertOk()
            ->assertJsonPath('pompa', 0);
    }

    public function test_pump_schedule_can_cross_midnight(): void
    {
        PumpSchedule::query()->create([
            'name' => 'Penyiraman malam',
            'days' => [1],
            'start_time' => '23:30',
            'duration_minutes' => 60,
            'is_enabled' => true,
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-05-12 00:15:00', config('app.timezone')));

        $this->getJson('/api/iot/control')
            ->assertOk()
            ->assertJsonPath('pompa', 1);
    }

    public function test_iot_token_is_required_when_configured(): void
    {
        config(['services.iot.token' => 'secret-token']);

        $this->getJson('/api/iot/control')->assertUnauthorized();

        $this->getJson('/api/iot/control', [
            'X-IOT-TOKEN' => 'secret-token',
        ])->assertOk();
    }
}
