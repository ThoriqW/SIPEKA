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
     * Hitung proyeksi pensiun per OPD untuk 5 tahun ke depan.
     * Returns [opd_id => [1 => count, ..., 5 => count]]
     * Lebih efisien untuk layar Kebutuhan/Bezetting karena satu query untuk semua OPD.
     */
    public function hitungProyeksiPensiunPerOpd(): array
    {
        $t = (int) date('Y');
        $result = [];

        $pegawaiList = Pegawai::query()
            ->select(['id', 'tanggal_lahir', 'jenjang', 'jenis_kepegawaian', 'opd_id'])
            ->get();

        foreach ($pegawaiList as $pegawai) {
            $tanggalPensiun = $this->bupCalculator->hitungTanggalPensiun(
                $pegawai->tanggal_lahir,
                $pegawai->jenjang,
                $pegawai->jenis_kepegawaian
            );
            $tahunPensiun = (int) $tanggalPensiun->format('Y');
            $opdId = $pegawai->opd_id;

            for ($n = 1; $n <= 5; $n++) {
                if ($tahunPensiun === $t + ($n - 1)) {
                    $result[$opdId][$n] = ($result[$opdId][$n] ?? 0) + 1;
                }
            }
        }

        // Pastikan semua OPD memiliki array lengkap 1..5
        foreach ($result as $opdId => $years) {
            for ($n = 1; $n <= 5; $n++) {
                $result[$opdId][$n] = $result[$opdId][$n] ?? 0;
            }
            ksort($result[$opdId]);
        }

        return $result;
    }

    /**
     * Hitung proyeksi pensiun untuk satu jabatan spesifik.
     *
     * @return array [1 => count, ..., 5 => count]
     */
    public function hitungProyeksiPensiunByJabatan(?int $jabatanId): array
    {
        $t = (int) date('Y');
        $result = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        if (!$jabatanId) {
            return $result;
        }

        $pegawaiList = Pegawai::query()
            ->where('jabatan_id', $jabatanId)
            ->get(['tanggal_lahir', 'jenjang', 'jenis_kepegawaian']);

        foreach ($pegawaiList as $pegawai) {
            $tanggalPensiun = $this->bupCalculator->hitungTanggalPensiun(
                $pegawai->tanggal_lahir,
                $pegawai->jenjang,
                $pegawai->jenis_kepegawaian
            );
            $tahunPensiun = (int) $tanggalPensiun->format('Y');

            for ($n = 1; $n <= 5; $n++) {
                if ($tahunPensiun === $t + ($n - 1)) {
                    $result[$n]++;
                }
            }
        }

        return $result;
    }

    /**
     * Hitung proyeksi pensiun per jabatan untuk 5 tahun ke depan.
     * Returns [jabatan_id => [1 => count, ..., 5 => count]]
     * Lebih efisien untuk FlattenedTreeService karena satu query untuk semua jabatan.
     *
     * @param int|null $opdId Filter by OPD (null = all OPDs)
     */
    public function hitungProyeksiPensiunPerJabatan(?int $opdId = null): array
    {
        $t = (int) date('Y');
        $result = [];

        $query = Pegawai::query()
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
                $pegawai->jenis_kepegawaian
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

    /**
     * Hitung proyeksi kebutuhan per tahun untuk 5 tahun ke depan.
     *
     * Kebutuhan Thn 1 = max(kebutuhan - Bezetting, 0) + Pensiun Thn 1
     * Kebutuhan Thn N = Pensiun Thn N — proyeksi pensiun DIHITUNG PER JABATAN
     *     (hanya pegawai yang menduduki jabatan INI yang diperhitungkan)
     */
    public function hitungProyeksiKebutuhan(Jabatan $jabatan, ?array $proyeksiPensiun = null): array
    {
        $proyeksiPensiun = $proyeksiPensiun ?? $this->hitungProyeksiPensiunByJabatan($jabatan->id);

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
