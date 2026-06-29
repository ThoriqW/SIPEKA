<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nip', 18)->unique();
            $table->string('jenis_kepegawaian'); // PNS | PPPK
            $table->date('tanggal_lahir');
            $table->string('golongan_pangkat', 5); // I/a .. IV/e
            $table->string('pendidikan'); // SD .. S3
            $table->string('jenjang'); // Pelaksana | Ahli Pertama | Ahli Muda | Ahli Madya | Ahli Utama | Keterampilan | Guru | Pimpinan Tinggi
            $table->foreignId('opd_id')->constrained('opd')->cascadeOnDelete();
            $table->foreignId('jabatan_id')->nullable()->constrained('jabatan')->nullOnDelete();
            $table->timestamps();

            $table->index('opd_id');
            $table->index('jabatan_id');
            $table->index('jenis_kepegawaian');
            $table->index('jenjang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
