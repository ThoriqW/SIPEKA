<?php

namespace Database\Seeders;

use App\Models\PenempatanPegawai;
use App\Models\Pegawai;
use App\Models\Jabatan;
use App\Models\Unor;
use Illuminate\Database\Seeder;

class PenempatanSeeder extends Seeder
{
    public function run(): void
    {
        $dikbud = Unor::where('kode_unor', 'DIKBUD')->first();
        $dinkes = Unor::where('kode_unor', 'DINKES')->first();

        $pegawaiList = Pegawai::all();

        foreach ($pegawaiList as $p) {
            // Gunakan opd_id + jabatan_id existing sebagai penempatan awal
            if ($p->opd_id && $p->jabatan_id) {
                PenempatanPegawai::create([
                    'pegawai_id' => $p->id,
                    'unor_id' => $p->opd_id,
                    'jabatan_id' => $p->jabatan_id,
                    'tanggal_mulai' => '2020-01-01',
                    'tanggal_selesai' => null,
                    'is_active' => true,
                ]);
            }
        }
    }
}
