<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus sesuai urutan FK constraint
        DB::table('tugas_tambahan_pegawai')->delete();
        DB::table('penempatan_pegawai')->delete();
        DB::table('kebutuhan_pegawai')->delete();
        DB::table('sotk')->delete();
        DB::table('pegawai')->delete();
        DB::table('jabatan')->delete();
        DB::table('users')->where('role', '!=', 'admin')->delete();
        DB::table('unor')->delete();
    }

    public function down(): void
    {
        // Tidak bisa rollback data yang sudah dihapus
    }
};
