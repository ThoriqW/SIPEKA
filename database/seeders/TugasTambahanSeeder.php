<?php

namespace Database\Seeders;

use App\Models\TugasTambahanPegawai;
use App\Models\MasterTugasTambahan;
use App\Models\Pegawai;
use App\Models\Unor;
use Illuminate\Database\Seeder;

class TugasTambahanSeeder extends Seeder
{
    public function run(): void
    {
        $kepalaSekolah = MasterTugasTambahan::where('nama_tugas', 'Kepala Sekolah')->first();
        $kepalaPuskesmas = MasterTugasTambahan::where('nama_tugas', 'Kepala Puskesmas')->first();
        $pltKadis = MasterTugasTambahan::where('nama_tugas', 'Plt. Kepala Dinas')->first();

        $smpn1 = Unor::where('kode_unor', 'SMPN1')->first();
        $pkmTalise = Unor::where('kode_unor', 'PKM-TALISE')->first();

        // Kepala Sekolah — untuk Guru Kelas di Dikbud
        $guru = Pegawai::whereHas('jabatan', function ($q) {
            $q->where('nama_jabatan', 'like', '%Guru Kelas%');
        })->first();

        if ($guru && $kepalaSekolah && $smpn1) {
            TugasTambahanPegawai::create([
                'pegawai_id' => $guru->id,
                'tugas_tambahan_id' => $kepalaSekolah->id,
                'unor_id' => $smpn1->id,
                'tanggal_mulai' => '2024-01-01',
                'tanggal_selesai' => null,
                'is_active' => true,
            ]);
        }

        // Kepala Puskesmas — untuk Dokter di Dinkes
        $dokter = Pegawai::whereHas('jabatan', function ($q) {
            $q->where('nama_jabatan', 'like', '%Dokter Umum%');
        })->first();

        if ($dokter && $kepalaPuskesmas && $pkmTalise) {
            TugasTambahanPegawai::create([
                'pegawai_id' => $dokter->id,
                'tugas_tambahan_id' => $kepalaPuskesmas->id,
                'unor_id' => $pkmTalise->id,
                'tanggal_mulai' => '2024-01-01',
                'tanggal_selesai' => null,
                'is_active' => true,
            ]);
        }

        // Plt. Kepala Dinas — contoh di PUPR (tidak ada Kadis definitif)
        $pupr = Unor::where('kode_unor', 'PUPR')->first();
        $pltPegawai = Pegawai::whereHas('penempatanAktif', fn($q) => $q->where('unor_id', $pupr->id))
            ->whereHas('jabatan', function ($q) {
                $q->where('jenjang', 'Administrator');
            })->first();

        if ($pltPegawai && $pltKadis) {
            TugasTambahanPegawai::create([
                'pegawai_id' => $pltPegawai->id,
                'tugas_tambahan_id' => $pltKadis->id,
                'unor_id' => $pltPegawai->penempatanAktif->unor_id,
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => null,
                'is_active' => true,
            ]);
        }
    }
}
