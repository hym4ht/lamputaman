<?php

use App\Http\Controllers\Api\IotController;
use Illuminate\Support\Facades\Route;

Route::post('/iot/sensor', [IotController::class, 'storeSensor'])->name('api.iot.sensor');
Route::get('/iot/control', [IotController::class, 'control'])->name('api.iot.control');
Route::get('/iot/smart-watering', [IotController::class, 'smartWateringStatus'])->name('api.iot.smart-watering.status');
Route::post('/iot/smart-watering', [IotController::class, 'smartWateringReport'])->name('api.iot.smart-watering.report');
Route::post('/fcm/register', [IotController::class, 'registerFcmToken'])->name('api.fcm.register');
