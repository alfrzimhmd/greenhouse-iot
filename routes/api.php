<?php

use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\ControlController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('sensor')->group(function () {
    Route::get('/latest', [SensorController::class, 'latest']);
    Route::get('/history', [SensorController::class, 'history']);
    Route::get('/stats', [SensorController::class, 'stats']);
});

Route::prefix('control')->group(function () {
    Route::post('/', [ControlController::class, 'send']);
    Route::get('/history', [ControlController::class, 'history']);
});