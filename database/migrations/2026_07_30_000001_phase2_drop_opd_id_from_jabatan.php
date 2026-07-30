<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Backfill — pastikan semua jabatan dengan opd_id punya SOTK entry
        $jabatanIdsWithSotk = DB::table('sotk')->pluck('jabatan_id')->toArray();
        $rows = DB::table('jabatan')
            ->whereNotNull('opd_id')
            ->whereNotIn('id', $jabatanIdsWithSotk)
            ->get(['id', 'opd_id']);

        foreach ($rows as $row) {
            DB::table('sotk')->insert([
                'unor_id'    => $row->opd_id,
                'jabatan_id' => $row->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Step 2: Drop FK constraint
        Schema::table('jabatan', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
        });

        // Step 3: Drop index (MySQL auto-creates index for FK)
        // Gunakan raw query untuk kompatibilitas
        try {
            Schema::table('jabatan', function (Blueprint $table) {
                $table->dropIndex(['opd_id']);
            });
        } catch (\Exception $e) {
            // Index mungkin sudah di-drop bersama FK — safe to ignore
        }

        // Step 4: Drop the column
        Schema::table('jabatan', function (Blueprint $table) {
            $table->dropColumn('opd_id');
        });
    }

    public function down(): void
    {
        // Step 1: Add back the column
        Schema::table('jabatan', function (Blueprint $table) {
            $table->foreignId('opd_id')
                ->nullable()
                ->after('jenjang');
        });

        // Step 2: Backfill from primary SOTK entries
        // Primary = UNOR yang bukan root (parent_id IS NOT NULL)
        $sotkRows = DB::table('sotk')
            ->join('unor', 'sotk.unor_id', '=', 'unor.id')
            ->whereNotNull('unor.parent_id')
            ->select('sotk.jabatan_id', 'sotk.unor_id')
            ->get();

        foreach ($sotkRows as $row) {
            DB::table('jabatan')
                ->where('id', $row->jabatan_id)
                ->whereNull('opd_id')
                ->update(['opd_id' => $row->unor_id]);
        }

        // Fallback: untuk jabatan yang hanya punya SOTK di root, ambil root
        DB::table('jabatan')
            ->whereNull('opd_id')
            ->update([
                'opd_id' => DB::raw('(SELECT sotk.unor_id FROM sotk WHERE sotk.jabatan_id = jabatan.id LIMIT 1)'),
            ]);

        // Step 3: Add FK constraint
        Schema::table('jabatan', function (Blueprint $table) {
            $table->foreign('opd_id')->references('id')->on('unor')->cascadeOnDelete();
        });
    }
};
