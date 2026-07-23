<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel node_organisasi — pohon organisasi tunggal.
     *
     * Setiap node memiliki jenis: UNIT (wadah/container) atau POSISI (dapat diisi pegawai).
     *
     * Tabel ini menggantikan peran tabel `jabatan` dan `opd` dalam membangun
     * struktur organisasi.
     *
     * Struktur:
     *   - Root: "Pemerintah Kota Palu" (UNIT, parent_id=null)
     *   - Level 1: OPD seperti BKPSDMD, Dinas Kesehatan (UNIT)
     *   - Level N: Unit turunan (Sekretariat, Bidang, Puskesmas Talise)
     *   - Leaf/diisi: POSISI (Kepala BKPSDMD, Dokter, Perawat, Guru)
     *
     * Aturan:
     *   - Node UNIT tidak bisa diisi pegawai (hanya sebagai container)
     *   - Node POSISI hanya boleh diisi 1 pegawai
     *   - Tidak ada batasan jenis node untuk parent-child
     *   - Tidak ada batas maksimal level
     */
    public function up(): void
    {
        Schema::create('node_organisasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode')->nullable()->unique();       // Auto-generated untuk POSISI
            $table->enum('jenis', ['UNIT', 'POSISI']);          // Tipe node
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('node_organisasi')
                ->nullOnDelete();                                // Self-FK
            $table->integer('kelas_jabatan')->nullable();       // Hanya untuk POSISI
            $table->integer('sort_order')->nullable()->default(0);
            $table->timestamps();

            $table->index('parent_id');
            $table->index('jenis');
            $table->index(['parent_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_organisasi');
    }
};
