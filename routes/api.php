<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FieldAuthController;
use App\Http\Controllers\Api\FieldMeterReadingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('field')->group(function () {
    Route::post('/login', [FieldAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [FieldAuthController::class, 'logout']);
        Route::get('/bootstrap', [FieldMeterReadingController::class, 'bootstrap']);
        Route::get('/customers', [FieldMeterReadingController::class, 'customers']);
        Route::post('/readings/sync', [FieldMeterReadingController::class, 'sync']);
    });
});
