<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lamp_schedules', function (Blueprint $table) {
            $table->time('end_time')->nullable()->after('start_time');
        });

        DB::table('lamp_schedules')
            ->whereNull('end_time')
            ->orderBy('id')
            ->get()
            ->each(function ($schedule): void {
                $startsAt = (string) $schedule->start_time;
                [$hour, $minute] = array_pad(explode(':', $startsAt), 2, 0);
                $duration = max(1, (int) $schedule->duration_minutes);
                $totalMinutes = (((int) $hour * 60) + (int) $minute + $duration) % 1440;

                DB::table('lamp_schedules')
                    ->where('id', $schedule->id)
                    ->update([
                        'end_time' => sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lamp_schedules', function (Blueprint $table) {
            $table->dropColumn('end_time');
        });
    }
};
