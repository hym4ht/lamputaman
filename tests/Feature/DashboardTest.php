<?php

namespace Tests\Feature;

use App\Models\DeviceControl;
use App\Models\PumpSchedule;
use App\Models\SensorData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_admin_can_view_responsive_dashboard_controls(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin Taman',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Admin Taman')
            ->assertSee('Ringkasan &amp; Grafik', false)
            ->assertSee('Suhu')
            ->assertSee('Kelembaban')
            ->assertSee('Grafik Sensor');
    }

    public function test_authenticated_admin_can_open_separated_dashboard_screens(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard/grafik')
            ->assertRedirect('/dashboard');

        $this->actingAs($user)
            ->get('/dashboard/kontrol')
            ->assertOk()
            ->assertSee('Kontrol Perangkat')
            ->assertSee('Relay Active Low');

        $this->actingAs($user)
            ->get('/dashboard/jadwal')
            ->assertOk()
            ->assertSee('Jadwal Otomatis')
            ->assertSee('Setiap hari');
    }

    public function test_authenticated_admin_can_update_control(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/dashboard/control/lampu1', ['status' => 1])
            ->assertOk()
            ->assertJsonPath('controls.lampu1', 1);

        $this->assertDatabaseHas('device_controls', [
            'device_name' => 'lampu1',
            'status' => true,
        ]);
    }

    public function test_dashboard_data_returns_latest_sensor_and_controls(): void
    {
        $user = User::factory()->create();

        SensorData::query()->create([
            'suhu' => 29.2,
            'kelembaban' => 80.1,
        ]);

        DeviceControl::query()->create([
            'device_name' => 'pompa',
            'status' => true,
        ]);

        $this->actingAs($user)
            ->getJson('/dashboard/data')
            ->assertOk()
            ->assertJsonPath('latest.suhu', 29.2)
            ->assertJsonPath('latest.kelembaban', 80.1)
            ->assertJsonPath('controls.pompa', 1);
    }

    public function test_dashboard_data_filters_and_limits_chart_readings_by_selected_range(): void
    {
        $user = User::factory()->create();
        $startTime = now()->subMinutes(30);

        $rows = collect(range(1, 100))->map(fn (int $index): array => [
            'suhu' => $index,
            'kelembaban' => 50 + $index,
            'created_at' => $startTime->copy()->addSeconds($index * 15),
        ])->all();

        DB::table('sensor_data')->insert($rows);

        $this->actingAs($user)
            ->getJson('/dashboard/data?sensor_range=25m')
            ->assertOk()
            ->assertJsonPath('sensor_range.key', '25m')
            ->assertJsonCount(80, 'readings')
            ->assertJsonPath('readings.79.suhu', 100);
    }

    public function test_dashboard_data_accepts_one_minute_chart_range(): void
    {
        $user = User::factory()->create();

        DB::table('sensor_data')->insert([
            [
                'suhu' => 25,
                'kelembaban' => 70,
                'created_at' => now()->subMinutes(2),
            ],
            [
                'suhu' => 26,
                'kelembaban' => 71,
                'created_at' => now()->subSeconds(30),
            ],
        ]);

        $this->actingAs($user)
            ->getJson('/dashboard/data?sensor_range=1m')
            ->assertOk()
            ->assertJsonPath('sensor_range.key', '1m')
            ->assertJsonCount(1, 'readings')
            ->assertJsonPath('readings.0.suhu', 26);
    }

    public function test_authenticated_admin_can_create_pump_schedule(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/dashboard/pump-schedules', [
                'name' => 'Penyiraman sore',
                'days' => [1, 3, 5],
                'start_time' => '16:30',
                'duration_minutes' => 15,
                'is_enabled' => 1,
            ])
            ->assertRedirect('/dashboard/jadwal');

        $this->assertDatabaseHas('pump_schedules', [
            'name' => 'Penyiraman sore',
            'start_time' => '16:30',
            'duration_minutes' => 15,
            'is_enabled' => true,
        ]);
    }

    public function test_authenticated_admin_can_toggle_and_delete_pump_schedule(): void
    {
        $user = User::factory()->create();
        $schedule = PumpSchedule::query()->create([
            'name' => 'Penyiraman tes',
            'days' => [1, 2, 3],
            'start_time' => '07:00',
            'duration_minutes' => 5,
            'is_enabled' => true,
        ]);

        $this->actingAs($user)
            ->patch("/dashboard/pump-schedules/{$schedule->id}", [
                'is_enabled' => 0,
            ])
            ->assertRedirect('/dashboard/jadwal');

        $this->assertDatabaseHas('pump_schedules', [
            'id' => $schedule->id,
            'is_enabled' => false,
        ]);

        $this->actingAs($user)
            ->delete("/dashboard/pump-schedules/{$schedule->id}")
            ->assertRedirect('/dashboard/jadwal');

        $this->assertDatabaseMissing('pump_schedules', [
            'id' => $schedule->id,
        ]);
    }
}
