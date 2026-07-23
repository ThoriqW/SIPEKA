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
     * Hitung proyeksi pensiun per POSISI organisasi untuk 5 tahun ke depan.
     * Menggunakan model SOTK baru (node_organisasi).
     *
     * Returns [posisi_id => [1 => count, ..., 5 => count]]
     *
     * @param int|null $unitId Filter by unit ancestor (null = all)
     */
    public function hitungProyeksiPensiunPerPosisi(?int $unitId = null): array
    {
        $t = (int) date('Y');
        $result = [];

        $query = Pegawai::query()
            ->with('jabatanAsn')
            ->select(['id', 'tanggal_lahir', 'jenjang', 'jenis_kepegawaian', 'posisi_organisasi_id'])
            ->whereNotNull('posisi_organisasi_id');

        if ($unitId !== null) {
            $query->where('opd_id', $unitId);
        }

        $pegawaiList = $query->get();

        foreach ($pegawaiList as $pegawai) {
            $tanggalPensiun = $this->bupCalculator->hitungTanggalPensiun(
                $pegawai->tanggal_lahir,
                $pegawai->jenjang,
                $pegawai->jenis_kepegawaian,
                $pegawai->jabatanAsn->nama_jabatan_asn ?? null
            );
            $tahunPensiun = (int) $tanggalPensiun->format('Y');
            $posisiId = $pegawai->posisi_organisasi_id;

            for ($n = 1; $n <= 5; $n++) {
                if ($tahunPensiun === $t + ($n - 1)) {
                    $result[$posisiId][$n] = ($result[$posisiId][$n] ?? 0) + 1;
                }
            }
        }

        // Pastikan semua posisi memiliki array lengkap 1..5
        foreach ($result as $posisiId => $years) {
            for ($n = 1; $n <= 5; $n++) {
                $result[$posisiId][$n] = $result[$posisiId][$n] ?? 0;
            }
            ksort($result[$posisiId]);
        }

        return $result;
    }
}
