<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\FileTrashController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SharedByMeController;
use App\Http\Controllers\SharedForMeController;
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

Route::middleware(['auth', 'verified'])->group(static function () {
    Route::get('/dashboard', static function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::controller(FileController::class)
        ->prefix('/file')
        ->name('file.')
        ->group(function () {
            Route::get('/{parentFolder?}', 'index')->name('index');
            Route::post('/create/{parentFolder?}', 'create')->name('create');
            Route::post('/upload/{parentFolder?}', 'upload')->name('upload');
            Route::delete('/destroy/{parentFolder?}', 'destroy')->name('destroy');
            Route::get('/download/{parentFolder}', 'download')->name('download');
            Route::patch('/favorite', 'favorite')->name('favorite');
            Route::post('/share/{parentFolder?}', 'share')->name('share');
        });

    Route::controller(FileTrashController::class)
        ->prefix('/trash')
        ->name('trash.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/restore', 'restore')->name('restore');
            Route::delete('/destroy', 'destroy')->name('destroy');
        });

    Route::controller(SharedByMeController::class)
        ->prefix('/share-by-me')
        ->name('share_by_me.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::delete('/unshare', 'unshare')->name('unshare');
            Route::get('/download', 'download')->name('download');
        });

    Route::controller(SharedForMeController::class)
        ->prefix('/share-for-me')
        ->name('share_for_me.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/download', 'download')->name('download');
        });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
