<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EspController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\KontrolMesinController;
use App\Http\Controllers\Api\MappingJoController;
use App\Http\Controllers\Api\ProduksiController;
use App\Http\Controllers\Api\TroubleController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.summary');
Route::get('operators', [ProduksiController::class, 'operators'])->name('operators.index');

Route::middleware('esp')->prefix('esp')->name('esp.')->group(function () {
    Route::post('ping', [EspController::class, 'ping'])->name('ping');
    Route::get('state', [EspController::class, 'state'])->name('state');
    Route::post('status', [EspController::class, 'status'])->name('status');
});

Route::prefix('mesin')->name('mesin.')->controller(KontrolMesinController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{mesin}', 'show')->name('show');
    Route::put('{mesin}/status', 'updateStatus')->name('status');
    Route::post('{mesin}/on', 'on')->name('on');
    Route::post('{mesin}/off', 'off')->name('off');
    Route::post('{mesin}/toggle', 'toggle')->name('toggle');
    Route::put('{mesin}/esp', 'assignEsp')->name('esp');
    Route::get('{mesin}/jo', 'jo')->name('jo');
    Route::get('{mesin}/history', 'history')->name('history');
});

Route::prefix('mesin/{mesin}')->name('mesin.')->group(function () {
    Route::post('start', [ProduksiController::class, 'start'])->name('start');
    Route::post('stop', [ProduksiController::class, 'stop'])->name('stop');
    Route::post('jo', [MappingJoController::class, 'store'])->name('jo.store');
    Route::put('jo/{noJo}', [MappingJoController::class, 'update'])->name('jo.update');
    Route::delete('jo/{noJo}', [MappingJoController::class, 'destroy'])->name('jo.destroy');
});

Route::get('history', [HistoryController::class, 'index'])->name('history.index');
Route::get('history/{history}', [HistoryController::class, 'show'])->name('history.show');

Route::get('trouble-items', [TroubleController::class, 'items'])->name('trouble-items.index');
Route::get('trouble-logs', [TroubleController::class, 'logs'])->name('trouble-logs.index');
