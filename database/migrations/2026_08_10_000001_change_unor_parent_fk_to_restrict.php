<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK nullOnDelete lalu buat ulang dengan restrict
        Schema::table('unor', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->foreign('parent_id')
                ->references('id')
                ->on('unor')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unor', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->foreign('parent_id')
                ->references('id')
                ->on('unor')
                ->nullOnDelete();
        });
    }
};
