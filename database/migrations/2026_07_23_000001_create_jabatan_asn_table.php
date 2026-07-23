<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel jabatan_asn — jabatan kepegawaian yang melekat pada pegawai.
     *
     * Tabel ini menggantikan peran master_jabatan sebagai katalog referensi
     * jabatan ASN, dengan tambahan dimensi jenjang.
     *
     * Contoh data:
     *   - Guru Ahli Pertama (Fungsional, Guru)
     *   - Dokter Ahli Madya (Fungsional, Ahli Madya)
     *   - Pranata Komputer Ahli Pertama (Fungsional, Ahli Pertama)
     *   - Analis SDM Aparatur (Pelaksana, Pelaksana)
     */
    public function up(): void
    {
        Schema::create('jabatan_asn', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jabatan_asn');               // "Guru Ahli Pertama", "Dokter Ahli Madya"
            $table->string('jenis_jabatan', 20);              // Struktural | Fungsional | Pelaksana
            $table->string('jenjang')->nullable();            // Ahli Pertama, Ahli Madya, dll.
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('jabatan_asn')
                ->nullOnDelete();                             // Self-FK untuk grouping (Dokter → Dokter Umum)
            $table->string('kode_jabatan_asn')->unique();     // Auto-generated: JASN-00001
            $table->timestamps();

            $table->unique(['nama_jabatan_asn', 'jenis_jabatan'], 'uk_jabatan_asn_nama_jenis');
            $table->index('jenis_jabatan');
            $table->index('jenjang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatan_asn');
    }
};
