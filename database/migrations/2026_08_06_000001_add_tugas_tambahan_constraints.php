<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // ────────────────────────────────────────
        // Step 1: Bersihkan data duplikat existing
        // ────────────────────────────────────────

        // Pelanggaran A: pegawai + tugas_tambahan_id sama, beda UNOR, sama2 aktif
        $duplikatPegawai = DB::table('tugas_tambahan_pegawai')
            ->select('pegawai_id', 'tugas_tambahan_id', DB::raw('COUNT(*) as cnt, MAX(id) as keep_id'))
            ->where('is_active', true)
            ->groupBy('pegawai_id', 'tugas_tambahan_id')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplikatPegawai as $row) {
            DB::table('tugas_tambahan_pegawai')
                ->where('pegawai_id', $row->pegawai_id)
                ->where('tugas_tambahan_id', $row->tugas_tambahan_id)
                ->where('is_active', true)
                ->where('id', '!=', $row->keep_id)
                ->update([
                    'is_active' => false,
                    'tanggal_selesai' => now()->toDateString(),
                    'updated_at' => now(),
                ]);
        }

        // Pelanggaran B: unor + tugas_tambahan_id sama, beda pegawai, sama2 aktif
        $duplikatUnor = DB::table('tugas_tambahan_pegawai')
            ->select('unor_id', 'tugas_tambahan_id', DB::raw('COUNT(*) as cnt, MAX(id) as keep_id'))
            ->where('is_active', true)
            ->groupBy('unor_id', 'tugas_tambahan_id')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplikatUnor as $row) {
            DB::table('tugas_tambahan_pegawai')
                ->where('unor_id', $row->unor_id)
                ->where('tugas_tambahan_id', $row->tugas_tambahan_id)
                ->where('is_active', true)
                ->where('id', '!=', $row->keep_id)
                ->update([
                    'is_active' => false,
                    'tanggal_selesai' => now()->toDateString(),
                    'updated_at' => now(),
                ]);
        }

        // ────────────────────────────────────────
        // Step 2: Tambah constraint
        // ────────────────────────────────────────
        if ($driver === 'mysql') {
            // MySQL: generated column + unique index
            DB::statement(
                "ALTER TABLE tugas_tambahan_pegawai
                ADD COLUMN cek_pegawai_tugas VARCHAR(64)
                    GENERATED ALWAYS AS (
                        IF(is_active = 1, CONCAT(pegawai_id, '|', tugas_tambahan_id), NULL)
                    ) STORED,
                ADD UNIQUE INDEX uq_pegawai_tugas_aktif (cek_pegawai_tugas)"
            );

            DB::statement(
                "ALTER TABLE tugas_tambahan_pegawai
                ADD COLUMN cek_unor_tugas VARCHAR(64)
                    GENERATED ALWAYS AS (
                        IF(is_active = 1, CONCAT(unor_id, '|', tugas_tambahan_id), NULL)
                    ) STORED,
                ADD UNIQUE INDEX uq_unor_tugas_aktif (cek_unor_tugas)"
            );
        } else {
            // SQLite: partial unique index dengan WHERE clause
            // Cek versi SQLite — partial index butuh 3.8.0+
            DB::statement(
                'CREATE UNIQUE INDEX uq_pegawai_tugas_aktif ON tugas_tambahan_pegawai (pegawai_id, tugas_tambahan_id) WHERE is_active = 1'
            );
            DB::statement(
                'CREATE UNIQUE INDEX uq_unor_tugas_aktif ON tugas_tambahan_pegawai (unor_id, tugas_tambahan_id) WHERE is_active = 1'
            );
        }

        // ────────────────────────────────────────
        // Step 3: Unique constraint pada master_tugas_tambahan.nama_tugas
        // ────────────────────────────────────────
        Schema::table('master_tugas_tambahan', function (Blueprint $table) {
            $table->unique('nama_tugas', 'master_tugas_tambahan_nama_unique');
        });
    }

    public function down(): void
    {
        Schema::table('master_tugas_tambahan', function (Blueprint $table) {
            $table->dropUnique('master_tugas_tambahan_nama_unique');
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('tugas_tambahan_pegawai', function (Blueprint $table) {
                $table->dropIndex('uq_pegawai_tugas_aktif');
                $table->dropColumn('cek_pegawai_tugas');
                $table->dropIndex('uq_unor_tugas_aktif');
                $table->dropColumn('cek_unor_tugas');
            });
        } else {
            DB::statement('DROP INDEX IF EXISTS uq_pegawai_tugas_aktif');
            DB::statement('DROP INDEX IF EXISTS uq_unor_tugas_aktif');
        }
    }
};
