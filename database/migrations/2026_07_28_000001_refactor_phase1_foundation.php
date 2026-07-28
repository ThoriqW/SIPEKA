<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ────────────────────────────────────────
        // Step 1: Drop foreign keys yang mereferensi tabel opd
        // ────────────────────────────────────────
        Schema::table('jabatan', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
        });
        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
        });

        // ────────────────────────────────────────
        // Step 2: Rename opd → unor
        // ────────────────────────────────────────
        Schema::rename('opd', 'unor');

        // ────────────────────────────────────────
        // Step 3: Rename kolom + tambah parent_id & singkatan
        //          Gunakan raw SQL yang kompatibel MySQL & SQLite
        // ────────────────────────────────────────
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE unor CHANGE nama_opd nama_unor VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE unor CHANGE kode_opd kode_unor VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE unor DROP INDEX opd_kode_opd_unique');
            DB::statement('ALTER TABLE unor ADD UNIQUE unor_kode_unor_unique (kode_unor)');
        } else {
            // SQLite / lainnya — gunakan RENAME COLUMN
            DB::statement('ALTER TABLE unor RENAME COLUMN nama_opd TO nama_unor');
            DB::statement('ALTER TABLE unor RENAME COLUMN kode_opd TO kode_unor');
            // SQLite tidak support rename index, tapi unique constraint ikut kolom
            // Buat ulang unique index untuk konsistensi
            try {
                DB::statement('DROP INDEX IF EXISTS opd_kode_opd_unique');
                DB::statement('CREATE UNIQUE INDEX unor_kode_unor_unique ON unor (kode_unor)');
            } catch (\Exception $e) {
                // Index mungkin sudah otomatis — safe to ignore
            }
        }

        // Tambah parent_id & singkatan
        Schema::table('unor', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('kode_unor');
            $table->string('singkatan', 10)->nullable()->after('parent_id');
        });

        // FK self-referencing: unor.parent_id → unor.id
        Schema::table('unor', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('unor')->nullOnDelete();
        });

        // ────────────────────────────────────────
        // Step 4: Re-create FK ke unor (sekarang references unor, bukan opd)
        // ────────────────────────────────────────
        Schema::table('jabatan', function (Blueprint $table) {
            $table->foreign('opd_id')->references('id')->on('unor')->cascadeOnDelete();
        });
        Schema::table('pegawai', function (Blueprint $table) {
            $table->foreign('opd_id')->references('id')->on('unor')->cascadeOnDelete();
        });

        // ────────────────────────────────────────
        // Step 5: Drop kolom obsolete dari jabatan
        // ────────────────────────────────────────
        // Drop generated column jptp_opd_unique (MySQL only; safe to try-catch)
        $driver = DB::connection()->getDriverName();
        try {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE jabatan DROP INDEX IF EXISTS jabatan_jptp_opd_unique');
                DB::statement('ALTER TABLE jabatan DROP COLUMN IF EXISTS jptp_opd_unique');
            } else {
                // SQLite: try to drop column if it exists
                DB::statement('ALTER TABLE jabatan DROP COLUMN jptp_opd_unique');
            }
        } catch (\Exception $e) {
            // Column/index doesn't exist — safe to ignore
        }

        Schema::table('jabatan', function (Blueprint $table) use ($driver) {
            // Drop FK dulu (MySQL: FK depends on index), lalu index, lalu kolom
            try { $table->dropForeign(['induk_jabatan_id']); } catch (\Exception $e) { /* ok */ }
            try { $table->dropIndex(['induk_jabatan_id']); } catch (\Exception $e) { /* ok */ }
            $table->dropColumn('induk_jabatan_id');
            $table->dropColumn('kebutuhan');
        });

        // ────────────────────────────────────────
        // Step 6: Buat tabel SOTK (junction UNOR-Jabatan)
        // ────────────────────────────────────────
        Schema::create('sotk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unor_id')->constrained('unor')->cascadeOnDelete();
            $table->foreignId('jabatan_id')->constrained('jabatan')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['unor_id', 'jabatan_id']);
        });

        // ────────────────────────────────────────
        // Step 7: Buat tabel kebutuhan_pegawai
        // ────────────────────────────────────────
        Schema::create('kebutuhan_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unor_id')->constrained('unor')->cascadeOnDelete();
            $table->foreignId('jabatan_id')->constrained('jabatan')->cascadeOnDelete();
            $table->year('tahun')->nullable()->comment('null = kebutuhan saat ini');
            $table->integer('jumlah')->default(0);
            $table->timestamps();

            $table->unique(['unor_id', 'jabatan_id', 'tahun'], 'kebutuhan_unique');
        });

        // ────────────────────────────────────────
        // Step 8: Buat tabel penempatan_pegawai
        // ────────────────────────────────────────
        Schema::create('penempatan_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->foreignId('unor_id')->constrained('unor');
            $table->foreignId('jabatan_id')->constrained('jabatan');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Untuk query Bezetting: COUNT pegawai WHERE is_active GROUP BY unor_id, jabatan_id
            $table->index(['unor_id', 'jabatan_id', 'is_active'], 'penempatan_bezetting_idx');
            // Untuk constraint satu penempatan aktif per pegawai
            $table->index(['pegawai_id', 'is_active'], 'penempatan_aktif_idx');
        });

        // ────────────────────────────────────────
        // Step 9: Buat tabel master_tugas_tambahan
        // ────────────────────────────────────────
        Schema::create('master_tugas_tambahan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_tugas');
            $table->timestamps();
        });

        // ────────────────────────────────────────
        // Step 10: Buat tabel tugas_tambahan_pegawai
        // ────────────────────────────────────────
        Schema::create('tugas_tambahan_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->foreignId('tugas_tambahan_id')->constrained('master_tugas_tambahan');
            $table->foreignId('unor_id')->constrained('unor');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['pegawai_id', 'is_active'], 'tugas_tambahan_aktif_idx');
        });

        // ────────────────────────────────────────
        // Step 11: Tambah jenjang & sub_jabatan ke master_jabatan
        // ────────────────────────────────────────
        Schema::table('master_jabatan', function (Blueprint $table) {
            $table->string('jenjang', 50)->nullable()->after('jenis_jabatan');
            $table->string('sub_jabatan', 100)->nullable()->after('jenjang');
        });
    }

    public function down(): void
    {
        // ────────────────────────────────────────
        // Rollback Step 11: Hapus jenjang & sub_jabatan
        // ────────────────────────────────────────
        Schema::table('master_jabatan', function (Blueprint $table) {
            $table->dropColumn(['sub_jabatan', 'jenjang']);
        });

        // ────────────────────────────────────────
        // Rollback Step 10: Drop tugas_tambahan_pegawai
        // ────────────────────────────────────────
        Schema::dropIfExists('tugas_tambahan_pegawai');

        // ────────────────────────────────────────
        // Rollback Step 9: Drop master_tugas_tambahan
        // ────────────────────────────────────────
        Schema::dropIfExists('master_tugas_tambahan');

        // ────────────────────────────────────────
        // Rollback Step 8: Drop penempatan_pegawai
        // ────────────────────────────────────────
        Schema::dropIfExists('penempatan_pegawai');

        // ────────────────────────────────────────
        // Rollback Step 7: Drop kebutuhan_pegawai
        // ────────────────────────────────────────
        Schema::dropIfExists('kebutuhan_pegawai');

        // ────────────────────────────────────────
        // Rollback Step 6: Drop sotk
        // ────────────────────────────────────────
        Schema::dropIfExists('sotk');

        // ────────────────────────────────────────
        // Rollback Step 5: Kembalikan kolom jabatan
        // ────────────────────────────────────────
        Schema::table('jabatan', function (Blueprint $table) {
            $table->integer('kebutuhan')->nullable()->after('kelas_jabatan');
            $table->foreignId('induk_jabatan_id')->nullable()->after('opd_id');
            $table->foreign('induk_jabatan_id')->references('id')->on('jabatan')->nullOnDelete();
            $table->index('induk_jabatan_id');
        });

        // ────────────────────────────────────────
        // Rollback Step 4: Drop FK ke unor
        // ────────────────────────────────────────
        Schema::table('jabatan', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
        });
        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
        });

        // ────────────────────────────────────────
        // Rollback Step 3: Hapus parent_id & singkatan,
        //                   rename kolom kembali
        // ────────────────────────────────────────
        Schema::table('unor', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['singkatan', 'parent_id']);
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE unor DROP INDEX unor_kode_unor_unique');
            DB::statement('ALTER TABLE unor CHANGE kode_unor kode_opd VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE unor CHANGE nama_unor nama_opd VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE unor ADD UNIQUE opd_kode_opd_unique (kode_opd)');
        } else {
            try {
                DB::statement('DROP INDEX IF EXISTS unor_kode_unor_unique');
            } catch (\Exception $e) { /* ignore */ }
            DB::statement('ALTER TABLE unor RENAME COLUMN kode_unor TO kode_opd');
            DB::statement('ALTER TABLE unor RENAME COLUMN nama_unor TO nama_opd');
            try {
                DB::statement('CREATE UNIQUE INDEX opd_kode_opd_unique ON unor (kode_opd)');
            } catch (\Exception $e) { /* ignore */ }
        }

        // ────────────────────────────────────────
        // Rollback Step 2: Rename unor → opd
        // ────────────────────────────────────────
        Schema::rename('unor', 'opd');

        // ────────────────────────────────────────
        // Rollback Step 1: Re-create FK ke opd
        // ────────────────────────────────────────
        Schema::table('jabatan', function (Blueprint $table) {
            $table->foreign('opd_id')->references('id')->on('opd')->cascadeOnDelete();
        });
        Schema::table('pegawai', function (Blueprint $table) {
            $table->foreign('opd_id')->references('id')->on('opd')->cascadeOnDelete();
        });
    }
};
