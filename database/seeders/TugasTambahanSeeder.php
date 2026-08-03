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
        // Plt. Kepala Dinas — contoh di Dinas Kesehatan
        // (menggantikan sementara Kadis yang kosong)
        $pltKadis = MasterTugasTambahan::where('nama_tugas', 'Plt. Kepala Dinas')->first();
        $dinkes = Unor::where('kode_unor', 'DINKES')->first();
        $pltPegawai = null;

        if ($dinkes && $pltKadis) {
            // Cari pegawai Administrator di Dinkes untuk jadi Plt
            $pltPegawai = Pegawai::whereHas('penempatanAktif', fn($q) => $q->where('unor_id', $dinkes->id))
                ->whereHas('jabatan', fn($q) => $q->where('jenjang', 'Administrator'))
                ->first();

            // Fallback: cari di semua OPD
            if (!$pltPegawai) {
                $pltPegawai = Pegawai::whereHas('jabatan', fn($q) => $q->where('jenjang', 'Administrator'))->first();
            }
        }

        if ($pltPegawai && $pltKadis) {
            TugasTambahanPegawai::create([
                'pegawai_id'        => $pltPegawai->id,
                'tugas_tambahan_id' => $pltKadis->id,
                'unor_id'           => $pltPegawai->penempatanAktif->unor_id,
                'tanggal_mulai'     => '2025-01-01',
                'tanggal_selesai'   => null,
                'is_active'         => true,
            ]);
        }
    }
}
