<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->defaults('screen', 'ringkasan')
        ->name('dashboard');
    Route::redirect('/dashboard/grafik', '/dashboard')->name('dashboard.grafik');
    Route::get('/dashboard/kontrol', [DashboardController::class, 'index'])
        ->defaults('screen', 'kontrol')
        ->name('dashboard.kontrol');
    Route::get('/dashboard/jadwal', [DashboardController::class, 'index'])
        ->defaults('screen', 'jadwal')
        ->name('dashboard.jadwal');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::patch('/dashboard/control/{device}', [DashboardController::class, 'updateControl'])->name('dashboard.control.update');
    Route::patch('/dashboard/control-lamps', [DashboardController::class, 'updateLampControls'])->name('dashboard.control.lamps.update');
    Route::post('/dashboard/pump-schedules', [DashboardController::class, 'storePumpSchedule'])->name('dashboard.pump-schedules.store');
    Route::patch('/dashboard/pump-schedules/{pumpSchedule}', [DashboardController::class, 'togglePumpSchedule'])->name('dashboard.pump-schedules.toggle');
    Route::delete('/dashboard/pump-schedules/{pumpSchedule}', [DashboardController::class, 'destroyPumpSchedule'])->name('dashboard.pump-schedules.destroy');
    Route::post('/dashboard/lamp-schedules', [DashboardController::class, 'storeLampSchedule'])->name('dashboard.lamp-schedules.store');
    Route::patch('/dashboard/lamp-schedules/{lampSchedule}', [DashboardController::class, 'toggleLampSchedule'])->name('dashboard.lamp-schedules.toggle');
    Route::delete('/dashboard/lamp-schedules/{lampSchedule}', [DashboardController::class, 'destroyLampSchedule'])->name('dashboard.lamp-schedules.destroy');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
