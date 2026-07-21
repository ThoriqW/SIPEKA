<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jabatan');
            $table->string('jenis_jabatan', 20);
            $table->foreignId('parent_id')->nullable()->constrained('master_jabatan')->nullOnDelete();
            $table->timestamps();

            $table->index('jenis_jabatan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_jabatan');
    }
};
