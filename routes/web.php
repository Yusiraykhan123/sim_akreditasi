<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/elemen', [ElementController::class, 'index'])->name('elements.index');
    Route::post('/elemen/{element}/dokumen', [ElementController::class, 'storeDocument'])->name('elements.documents.store');
    Route::middleware('can:manage-elements')->group(function () {
        Route::get('/elemen/tambah', [ElementController::class, 'create'])->name('elements.create');
        Route::post('/elemen', [ElementController::class, 'store'])->name('elements.store');
        Route::get('/elemen/{element}/ubah', [ElementController::class, 'edit'])->name('elements.edit');
        Route::put('/elemen/{element}', [ElementController::class, 'update'])->name('elements.update');
        Route::delete('/elemen/{element}', [ElementController::class, 'destroy'])->name('elements.destroy');
    });
    Route::middleware('can:manage-units')->group(function () {
        Route::get('/unit', [UnitController::class, 'index'])->name('units.index');
        Route::get('/unit/tambah', [UnitController::class, 'create'])->name('units.create');
        Route::post('/unit', [UnitController::class, 'store'])->name('units.store');
        Route::get('/unit/{unit}/ubah', [UnitController::class, 'edit'])->name('units.edit');
        Route::put('/unit/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::delete('/unit/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
    });
    Route::get('/dokumen', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/dokumen/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/dokumen/{document}/unduh', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/dokumen/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
});
