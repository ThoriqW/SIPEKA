<?php

namespace App\Services;

use App\Models\Jabatan;
use App\Models\Pegawai;

class ProjectionService
{
    private int $replacementRatio = 1; // Rasio penggantian 1:1, dapat diubah

    public function __construct(
        private BupCalculator $bupCalculator,
    ) {}

    /**
     * Hitung proyeksi pensiun per tahun untuk 5 tahun ke depan.
     *
     * T = YEAR(today())
     * Pensiun Thn N = jumlah pegawai dgn YEAR(Tanggal Pensiun) = T + (N - 1)
     */
    public function hitungProyeksiPensiun(?int $opdId = null): array
    {
        $t = (int) date('Y');
        $result = [];

        $query = Pegawai::query();
        if ($opdId) {
            $query->where('opd_id', $opdId);
        }

        $pegawaiList = $query->get(['tanggal_lahir', 'jenjang', 'jenis_kepegawaian']);

        // Hitung tahun pensiun untuk setiap pegawai
        $tahunPensiunList = $pegawaiList->map(function (Pegawai $p) {
            $tanggalPensiun = $this->bupCalculator->hitungTanggalPensiun(
                $p->tanggal_lahir,
                $p->jenjang,
                $p->jenis_kepegawaian
            );
            return (int) $tanggalPensiun->format('Y');
        });

        for ($n = 1; $n <= 5; $n++) {
            $tahunTarget = $t + ($n - 1);
            $result[$n] = $tahunPensiunList->filter(fn($y) => $y === $tahunTarget)->count();
        }

        return $result;
    }

    /**
     * Hitung proyeksi kebutuhan per tahun untuk 5 tahun ke depan.
     *
     * Kebutuhan Thn 1 = max(kebutuhan - Bezetting, 0) + Pensiun Thn 1
     * Kebutuhan Thn N = Pensiun Thn N   (N = 2..5)
     */
    public function hitungProyeksiKebutuhan(Jabatan $jabatan, ?array $proyeksiPensiun = null): array
    {
        $proyeksiPensiun = $proyeksiPensiun ?? $this->hitungProyeksiPensiun($jabatan->opd_id);

        $bezetting = $jabatan->pegawai()->count();
        $kebutuhan = $jabatan->kebutuhan ?? 0;

        $result = [];
        for ($n = 1; $n <= 5; $n++) {
            if ($n === 1) {
                $result[$n] = max($kebutuhan - $bezetting, 0) + ($proyeksiPensiun[$n] ?? 0);
            } else {
                $result[$n] = ($proyeksiPensiun[$n] ?? 0) * $this->replacementRatio;
            }
        }

        return $result;
    }
}
