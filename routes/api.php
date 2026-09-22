<?php

use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\ControlController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ActivityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============ SENSOR ============
Route::prefix('sensor')->group(function () {
    Route::get('/latest', [SensorController::class, 'latest']);
    Route::get('/history', [SensorController::class, 'history']);
    Route::get('/stats', [SensorController::class, 'stats']);
});

// ============ CONTROL ============
Route::prefix('control')->group(function () {
    Route::post('/', [ControlController::class, 'send']);
    Route::get('/history', [ControlController::class, 'history']);
});

// ============ SCHEDULE ============
Route::prefix('schedule')->group(function () {
    Route::get('/', [ScheduleController::class, 'index']);
    Route::post('/', [ScheduleController::class, 'store']);
    Route::put('/{id}', [ScheduleController::class, 'update']);
    Route::delete('/{id}', [ScheduleController::class, 'destroy']);
    Route::post('/sync', [ScheduleController::class, 'sync']);
});

// ============ ACTIVITY ============
Route::prefix('activity')->group(function () {
    Route::get('/', [ActivityController::class, 'index']);
    Route::get('/latest', [ActivityController::class, 'latest']);
    Route::get('/stats', [ActivityController::class, 'stats']);
    Route::delete('/clear', [ActivityController::class, 'clear']);
});