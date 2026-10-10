<?php

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\OccupantController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('/tenants', [TenantController::class, 'index']);
Route::post('/tenants', [TenantController::class, 'store']);
Route::put('/tenants/{tenant}', [TenantController::class, 'update']);
Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy']);

Route::middleware('tenant')->group(function () {
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::put('/rooms/{room}', [RoomController::class, 'update']);
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

    Route::get('/children', [ChildController::class, 'index']);
    Route::post('/children', [ChildController::class, 'store']);
    Route::put('/children/{child}', [ChildController::class, 'update']);
    Route::delete('/children/{child}', [ChildController::class, 'destroy']);

    Route::post('/rooms/{room}/check-ins', [CheckInController::class, 'store']);
    Route::post('/check-ins/{id}/check-out', [CheckInController::class, 'checkOut']);
    Route::get('/rooms/{room}/occupants', [OccupantController::class, 'index']);
});
