<?php

namespace App\Services;

use App\Models\Pegawai;

class ProjectionService
{
    public function __construct(
        private BupCalculator $bupCalculator,
    ) {}

    /**
     * Dapatkan mapping label tahun untuk 5 tahun proyeksi.
     * Key 1..5 → tahun aktual berbasis tahun berjalan.
     *
     * Contoh (tahun berjalan 2026): [1 => '2026', 2 => '2027', ..., 5 => '2030']
     */
    public function getTahunLabels(): array
    {
        $t = (int) date('Y');
        $labels = [];
        for ($n = 1; $n <= 5; $n++) {
            $labels[$n] = (string) ($t + $n - 1);
        }
        return $labels;
    }

    /**
     * Hitung proyeksi pensiun per jabatan untuk 5 tahun ke depan.
     * Returns [jabatan_id => [1 => count, ..., 5 => count]]
     *
     * @param int|null $opdId Filter by OPD (null = all OPDs)
     */
    public function hitungProyeksiPensiunPerJabatan(?int $opdId = null): array
    {
        $t = (int) date('Y');
        $result = [];

        $query = Pegawai::query()
            ->with('jabatan')
            ->select(['id', 'tanggal_lahir', 'jenjang', 'jenis_kepegawaian', 'jabatan_id'])
            ->whereNotNull('jabatan_id');

        if ($opdId !== null) {
            $query->where('opd_id', $opdId);
        }

        $pegawaiList = $query->get();

        foreach ($pegawaiList as $pegawai) {
            $tanggalPensiun = $this->bupCalculator->hitungTanggalPensiun(
                $pegawai->tanggal_lahir,
                $pegawai->jenjang,
                $pegawai->jenis_kepegawaian,
                $pegawai->jabatan->nama_jabatan ?? null
            );
            $tahunPensiun = (int) $tanggalPensiun->format('Y');
            $jabatanId = $pegawai->jabatan_id;

            for ($n = 1; $n <= 5; $n++) {
                if ($tahunPensiun === $t + ($n - 1)) {
                    $result[$jabatanId][$n] = ($result[$jabatanId][$n] ?? 0) + 1;
                }
            }
        }

        // Pastikan semua jabatan memiliki array lengkap 1..5
        foreach ($result as $jabatanId => $years) {
            for ($n = 1; $n <= 5; $n++) {
                $result[$jabatanId][$n] = $result[$jabatanId][$n] ?? 0;
            }
            ksort($result[$jabatanId]);
        }

        return $result;
    }
}
