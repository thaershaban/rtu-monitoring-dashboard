<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RtuDataController; // تأكد من هذا السطر

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// المسار الذي يخدم الواجهة الأمامية (ملف Blade)
Route::get('/', function () {
    return view('dashboard');
});

// مسار الـ API الخاص ببيانات RTU (الآن في web.php)
Route::get('/rtu-data', [RtuDataController::class, 'index']); // IMPORTANT: This route is now directly at /rtu-data