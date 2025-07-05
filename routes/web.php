<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RtuDataController; // تأكد من استيراد وحدة التحكم

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('dashboard');
});

Route::get('/rtu-data', [RtuDataController::class, 'index']);

// مسار تصدير CSV (يمكنك الاحتفاظ به أو حذفه)
// ملاحظة: إذا كانت دالة تصدير CSV في RtuDataController اسمها 'export' فقط، فهذا صحيح.
// إذا كان اسمها 'exportRtuData'، فيجب تعديلها هنا أيضًا.
// بناءً على الكود السابق، اسمها 'exportRtuData'. لذا سأقوم بتصحيحها.
Route::get('/export-rtu-data', [RtuDataController::class, 'exportRtuData']);

// **مسار تصدير بيانات RTU إلى Excel (تم تصحيح اسم الدالة)**
Route::get('/export-rtu-data-excel', [RtuDataController::class, 'exportRtuDataExcel']);

