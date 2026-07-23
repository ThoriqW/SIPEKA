<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus tabel master_jabatan.
     *
     * Fungsinya sudah sepenuhnya digantikan oleh jabatan_asn
     * yang memiliki dimensi jenjang (Ahli Pertama, Ahli Madya, dll).
     */
    public function up(): void
    {
        Schema::dropIfExists('master_jabatan');
    }

    public function down(): void
    {
        // Rollback: buat ulang tabel dari migration awal
        // (tidak rekomendasi untuk rollback produksi)
        Schema::create('master_jabatan', function ($table) {
            $table->id();
            $table->string('nama_jabatan');
            $table->string('jenis_jabatan', 20);
            $table->foreignId('parent_id')->nullable()->constrained('master_jabatan')->nullOnDelete();
            $table->timestamps();
            $table->index('jenis_jabatan');
        });
    }
};
