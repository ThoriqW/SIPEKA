<?php

namespace Database\Seeders;

use App\Models\Opd;
use Illuminate\Database\Seeder;

class OpdSeeder extends Seeder
{
    public function run(): void
    {
        $opds = [
            ['nama_opd' => 'Dinas Pendidikan dan Kebudayaan', 'kode_opd' => 'DIKBUD'],
            ['nama_opd' => 'Dinas Kesehatan', 'kode_opd' => 'DINKES'],
            ['nama_opd' => 'Dinas Pekerjaan Umum dan Penataan Ruang', 'kode_opd' => 'PUPR'],
            ['nama_opd' => 'Badan Kepegawaian dan Pengembangan SDM', 'kode_opd' => 'BKPSDM'],
            ['nama_opd' => 'Sekretariat Daerah', 'kode_opd' => 'SETDA'],
        ];

        foreach ($opds as $opd) {
            Opd::create($opd);
        }
    }
}
