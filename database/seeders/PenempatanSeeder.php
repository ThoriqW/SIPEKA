<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use Illuminate\Database\Seeder;

class PenempatanSeeder extends Seeder
{
    public function run(): void
    {
        $pegawaiList = Pegawai::all();

        foreach ($pegawaiList as $p) {
            if (!$p->jabatan_id) continue;

            // Resolve UNOR dari SOTK jabatan
            $jabatan = Jabatan::with('sotkEntries')->find($p->jabatan_id);
            $unorId = $jabatan?->sotkEntries->first()?->unor_id;
            if (!$unorId) continue;

            PenempatanPegawai::create([
                'pegawai_id'     => $p->id,
                'unor_id'        => $unorId,
                'jabatan_id'     => $p->jabatan_id,
                'tanggal_mulai'  => '2020-01-01',
                'tanggal_selesai' => null,
                'is_active'      => true,
            ]);
        }
    }
}
