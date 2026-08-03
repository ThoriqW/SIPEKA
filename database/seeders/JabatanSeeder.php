<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\Sotk;
use App\Models\Unor;
use Illuminate\Database\Seeder;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBkpsdm();
        $this->seedDinkes();
        $this->seedDikbud();
    }

    /**
     * Helper: buat jabatan + SOTK entry.
     */
    private function createJabatan(array $jab, int $unorId): Jabatan
    {
        $jabatan = Jabatan::create($jab);
        Sotk::create([
            'unor_id'    => $unorId,
            'jabatan_id' => $jabatan->id,
        ]);
        return $jabatan;
    }

    /**
     * Helper: ekstrak nama_jabatan dan jenjang dari nama display.
     * Contoh: "Pranata Komputer Ahli Pertama" → ["Pranata Komputer", "Ahli Pertama"]
     */
    private function parseNama(string $displayName, string $jenisJabatan): array
    {
        if ($jenisJabatan === 'Pelaksana') {
            return [$displayName, 'Pelaksana'];
        }

        if ($jenisJabatan === 'Struktural') {
            return [$displayName, null]; // jenjang ditentukan manual
        }

        // Fungsional: coba ekstrak jenjang dari akhir nama
        $jenjangSuffixes = [
            'Ahli Utama',
            'Ahli Madya',
            'Ahli Muda',
            'Ahli Pertama',
            'Keterampilan - Penyelia',
            'Keterampilan - Mahir',
            'Keterampilan - Terampil',
            'Keterampilan - Pemula',
        ];

        foreach ($jenjangSuffixes as $suffix) {
            $pattern = '/\s+' . preg_quote($suffix, '/') . '$/';
            if (preg_match($pattern, $displayName)) {
                return [preg_replace($pattern, '', $displayName), $suffix];
            }
        }

        // Tidak ada jenjang di nama
        return [$displayName, null];
    }

    // ============================================================
    // BKPSDMD Kota Palu
    // ============================================================
    private function seedBkpsdm(): void
    {
        $bkpsdm    = Unor::where('kode_unor', 'BKPSDM')->firstOrFail();
        $sekr      = Unor::where('kode_unor', 'BKPSDM-SEKR')->firstOrFail();
        $subUmum   = Unor::where('kode_unor', 'BKPSDM-SUB-UMUM')->firstOrFail();
        $subKeu    = Unor::where('kode_unor', 'BKPSDM-SUB-KEU')->firstOrFail();
        $subRen    = Unor::where('kode_unor', 'BKPSDM-SUB-REN')->firstOrFail();
        $bidPpi    = Unor::where('kode_unor', 'BKPSDM-BID-PPI')->firstOrFail();
        $bidMp     = Unor::where('kode_unor', 'BKPSDM-BID-MP')->firstOrFail();
        $bidPk     = Unor::where('kode_unor', 'BKPSDM-BID-PK')->firstOrFail();

        $seq = 0;

        // --- BKPSDMD langsung ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Badan',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 15,
            'jenjang'       => 'Pimpinan Tinggi Pratama',
        ], $bkpsdm->id);

        // --- Sekretariat ---
        $this->createJabatan([
            'nama_jabatan'  => 'Sekretaris',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 13,
            'jenjang'       => 'Administrator',
        ], $sekr->id);

        // --- Sub Bagian Umum dan Kepegawaian ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Sub Bagian',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 10,
            'jenjang'       => 'Pengawas',
        ], $subUmum->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Pengelola Kepegawaian',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 6,
            'jenjang'       => 'Pelaksana',
        ], $subUmum->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Pengemudi',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 4,
            'jenjang'       => 'Pelaksana',
        ], $subUmum->id);

        // --- Sub Bagian Keuangan ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Sub Bagian',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 10,
            'jenjang'       => 'Pengawas',
        ], $subKeu->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Pengelola Keuangan',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 6,
            'jenjang'       => 'Pelaksana',
        ], $subKeu->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Bendahara',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 6,
            'jenjang'       => 'Pelaksana',
        ], $subKeu->id);

        // --- Sub Bagian Perencanaan ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Sub Bagian',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 10,
            'jenjang'       => 'Pengawas',
        ], $subRen->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Pranata Komputer',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang'       => 'Ahli Pertama',
        ], $subRen->id);

        // --- Bidang Pengadaan, Pemberhentian dan Informasi ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Bidang',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang'       => 'Administrator',
        ], $bidPpi->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Pranata Komputer',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang'       => 'Ahli Pertama',
        ], $bidPpi->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Pengelola Kepegawaian',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 6,
            'jenjang'       => 'Pelaksana',
        ], $bidPpi->id);

        // --- Bidang Mutasi dan Promosi ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Bidang',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang'       => 'Administrator',
        ], $bidMp->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Pengelola Kepegawaian',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 6,
            'jenjang'       => 'Pelaksana',
        ], $bidMp->id);

        // --- Bidang Pengembangan Kompetensi ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Bidang',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang'       => 'Administrator',
        ], $bidPk->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Widyaiswara',
            'kode_jabatan'  => sprintf('BKPSDM-%03d', ++$seq),
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 9,
            'jenjang'       => 'Ahli Muda',
        ], $bidPk->id);

    }

    // ============================================================
    // Dinas Kesehatan
    // ============================================================
    private function seedDinkes(): void
    {
        $dinkes    = Unor::where('kode_unor', 'DINKES')->firstOrFail();
        $sekr      = Unor::where('kode_unor', 'DINKES-SEKR')->firstOrFail();
        $bidYankes = Unor::where('kode_unor', 'DINKES-BID-YANKES')->firstOrFail();
        $bidP2p    = Unor::where('kode_unor', 'DINKES-BID-P2P')->firstOrFail();
        $bidKesmas = Unor::where('kode_unor', 'DINKES-BID-KESMAS')->firstOrFail();

        $seq = 0;

        // --- Dinkes langsung ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Dinas',
            'kode_jabatan'  => sprintf('DINKES-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 15,
            'jenjang'       => 'Pimpinan Tinggi Pratama',
        ], $dinkes->id);

        // --- Sekretariat ---
        $this->createJabatan([
            'nama_jabatan'  => 'Sekretaris',
            'kode_jabatan'  => sprintf('DINKES-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 13,
            'jenjang'       => 'Administrator',
        ], $sekr->id);

        // --- Bidang Pelayanan Kesehatan ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Bidang',
            'kode_jabatan'  => sprintf('DINKES-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang'       => 'Administrator',
        ], $bidYankes->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Dokter',
            'kode_jabatan'  => sprintf('DINKES-%03d', ++$seq),
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 9,
            'jenjang'       => 'Ahli Pertama',
        ], $bidYankes->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Perawat',
            'kode_jabatan'  => sprintf('DINKES-%03d', ++$seq),
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 7,
            'jenjang'       => 'Ahli Pertama',
        ], $bidYankes->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Administrator Kesehatan',
            'kode_jabatan'  => sprintf('DINKES-%03d', ++$seq),
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang'       => 'Ahli Pertama',
        ], $bidYankes->id);

        // --- Bidang Pencegahan dan Pengendalian Penyakit ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Bidang',
            'kode_jabatan'  => sprintf('DINKES-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang'       => 'Administrator',
        ], $bidP2p->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Epidemiolog Kesehatan',
            'kode_jabatan'  => sprintf('DINKES-%03d', ++$seq),
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang'       => 'Ahli Pertama',
        ], $bidP2p->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Entomolog Kesehatan',
            'kode_jabatan'  => sprintf('DINKES-%03d', ++$seq),
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang'       => 'Ahli Pertama',
        ], $bidP2p->id);

        // --- Bidang Kesehatan Masyarakat ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Bidang',
            'kode_jabatan'  => sprintf('DINKES-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang'       => 'Administrator',
        ], $bidKesmas->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Nutrisionis',
            'kode_jabatan'  => sprintf('DINKES-%03d', ++$seq),
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang'       => 'Ahli Pertama',
        ], $bidKesmas->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Administrator Kesehatan',
            'kode_jabatan'  => sprintf('DINKES-%03d', ++$seq),
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang'       => null,
        ], $bidKesmas->id);

    }

    // ============================================================
    // Dinas Pendidikan
    // ============================================================
    private function seedDikbud(): void
    {
        $dikbud   = Unor::where('kode_unor', 'DIKBUD')->firstOrFail();
        $sekr     = Unor::where('kode_unor', 'DIKBUD-SEKR')->firstOrFail();
        $bidPaud  = Unor::where('kode_unor', 'DIKBUD-BID-PAUD')->firstOrFail();
        $bidSd    = Unor::where('kode_unor', 'DIKBUD-BID-SD')->firstOrFail();
        $bidSmp   = Unor::where('kode_unor', 'DIKBUD-BID-SMP')->firstOrFail();
        $bidGtk   = Unor::where('kode_unor', 'DIKBUD-BID-GTK')->firstOrFail();

        $seq = 0;

        // --- Dikbud langsung ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Dinas',
            'kode_jabatan'  => sprintf('DIKBUD-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 15,
            'jenjang'       => 'Pimpinan Tinggi Pratama',
        ], $dikbud->id);

        // --- Sekretariat ---
        $this->createJabatan([
            'nama_jabatan'  => 'Sekretaris',
            'kode_jabatan'  => sprintf('DIKBUD-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 13,
            'jenjang'       => 'Administrator',
        ], $sekr->id);

        // --- Bidang PAUD ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Bidang',
            'kode_jabatan'  => sprintf('DIKBUD-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang'       => 'Administrator',
        ], $bidPaud->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Analis Kebijakan',
            'kode_jabatan'  => sprintf('DIKBUD-%03d', ++$seq),
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang'       => 'Ahli Pertama',
        ], $bidPaud->id);

        // --- Bidang SD ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Bidang',
            'kode_jabatan'  => sprintf('DIKBUD-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang'       => 'Administrator',
        ], $bidSd->id);

        // --- Bidang SMP ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Bidang',
            'kode_jabatan'  => sprintf('DIKBUD-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang'       => 'Administrator',
        ], $bidSmp->id);

        // --- Bidang GTK ---
        $this->createJabatan([
            'nama_jabatan'  => 'Kepala Bidang',
            'kode_jabatan'  => sprintf('DIKBUD-%03d', ++$seq),
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang'       => 'Administrator',
        ], $bidGtk->id);

        $this->createJabatan([
            'nama_jabatan'  => 'Pengelola Kepegawaian',
            'kode_jabatan'  => sprintf('DIKBUD-%03d', ++$seq),
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 6,
            'jenjang'       => 'Pelaksana',
        ], $bidGtk->id);

    }
}
