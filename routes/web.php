<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

// Admin routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // OPD — semua user terautentikasi
    Route::resource('opd', \App\Http\Controllers\Admin\OpdController::class);

    // User — BKD-only
    Route::middleware('role:bkd')->group(function () {
        Route::resource('user', \App\Http\Controllers\Admin\UserController::class);
        Route::resource('master-jabatan', \App\Http\Controllers\Admin\MasterJabatanController::class);
        // Jabatan ASN — BKD-only (katalog referensi jabatan kepegawaian)
        Route::resource('jabatan-asn', \App\Http\Controllers\Admin\JabatanAsnController::class);
    });

    // --- Node Organisasi (Struktur Organisasi baru) ---
    Route::resource('node-organisasi', \App\Http\Controllers\Admin\NodeOrganisasiController::class);
    Route::get('node-organisasi/{nodeOrganisasi}/children', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'children'])
        ->name('node-organisasi.children');
    Route::get('node-organisasi/ajax/by-parent', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'getPosisiByParent'])
        ->name('node-organisasi.by-parent');

    // --- AJAX endpoints ---
    Route::get('pegawai/extract-tanggal-lahir', [\App\Http\Controllers\Admin\PegawaiController::class, 'extractTanggalLahir'])
        ->name('pegawai.extract-tanggal-lahir');
    Route::get('pegawai/posisi-by-unit', [\App\Http\Controllers\Admin\PegawaiController::class, 'getPosisiByUnit'])
        ->name('pegawai.posisi-by-unit');
    Route::get('jabatan/by-opd', [\App\Http\Controllers\Admin\JabatanController::class, 'getByOpd'])
        ->name('jabatan.by-opd');

    // Pegawai & Jabatan — semua user terautentikasi
    Route::resource('pegawai', \App\Http\Controllers\Admin\PegawaiController::class);
    Route::resource('jabatan', \App\Http\Controllers\Admin\JabatanController::class);

    // Kebutuhan & Bezetting (tree table views)
    Route::get('kebutuhan', [\App\Http\Controllers\Admin\BezettingController::class, 'index'])
        ->name('kebutuhan.index');
    Route::get('kebutuhan/export', [\App\Http\Controllers\Admin\BezettingController::class, 'export'])
        ->name('kebutuhan.export');
    Route::get('bezetting', [\App\Http\Controllers\Admin\KebutuhanController::class, 'index'])
        ->name('bezetting.index');
    Route::get('bezetting/export', [\App\Http\Controllers\Admin\KebutuhanController::class, 'export'])
        ->name('bezetting.export');
});

require __DIR__.'/auth.php';
