<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

// Admin routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // Unit Organisasi — semua user terautentikasi
    Route::get('unor', [\App\Http\Controllers\Admin\UnorController::class, 'index'])->name('unor.index');
    Route::get('unor/create', [\App\Http\Controllers\Admin\UnorController::class, 'create'])->name('unor.create');
    Route::post('unor', [\App\Http\Controllers\Admin\UnorController::class, 'store'])->name('unor.store');
    Route::get('unor/{unor}/edit', [\App\Http\Controllers\Admin\UnorController::class, 'edit'])->name('unor.edit');
    Route::put('unor/{unor}', [\App\Http\Controllers\Admin\UnorController::class, 'update'])->name('unor.update');
    Route::delete('unor/{unor}', [\App\Http\Controllers\Admin\UnorController::class, 'destroy'])->name('unor.destroy');

    // Master Tugas Tambahan
    Route::resource('tugas-tambahan', \App\Http\Controllers\Admin\MasterTugasTambahanController::class);

    // User & Referensi Jabatan — Admin-only
    Route::middleware('role:admin')->group(function () {
        Route::resource('user', \App\Http\Controllers\Admin\UserController::class);
        Route::resource('referensi-jabatan', \App\Http\Controllers\Admin\ReferensiJabatanController::class);
    });

    // AJAX endpoints
    Route::get('pegawai/extract-tanggal-lahir', [\App\Http\Controllers\Admin\PegawaiController::class, 'extractTanggalLahir'])
        ->name('pegawai.extract-tanggal-lahir');
    Route::get('jabatan/by-opd', [\App\Http\Controllers\Admin\JabatanController::class, 'getByOpd'])
        ->name('jabatan.by-opd');

    // Pegawai & Jabatan
    Route::resource('pegawai', \App\Http\Controllers\Admin\PegawaiController::class);
    Route::post('pegawai/{pegawai}/tugas-tambahan', [\App\Http\Controllers\Admin\PegawaiController::class, 'storeTugasTambahan'])
        ->name('pegawai.tugas-tambahan.store');
    Route::patch('pegawai/{pegawai}/tugas-tambahan/{tugasTambahan}/cabut', [\App\Http\Controllers\Admin\PegawaiController::class, 'cabutTugasTambahan'])
        ->name('pegawai.tugas-tambahan.cabut');
    Route::delete('pegawai/{pegawai}/tugas-tambahan/{tugasTambahan}', [\App\Http\Controllers\Admin\PegawaiController::class, 'destroyTugasTambahan'])
        ->name('pegawai.tugas-tambahan.destroy');
    Route::resource('jabatan', \App\Http\Controllers\Admin\JabatanController::class);

    // Kebutuhan & Bezetting
    Route::get('kebutuhan', [\App\Http\Controllers\Admin\KebutuhanController::class, 'index'])->name('kebutuhan.index');
    Route::get('kebutuhan/export', [\App\Http\Controllers\Admin\KebutuhanController::class, 'export'])->name('kebutuhan.export');
    Route::get('bezetting', [\App\Http\Controllers\Admin\BezettingController::class, 'index'])->name('bezetting.index');
    Route::get('bezetting/export', [\App\Http\Controllers\Admin\BezettingController::class, 'export'])->name('bezetting.export');
});

require __DIR__.'/auth.php';
