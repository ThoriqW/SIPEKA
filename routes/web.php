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
        // Jabatan ASN — katalog referensi jabatan kepegawaian
        Route::resource('jabatan-asn', \App\Http\Controllers\Admin\JabatanAsnController::class);
    });

    // --- Unor (Unit Organisasi) ---
    Route::get('unor', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'unorIndex'])
        ->name('unor.index');
    Route::get('unor/create', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'create'])
        ->name('unor.create');
    Route::post('unor', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'store'])
        ->name('unor.store');
    Route::get('unor/{nodeOrganisasi}/edit', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'edit'])
        ->name('unor.edit');
    Route::put('unor/{nodeOrganisasi}', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'update'])
        ->name('unor.update');
    Route::delete('unor/{nodeOrganisasi}', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'destroy'])
        ->name('unor.destroy');

    // --- Kebutuhan Jabatan (POSISI) ---
    Route::get('kebutuhan-jabatan', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'kebutuhanIndex'])
        ->name('kebutuhan-jabatan.index');
    Route::get('kebutuhan-jabatan/create', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'create'])
        ->name('kebutuhan-jabatan.create');
    Route::post('kebutuhan-jabatan', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'store'])
        ->name('kebutuhan-jabatan.store');
    Route::get('kebutuhan-jabatan/{nodeOrganisasi}/edit', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'edit'])
        ->name('kebutuhan-jabatan.edit');
    Route::put('kebutuhan-jabatan/{nodeOrganisasi}', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'update'])
        ->name('kebutuhan-jabatan.update');
    Route::delete('kebutuhan-jabatan/{nodeOrganisasi}', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'destroy'])
        ->name('kebutuhan-jabatan.destroy');

    // --- AJAX endpoints ---
    Route::get('unor/{nodeOrganisasi}/children', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'children'])
        ->name('unor.children');
    Route::get('unor/ajax/by-parent', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'getPosisiByParent'])
        ->name('unor.by-parent');
    Route::get('unor/ajax/by-opd', [\App\Http\Controllers\Admin\NodeOrganisasiController::class, 'getUnorByOpd'])
        ->name('unor.by-opd');

    // --- AJAX endpoints ---
    Route::get('pegawai/extract-tanggal-lahir', [\App\Http\Controllers\Admin\PegawaiController::class, 'extractTanggalLahir'])
        ->name('pegawai.extract-tanggal-lahir');
    Route::get('pegawai/posisi-by-unit', [\App\Http\Controllers\Admin\PegawaiController::class, 'getPosisiByUnit'])
        ->name('pegawai.posisi-by-unit');

    // Pegawai — semua user terautentikasi
    Route::resource('pegawai', \App\Http\Controllers\Admin\PegawaiController::class);

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
