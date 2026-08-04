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
     * Hitung proyeksi pensiun per UNOR + jabatan untuk 5 tahun ke depan.
     * Returns [unor_id => [jabatan_id => ['counts' => [1=>count,...], 'pegawai' => [...]]]]
     *
     * @param int|null $opdId Filter by OPD (null = all OPDs)
     */
    public function hitungProyeksiPensiunPerJabatan(?int $opdId = null): array
    {
        $t = (int) date('Y');
        $result = [];

        $query = Pegawai::query()
            ->with(['jabatan', 'penempatanAktif'])
            ->select(['id', 'nip', 'nama', 'tanggal_lahir', 'jenis_kepegawaian', 'jabatan_id'])
            ->whereNotNull('jabatan_id');

        if ($opdId !== null) {
            $query->whereHas('penempatanAktif', fn($q) => $q->where('unor_id', $opdId));
        }

        $pegawaiList = $query->get();

        foreach ($pegawaiList as $pegawai) {
            // Hanya proses pegawai yang memiliki penempatan aktif
            if (!$pegawai->penempatanAktif) {
                continue;
            }

            $unorId = $pegawai->penempatanAktif->unor_id;
            $jabatanId = $pegawai->jabatan_id;

            $tanggalPensiun = $this->bupCalculator->hitungTanggalPensiun(
                $pegawai->tanggal_lahir,
                $pegawai->jabatan?->jenjang ?? '',
                $pegawai->jenis_kepegawaian,
                $pegawai->jabatan->nama_jabatan ?? null
            );
            $tahunPensiun = (int) $tanggalPensiun->format('Y');

            // Hitung offset tahun (1..5) dari tahun berjalan
            $n = $tahunPensiun - $t + 1;

            // Hanya catat jika dalam rentang proyeksi 5 tahun
            if ($n >= 1 && $n <= 5) {
                $result[$unorId][$jabatanId]['counts'][$n] = ($result[$unorId][$jabatanId]['counts'][$n] ?? 0) + 1;
                $result[$unorId][$jabatanId]['pegawai'][] = [
                    'nip'             => $pegawai->nip,
                    'nama'            => $pegawai->nama,
                    'tahun_pensiun'   => $tahunPensiun,
                ];
            }
        }

        // Pastikan semua entri memiliki array counts lengkap 1..5
        foreach ($result as $unorId => $jabatans) {
            foreach ($jabatans as $jabatanId => $data) {
                for ($n = 1; $n <= 5; $n++) {
                    $result[$unorId][$jabatanId]['counts'][$n] = $result[$unorId][$jabatanId]['counts'][$n] ?? 0;
                }
                ksort($result[$unorId][$jabatanId]['counts']);
            }
        }

        return $result;
    }
}
