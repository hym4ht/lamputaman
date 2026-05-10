<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lamp_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Jadwal Lampu');
            $table->string('target')->default('all_lamps');
            $table->json('days');
            $table->time('start_time');
            $table->unsignedSmallInteger('duration_minutes');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lamp_schedules');
    }
};
