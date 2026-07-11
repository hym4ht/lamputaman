<?php

use App\Http\Controllers\Api\IotController;
use Illuminate\Support\Facades\Route;

Route::post('/iot/sensor', [IotController::class, 'storeSensor'])->name('api.iot.sensor');
Route::get('/iot/control', [IotController::class, 'control'])->name('api.iot.control');
Route::post('/fcm/register', [IotController::class, 'registerFcmToken'])->name('api.fcm.register');
