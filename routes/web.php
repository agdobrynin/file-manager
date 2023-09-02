<?php

use App\Helpers\PhpConfig;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Inertia::share('upload', static fn() => [
    'maxUploadFiles' => PhpConfig::maxUploadFiles(),
    'maxUploadFileBytes' => PhpConfig::maxUploadFileBytes(),
    'maxPostBytes' => PhpConfig::maxPostBytes(),
]);

Route::middleware(['auth', 'verified'])->group(static function () {
    Route::get('/dashboard', static function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/my-files/{parentFolder?}', [FileController::class, 'myFiles'])
        ->name('my.files');

    Route::post('/folder/create/{parentFolder?}', [FileController::class, 'createFolder'])
        ->name('folder.create');

    Route::post('/file/{parentFolder?}', [FileController::class, 'upload'])
        ->name('file.upload');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
