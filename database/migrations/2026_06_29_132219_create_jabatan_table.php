<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jabatan');
            $table->string('kode_jabatan');
            $table->string('jenis_jabatan'); // Struktural | Fungsional | Pelaksana
            $table->integer('kelas_jabatan');
            $table->integer('kebutuhan')->nullable(); // hanya untuk Fungsional & Pelaksana
            $table->foreignId('opd_id')->constrained('opd')->cascadeOnDelete();
            $table->foreignId('induk_jabatan_id')->nullable()->constrained('jabatan')->nullOnDelete();
            $table->timestamps();

            $table->index('opd_id');
            $table->index('induk_jabatan_id');
            $table->index('jenis_jabatan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatan');
    }
};
