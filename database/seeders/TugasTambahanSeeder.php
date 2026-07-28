<?php

namespace Database\Seeders;

use App\Models\TugasTambahanPegawai;
use App\Models\MasterTugasTambahan;
use App\Models\Pegawai;
use App\Models\Jabatan;
use App\Models\Unor;
use Illuminate\Database\Seeder;

class TugasTambahanSeeder extends Seeder
{
    public function run(): void
    {
        $kepalaSekolah = MasterTugasTambahan::where('nama_tugas', 'Kepala Sekolah')->first();
        $kepalaPuskesmas = MasterTugasTambahan::where('nama_tugas', 'Kepala Puskesmas')->first();

        // Guru Kelas di DIKBUD → Kepala Sekolah
        $guru = Pegawai::whereHas('jabatan', function ($q) {
            $q->where('kode_jabatan', 'DIKBUD-006');
        })->first();

        if ($guru && $kepalaSekolah) {
            TugasTambahanPegawai::create([
                'pegawai_id' => $guru->id,
                'tugas_tambahan_id' => $kepalaSekolah->id,
                'unor_id' => $guru->opd_id,
                'tanggal_mulai' => '2023-01-01',
                'tanggal_selesai' => null,
                'is_active' => true,
            ]);
        }

        // Dokter Umum di DINKES → Kepala Puskesmas
        $dokter = Pegawai::whereHas('jabatan', function ($q) {
            $q->where('kode_jabatan', 'DINKES-003');
        })->first();

        if ($dokter && $kepalaPuskesmas) {
            TugasTambahanPegawai::create([
                'pegawai_id' => $dokter->id,
                'tugas_tambahan_id' => $kepalaPuskesmas->id,
                'unor_id' => $dokter->opd_id,
                'tanggal_mulai' => '2023-01-01',
                'tanggal_selesai' => null,
                'is_active' => true,
            ]);
        }
    }
}
