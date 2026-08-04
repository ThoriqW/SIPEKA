<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_jabatan', function (Blueprint $table) {
            $table->string('kelompok', 30)->nullable()->after('sub_jabatan');
            $table->index('kelompok');
        });
    }

    public function down(): void
    {
        Schema::table('master_jabatan', function (Blueprint $table) {
            $table->dropIndex(['kelompok']);
            $table->dropColumn('kelompok');
        });
    }
};
