<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Backfill — pastikan semua pegawai dengan opd_id punya penempatan aktif
        $pegawaiIdsWithPenempatan = DB::table('penempatan_pegawai')
            ->where('is_active', true)
            ->pluck('pegawai_id')
            ->toArray();

        $rows = DB::table('pegawai')
            ->whereNotNull('opd_id')
            ->whereNotIn('id', $pegawaiIdsWithPenempatan)
            ->get(['id', 'opd_id', 'jabatan_id']);

        foreach ($rows as $row) {
            DB::table('penempatan_pegawai')->insert([
                'pegawai_id'     => $row->id,
                'unor_id'        => $row->opd_id,
                'jabatan_id'     => $row->jabatan_id,
                'tanggal_mulai'  => now()->toDateString(),
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // Step 2: Drop FK constraint
        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
        });

        // Step 3: Drop index (if any)
        try {
            Schema::table('pegawai', function (Blueprint $table) {
                $table->dropIndex(['opd_id']);
            });
        } catch (\Exception $e) {
            // Index mungkin sudah di-drop bersama FK
        }

        // Step 4: Drop the column
        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropColumn('opd_id');
        });
    }

    public function down(): void
    {
        // Step 1: Add back the column
        Schema::table('pegawai', function (Blueprint $table) {
            $table->foreignId('opd_id')
                ->nullable()
                ->after('jenjang');
        });

        // Step 2: Backfill from penempatan aktif
        $penempatanRows = DB::table('penempatan_pegawai')
            ->where('is_active', true)
            ->get(['pegawai_id', 'unor_id']);

        foreach ($penempatanRows as $row) {
            DB::table('pegawai')
                ->where('id', $row->pegawai_id)
                ->whereNull('opd_id')
                ->update(['opd_id' => $row->unor_id]);
        }

        // Step 3: Add FK constraint
        Schema::table('pegawai', function (Blueprint $table) {
            $table->foreign('opd_id')->references('id')->on('unor')->cascadeOnDelete();
        });
    }
};
