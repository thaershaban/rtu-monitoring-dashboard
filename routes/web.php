<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RtuDataController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes will handle both web pages and API-like requests
| for your Vue.js frontend.
|
*/

// Main Vue.js Application Route
Route::get('/', function () {
    return view('app'); // Make sure you have app.blade.php
})->name('home');

// API-like Routes for Vue.js Frontend
Route::prefix('rtu')->group(function () {
    // Get RTU Data
    Route::get('/data', [RtuDataController::class, 'index'])->name('rtu.data');
    
    // Export Routes
    Route::get('/export/csv', [RtuDataController::class, 'exportRtuData'])->name('rtu.export.csv');
    Route::get('/export/excel', [RtuDataController::class, 'exportRtuDataExcel'])->name('rtu.export.excel');
});