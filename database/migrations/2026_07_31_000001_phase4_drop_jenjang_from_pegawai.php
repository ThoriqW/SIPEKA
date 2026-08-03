<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            // SQLite: drop index sebelum drop kolom
            $driver = DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                try { $table->dropIndex(['jenjang']); } catch (\Exception $e) {}
            }
            $table->dropColumn('jenjang');
        });
    }

    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->string('jenjang', 50)->nullable()->after('pendidikan');
        });

        // Backfill from jabatan
        DB::statement('UPDATE pegawai SET jenjang = (SELECT jenjang FROM jabatan WHERE jabatan.id = pegawai.jabatan_id) WHERE jabatan_id IS NOT NULL');
    }
};
