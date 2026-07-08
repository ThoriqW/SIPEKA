<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {

    // BKD-only: OPD management
    Route::middleware('role:bkd')->group(function () {
        Route::resource('opd', \App\Http\Controllers\Admin\OpdController::class);
    });

    // AJAX endpoints — MUST be before resource routes
    Route::get('pegawai/extract-tanggal-lahir', [\App\Http\Controllers\Admin\PegawaiController::class, 'extractTanggalLahir'])
        ->name('pegawai.extract-tanggal-lahir');
    Route::get('jabatan/by-opd', [\App\Http\Controllers\Admin\JabatanController::class, 'getByOpd'])
        ->name('jabatan.by-opd');

    // Pegawai & Jabatan (both roles, filtered by controller)
    Route::resource('pegawai', \App\Http\Controllers\Admin\PegawaiController::class);
    Route::resource('jabatan', \App\Http\Controllers\Admin\JabatanController::class);

    // Kebutuhan & Bezetting (tree table views)
    // Route 'kebutuhan' → BezettingController menangani halaman Kebutuhan (dengan proyeksi)
    Route::get('kebutuhan', [\App\Http\Controllers\Admin\BezettingController::class, 'index'])
        ->name('kebutuhan.index');
    Route::get('kebutuhan/export', [\App\Http\Controllers\Admin\BezettingController::class, 'export'])
        ->name('kebutuhan.export');
    // Route 'bezetting' → KebutuhanController menangani halaman Bezetting (tanpa proyeksi)
    Route::get('bezetting', [\App\Http\Controllers\Admin\KebutuhanController::class, 'index'])
        ->name('bezetting.index');
    Route::get('bezetting/export', [\App\Http\Controllers\Admin\KebutuhanController::class, 'export'])
        ->name('bezetting.export');
});

require __DIR__.'/auth.php';
