<?php

use Illuminate\Support\Facades\Route;

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
    return redirect('/admin');
});

Route::get('/field-app', function () {
    return response()
        ->view('field-app')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
})->name('field.app');

// تصدير قالب سجلات الاستخدام للعملاء
Route::get('/customers/readings-template/export', \App\Http\Controllers\CustomerReadingTemplateExportController::class)
    ->name('customers.readings-template.export')
    ->middleware(['web', 'auth']);

Route::get('/admin/database-backup/download', \App\Http\Controllers\DatabaseBackupController::class)
    ->name('admin.database-backup.download')
    ->middleware(['web', 'auth']);
