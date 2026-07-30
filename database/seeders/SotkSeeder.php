<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\Sotk;
use App\Models\Unor;
use Illuminate\Database\Seeder;

class SotkSeeder extends Seeder
{
    public function run(): void
    {
        $pemkot = Unor::where('kode_unor', 'PEMKOT')->first();
        if (!$pemkot) return;

        // Primary SOTK entries sudah dibuat oleh JabatanSeeder.
        // Di sini hanya tambahkan PEMKOT root entries untuk JPTP (Kepala OPD).
        $jptpList = Jabatan::where('jenis_jabatan', 'Struktural')
            ->where('jenjang', 'Pimpinan Tinggi Pratama')
            ->get();

        foreach ($jptpList as $jptp) {
            Sotk::firstOrCreate([
                'unor_id'    => $pemkot->id,
                'jabatan_id' => $jptp->id,
            ]);
        }
    }
}
