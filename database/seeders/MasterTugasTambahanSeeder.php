<?php

namespace Database\Seeders;

use App\Models\MasterTugasTambahan;
use Illuminate\Database\Seeder;

class MasterTugasTambahanSeeder extends Seeder
{
    public function run(): void
    {
        $tugas = [
            'Kepala Sekolah',
            'Kepala Puskesmas',
            'Plt. Kepala Dinas',
            'Plt. Kepala Bidang',
        ];

        foreach ($tugas as $nama) {
            MasterTugasTambahan::create(['nama_tugas' => $nama]);
        }
    }
}
