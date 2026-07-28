<?php

namespace Database\Seeders;

use App\Models\Unor;
use Illuminate\Database\Seeder;

class UnorSeeder extends Seeder
{
    public function run(): void
    {
        // Level 0: Root
        $pemkot = Unor::create([
            'nama_unor' => 'Pemerintah Kota Palu',
            'kode_unor' => 'PEMKOT',
            'singkatan' => 'Pemkot',
            'parent_id' => null,
        ]);

        // Level 1: OPD di bawah Pemerintah Kota Palu
        $unors = [
            ['nama_unor' => 'Dinas Pendidikan dan Kebudayaan', 'kode_unor' => 'DIKBUD', 'singkatan' => 'Dikbud'],
            ['nama_unor' => 'Dinas Kesehatan', 'kode_unor' => 'DINKES', 'singkatan' => 'Dinkes'],
            ['nama_unor' => 'Dinas Pekerjaan Umum dan Penataan Ruang', 'kode_unor' => 'PUPR', 'singkatan' => 'PUPR'],
            ['nama_unor' => 'Badan Kepegawaian dan Pengembangan SDM', 'kode_unor' => 'BKPSDM', 'singkatan' => 'BKPSDM'],
            ['nama_unor' => 'Sekretariat Daerah', 'kode_unor' => 'SETDA', 'singkatan' => 'Setda'],
        ];

        foreach ($unors as $data) {
            $data['parent_id'] = $pemkot->id;
            Unor::create($data);
        }

        // Level 2: Sub-UNOR contoh
        $dikbud = Unor::where('kode_unor', 'DIKBUD')->first();
        $dinkes = Unor::where('kode_unor', 'DINKES')->first();

        if ($dikbud) {
            Unor::create([
                'nama_unor' => 'SMP Negeri 1',
                'kode_unor' => 'SMPN1',
                'singkatan' => 'SMPN 1',
                'parent_id' => $dikbud->id,
            ]);
        }

        if ($dinkes) {
            Unor::create([
                'nama_unor' => 'Puskesmas Talise',
                'kode_unor' => 'PKM-TALISE',
                'singkatan' => 'PKM Talise',
                'parent_id' => $dinkes->id,
            ]);
        }
    }
}
