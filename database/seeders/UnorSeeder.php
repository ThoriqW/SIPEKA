<?php

namespace Database\Seeders;

use App\Models\Unor;
use Illuminate\Database\Seeder;

class UnorSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────
        // Level 0: Root
        // ─────────────────────────────────────────────
        $pemkot = Unor::create([
            'nama_unor' => 'Pemerintah Kota Palu',
            'kode_unor' => 'PEMKOT',
            'singkatan' => 'Pemkot',
            'parent_id' => null,
        ]);

        // ─────────────────────────────────────────────
        // Level 1: 3 OPD
        // ─────────────────────────────────────────────
        $bkpsdm = Unor::create([
            'nama_unor' => 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia',
            'kode_unor' => 'BKPSDM',
            'singkatan' => 'BKPSDM',
            'parent_id' => $pemkot->id,
        ]);

        $dinkes = Unor::create([
            'nama_unor' => 'Dinas Kesehatan',
            'kode_unor' => 'DINKES',
            'singkatan' => 'Dinkes',
            'parent_id' => $pemkot->id,
        ]);

        $dikbud = Unor::create([
            'nama_unor' => 'Dinas Pendidikan',
            'kode_unor' => 'DIKBUD',
            'singkatan' => 'Dikbud',
            'parent_id' => $pemkot->id,
        ]);

        // ─────────────────────────────────────────────
        // BKPSDMD — children
        // ─────────────────────────────────────────────
        $bkpsdmSekr = Unor::create([
            'nama_unor' => 'Sekretariat',
            'kode_unor' => 'BKPSDM-SEKR',
            'singkatan' => 'Sekr',
            'parent_id' => $bkpsdm->id,
        ]);

        Unor::create([
            'nama_unor' => 'Sub Bagian Umum dan Kepegawaian',
            'kode_unor' => 'BKPSDM-SUB-UMUM',
            'parent_id' => $bkpsdmSekr->id,
        ]);

        Unor::create([
            'nama_unor' => 'Sub Bagian Keuangan',
            'kode_unor' => 'BKPSDM-SUB-KEU',
            'parent_id' => $bkpsdmSekr->id,
        ]);

        Unor::create([
            'nama_unor' => 'Sub Bagian Perencanaan',
            'kode_unor' => 'BKPSDM-SUB-REN',
            'parent_id' => $bkpsdmSekr->id,
        ]);

        Unor::create([
            'nama_unor' => 'Bidang Pengadaan, Pemberhentian dan Informasi',
            'kode_unor' => 'BKPSDM-BID-PPI',
            'parent_id' => $bkpsdm->id,
        ]);

        Unor::create([
            'nama_unor' => 'Bidang Mutasi dan Promosi',
            'kode_unor' => 'BKPSDM-BID-MP',
            'parent_id' => $bkpsdm->id,
        ]);

        Unor::create([
            'nama_unor' => 'Bidang Pengembangan Kompetensi',
            'kode_unor' => 'BKPSDM-BID-PK',
            'parent_id' => $bkpsdm->id,
        ]);

        // ─────────────────────────────────────────────
        // Dinas Kesehatan — children
        // ─────────────────────────────────────────────
        $dinkesSekr = Unor::create([
            'nama_unor' => 'Sekretariat',
            'kode_unor' => 'DINKES-SEKR',
            'singkatan' => 'Sekr',
            'parent_id' => $dinkes->id,
        ]);

        Unor::create([
            'nama_unor' => 'Sub Bagian Umum dan Kepegawaian',
            'kode_unor' => 'DINKES-SUB-UMUM',
            'parent_id' => $dinkesSekr->id,
        ]);

        Unor::create([
            'nama_unor' => 'Sub Bagian Keuangan',
            'kode_unor' => 'DINKES-SUB-KEU',
            'parent_id' => $dinkesSekr->id,
        ]);

        Unor::create([
            'nama_unor' => 'Sub Bagian Perencanaan',
            'kode_unor' => 'DINKES-SUB-REN',
            'parent_id' => $dinkesSekr->id,
        ]);

        Unor::create([
            'nama_unor' => 'Bidang Pelayanan Kesehatan',
            'kode_unor' => 'DINKES-BID-YANKES',
            'parent_id' => $dinkes->id,
        ]);

        Unor::create([
            'nama_unor' => 'Bidang Pencegahan dan Pengendalian Penyakit',
            'kode_unor' => 'DINKES-BID-P2P',
            'parent_id' => $dinkes->id,
        ]);

        Unor::create([
            'nama_unor' => 'Bidang Kesehatan Masyarakat',
            'kode_unor' => 'DINKES-BID-KESMAS',
            'parent_id' => $dinkes->id,
        ]);

        // ─────────────────────────────────────────────
        // Dinas Pendidikan — children
        // ─────────────────────────────────────────────
        $dikbudSekr = Unor::create([
            'nama_unor' => 'Sekretariat',
            'kode_unor' => 'DIKBUD-SEKR',
            'singkatan' => 'Sekr',
            'parent_id' => $dikbud->id,
        ]);

        Unor::create([
            'nama_unor' => 'Sub Bagian Umum dan Kepegawaian',
            'kode_unor' => 'DIKBUD-SUB-UMUM',
            'parent_id' => $dikbudSekr->id,
        ]);

        Unor::create([
            'nama_unor' => 'Sub Bagian Keuangan',
            'kode_unor' => 'DIKBUD-SUB-KEU',
            'parent_id' => $dikbudSekr->id,
        ]);

        Unor::create([
            'nama_unor' => 'Sub Bagian Perencanaan',
            'kode_unor' => 'DIKBUD-SUB-REN',
            'parent_id' => $dikbudSekr->id,
        ]);

        Unor::create([
            'nama_unor' => 'Bidang Pendidikan Anak Usia Dini',
            'kode_unor' => 'DIKBUD-BID-PAUD',
            'parent_id' => $dikbud->id,
        ]);

        Unor::create([
            'nama_unor' => 'Bidang Sekolah Dasar',
            'kode_unor' => 'DIKBUD-BID-SD',
            'parent_id' => $dikbud->id,
        ]);

        Unor::create([
            'nama_unor' => 'Bidang Sekolah Menengah Pertama',
            'kode_unor' => 'DIKBUD-BID-SMP',
            'parent_id' => $dikbud->id,
        ]);

        Unor::create([
            'nama_unor' => 'Bidang Guru dan Tenaga Kependidikan',
            'kode_unor' => 'DIKBUD-BID-GTK',
            'parent_id' => $dikbud->id,
        ]);
    }
}
