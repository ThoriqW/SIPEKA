<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom gelar depan & gelar belakang pada pegawai.
     *
     * Data lama sengaja TIDAK dipindahkan: gelarnya sudah menyatu di dalam
     * kolom `nama` (mis. "Drs. Abdul Muis, M.Si."). Kolom baru ini kosong untuk
     * mereka, dan Pegawai::namaLengkap() mengembalikan `nama` apa adanya
     * sehingga tidak ada gelar yang tergandakan.
     */
    public function up(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->string('gelar_depan', 50)->nullable()->after('nama');
            $table->string('gelar_belakang', 100)->nullable()->after('gelar_depan');
        });
    }

    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropColumn(['gelar_depan', 'gelar_belakang']);
        });
    }
};
