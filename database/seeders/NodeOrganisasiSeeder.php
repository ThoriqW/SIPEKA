<?php

namespace Database\Seeders;

use App\Models\NodeOrganisasi;
use App\Models\Opd;
use App\Models\Pegawai;
use App\Services\KodeNodeGenerator;
use Illuminate\Database\Seeder;

/**
 * Seeder mengisi tabel node_organisasi (UNIT + POSISI) sebagai struktur organisasi.
 *
 * Struktur:
 *   Pemerintah Kota Palu (UNIT, root)
 *   ├── Dinas Pendidikan dan Kebudayaan (UNIT)
 *   │   ├── Kepala Dinas Pendidikan dan Kebudayaan (POSISI)
 *   │   ├── Sekretariat (UNIT)
 *   │   │   ├── Sub Bagian Keuangan (UNIT)
 *   │   │   │   ├── Pengelola Keuangan 1 (POSISI)
 *   │   │   │   ├── Pengelola Keuangan 2 (POSISI)
 *   │   │   │   └── Pengelola Keuangan 3 (POSISI)
 *   │   ├── Bidang Sekolah Dasar (UNIT)
 *   │   │   ├── Guru 1..10 (POSISI)
 *   │   │   └── Operator Sekolah 1..5 (POSISI)
 *   └── Dinas Kesehatan (UNIT)
 *       ├── Kepala Dinas Kesehatan (POSISI)
 *       └── Bidang Pelayanan Kesehatan (UNIT)
 *           ├── Dokter 1..5 (POSISI)
 *           └── Perawat 1..8 (POSISI)
 */
class NodeOrganisasiSeeder extends Seeder
{
    public function run(): void
    {
        if (NodeOrganisasi::exists()) {
            return;
        }

        $generator = app(KodeNodeGenerator::class);

        // ─── Root ───
        $root = NodeOrganisasi::create([
            'nama' => 'Pemerintah Kota Palu',
            'kode' => 'PEMKOT-PALU',
            'jenis' => 'UNIT',
            'parent_id' => null,
        ]);

        $opdList = Opd::all();

        foreach ($opdList as $opd) {
            // ─── UNIT OPD ───
            $unitOpd = NodeOrganisasi::create([
                'nama' => $opd->nama_opd,
                'kode' => $opd->kode_opd,
                'jenis' => 'UNIT',
                'parent_id' => $root->id,
            ]);

            // ─── POSISI: Kepala OPD ───
            $pimpinan = NodeOrganisasi::create([
                'nama' => 'Kepala ' . $opd->nama_opd,
                'kode' => $generator->generateKodePosisi($unitOpd, 'Kepala ' . $opd->nama_opd),
                'jenis' => 'POSISI',
                'parent_id' => $unitOpd->id,
                'kelas_jabatan' => 15,
            ]);

            // ─── Unit turunan berdasarkan OPD ───
            if ($opd->kode_opd === 'DIKBUD') {
                $this->seedDikbud($unitOpd, $generator);
            } elseif ($opd->kode_opd === 'DINKES') {
                $this->seedDinkes($unitOpd, $generator);
            }
        }
    }

    private function seedDikbud(NodeOrganisasi $unitOpd, KodeNodeGenerator $gen): void
    {
        // Sekretariat (UNIT)
        $sekretariat = NodeOrganisasi::create([
            'nama' => 'Sekretariat',
            'kode' => $gen->generateKodeUnit('Sekretariat Dikbud'),
            'jenis' => 'UNIT',
            'parent_id' => $unitOpd->id,
        ]);

        // Sub Bagian Keuangan (UNIT)
        $subKeuangan = NodeOrganisasi::create([
            'nama' => 'Sub Bagian Keuangan',
            'kode' => $gen->generateKodeUnit('Sub Keuangan Dikbud'),
            'jenis' => 'UNIT',
            'parent_id' => $sekretariat->id,
        ]);

        // Pengelola Keuangan (3 POSISI — kebutuhan = 3)
        for ($i = 1; $i <= 3; $i++) {
            NodeOrganisasi::create([
                'nama' => 'Pengelola Keuangan ' . $i,
                'kode' => $gen->generateKodePosisi($subKeuangan, 'Pengelola Keuangan'),
                'jenis' => 'POSISI',
                'parent_id' => $subKeuangan->id,
                'kelas_jabatan' => 6,
            ]);
        }

        // Bidang Sekolah Dasar (UNIT)
        $bidangSd = NodeOrganisasi::create([
            'nama' => 'Bidang Sekolah Dasar',
            'kode' => $gen->generateKodeUnit('Bidang SD'),
            'jenis' => 'UNIT',
            'parent_id' => $unitOpd->id,
        ]);

        // Guru (10 POSISI — kebutuhan = 10)
        for ($i = 1; $i <= 10; $i++) {
            NodeOrganisasi::create([
                'nama' => 'Guru ' . $i,
                'kode' => $gen->generateKodePosisi($bidangSd, 'Guru'),
                'jenis' => 'POSISI',
                'parent_id' => $bidangSd->id,
                'kelas_jabatan' => 8,
            ]);
        }

        // Operator Sekolah (5 POSISI — kebutuhan = 5)
        for ($i = 1; $i <= 5; $i++) {
            NodeOrganisasi::create([
                'nama' => 'Operator Sekolah ' . $i,
                'kode' => $gen->generateKodePosisi($bidangSd, 'Operator Sekolah'),
                'jenis' => 'POSISI',
                'parent_id' => $bidangSd->id,
                'kelas_jabatan' => 5,
            ]);
        }
    }

    private function seedDinkes(NodeOrganisasi $unitOpd, KodeNodeGenerator $gen): void
    {
        // Bidang Pelayanan Kesehatan (UNIT)
        $bidangYankes = NodeOrganisasi::create([
            'nama' => 'Bidang Pelayanan Kesehatan',
            'kode' => $gen->generateKodeUnit('Bid Yankes'),
            'jenis' => 'UNIT',
            'parent_id' => $unitOpd->id,
        ]);

        // Dokter (5 POSISI — kebutuhan = 5)
        for ($i = 1; $i <= 5; $i++) {
            NodeOrganisasi::create([
                'nama' => 'Dokter ' . $i,
                'kode' => $gen->generateKodePosisi($bidangYankes, 'Dokter'),
                'jenis' => 'POSISI',
                'parent_id' => $bidangYankes->id,
                'kelas_jabatan' => 9,
            ]);
        }

        // Perawat (8 POSISI — kebutuhan = 8)
        for ($i = 1; $i <= 8; $i++) {
            NodeOrganisasi::create([
                'nama' => 'Perawat ' . $i,
                'kode' => $gen->generateKodePosisi($bidangYankes, 'Perawat'),
                'jenis' => 'POSISI',
                'parent_id' => $bidangYankes->id,
                'kelas_jabatan' => 7,
            ]);
        }
    }
}
