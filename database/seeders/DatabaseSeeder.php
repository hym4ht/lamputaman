<?php

namespace Database\Seeders;

use App\Models\DeviceControl;
use App\Models\PumpSchedule;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        ], [
            'name' => env('ADMIN_NAME', 'Admin Smart Garden'),
            'password' => env('ADMIN_PASSWORD', 'password'),
        ]);

        DeviceControl::ensureDefaults();

        PumpSchedule::query()->firstOrCreate([
            'name' => 'Penyiraman pagi',
        ], [
            'days' => [1, 2, 3, 4, 5, 6, 7],
            'start_time' => '06:00',
            'duration_minutes' => 10,
            'is_enabled' => false,
        ]);
    }
}
