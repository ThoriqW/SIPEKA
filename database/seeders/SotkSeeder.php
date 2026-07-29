<?php

namespace Database\Seeders;

use App\Models\Sotk;
use App\Models\Jabatan;
use App\Models\Unor;
use Illuminate\Database\Seeder;

class SotkSeeder extends Seeder
{
    public function run(): void
    {
        $pemkot = Unor::where('kode_unor', 'PEMKOT')->first();

        // Semua jabatan di setiap OPD masuk SOTK
        $unorList = Unor::whereNotNull('parent_id')->get();

        foreach ($unorList as $unor) {
            $jabatanList = Jabatan::where('opd_id', $unor->id)->get();

            foreach ($jabatanList as $jabatan) {
                Sotk::create([
                    'unor_id' => $unor->id,
                    'jabatan_id' => $jabatan->id,
                ]);
            }

            // JPTP juga masuk SOTK Pemkot (root)
            $jptpList = Jabatan::where('opd_id', $unor->id)
                ->where('jenis_jabatan', 'Struktural')
                ->where('jenjang', 'Pimpinan Tinggi Pratama')
                ->get();

            foreach ($jptpList as $jptp) {
                Sotk::firstOrCreate([
                    'unor_id' => $pemkot->id,
                    'jabatan_id' => $jptp->id,
                ]);
            }
        }
    }
}
