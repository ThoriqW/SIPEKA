<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unor', function (Blueprint $table) {
            $table->dropColumn('singkatan');
        });
    }

    public function down(): void
    {
        Schema::table('unor', function (Blueprint $table) {
            $table->string('singkatan', 10)->nullable()->after('kode_unor');
        });
    }
};
