<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modifikasi tabel pegawai untuk mendukung model SOTK baru.
     *
     * Perubahan:
     *   - Tambah jabatan_asn_id (FK ke jabatan_asn) — jabatan kepegawaian
     *   - Tambah posisi_organisasi_id (FK ke node_organisasi) — posisi di struktur org
     *   - Kolom jabatan_id yang lama dipertahankan untuk backward compatibility
     *     dan akan di-drop setelah migrasi data selesai.
     *   - Kolom opd_id tetap sebagai kolom denormalisasi
     *   - Unique constraint pada posisi_organisasi_id (satu posisi = satu pegawai)
     */
    public function up(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            // Tambah kolom baru (nullable dulu untuk memudahkan migrasi bertahap)
            $table->foreignId('jabatan_asn_id')
                ->nullable()
                ->after('jenjang')
                ->constrained('jabatan_asn')
                ->nullOnDelete();

            $table->foreignId('posisi_organisasi_id')
                ->nullable()
                ->after('jabatan_asn_id')
                ->constrained('node_organisasi')
                ->nullOnDelete();

            // Unique: satu posisi hanya boleh diisi satu pegawai
            // NULL values tidak dianggap duplikat, jadi posisi kosong (NULL) aman
            $table->unique('posisi_organisasi_id', 'uk_pegawai_posisi_organisasi');
        });
    }

    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropUnique('uk_pegawai_posisi_organisasi');
            $table->dropForeign(['posisi_organisasi_id']);
            $table->dropForeign(['jabatan_asn_id']);
            $table->dropColumn(['posisi_organisasi_id', 'jabatan_asn_id']);
        });
    }
};
