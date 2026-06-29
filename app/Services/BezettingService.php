<?php

namespace App\Services;

use App\Models\Jabatan;
use Illuminate\Support\Facades\DB;

class BezettingService
{
    public function __construct(
        private BupCalculator $bupCalculator,
    ) {}

    /**
     * Hitung Bezetting = jumlah pegawai pada jabatan tertentu.
     */
    public function hitungBezetting(Jabatan $jabatan): int
    {
        return $jabatan->pegawai()->count();
    }

    /**
     * Hitung Selisih = Bezetting - Kebutuhan.
     * Untuk jabatan Struktural (kebutuhan = null), selisih = null.
     */
    public function hitungSelisih(Jabatan $jabatan): ?int
    {
        $bezetting = $this->hitungBezetting($jabatan);

        if ($jabatan->kebutuhan === null) {
            return null;
        }

        return $bezetting - $jabatan->kebutuhan;
    }

    /**
     * Hitung level (kedalaman) jabatan berdasarkan rantai induk_jabatan_id.
     * Level 0 = root "Instansi Pemerintah Kota Palu" (virtual)
     * Level 1 = Kepala OPD (induk_jabatan_id = null)
     * Level 2-4 = turunan
     */
    public function hitungLevel(Jabatan $jabatan): int
    {
        $level = 1; // mulai dari level 1
        $current = $jabatan;

        while ($current->induk_jabatan_id !== null) {
            $level++;
            $current = $current->induk;
        }

        return $level;
    }

    /**
     * Ambil data Bezetting untuk seluruh jabatan dalam satu OPD.
     * Return array dengan kolom: jabatan_id, nama_jabatan, level, kebutuhan, bezetting, selisih.
     */
    public function getBezettingPerOpd(int $opdId): array
    {
        $jabatanList = Jabatan::with('pegawai')
            ->where('opd_id', $opdId)
            ->get();

        return $this->buildTreeData($jabatanList);
    }

    /**
     * Bangun data tree untuk seluruh OPD (untuk layar Bezetting).
     * Root: "Instansi Pemerintah Kota Palu"
     */
    public function getBezettingSemuaOpd(): array
    {
        $jabatanList = Jabatan::with(['pegawai', 'opd'])
            ->get();

        $tree = $this->buildTreeData($jabatanList);

        // Tambahkan root virtual di level 0
        array_unshift($tree, [
            'id' => 0,
            'parent_id' => null,
            'level' => 0,
            'nama_jabatan' => 'Instansi Pemerintah Kota Palu',
            'jenis_jabatan' => null,
            'kelas_jabatan' => null,
            'kebutuhan' => null,
            'bezetting' => $jabatanList->flatMap->pegawai->unique('id')->count(),
            'selisih' => null,
            'pegawai' => [],
        ]);

        return $tree;
    }

    /**
     * Build tree data dari flat jabatan list.
     */
    private function buildTreeData($jabatanList): array
    {
        return $jabatanList->map(function (Jabatan $jabatan) {
            $bezetting = $jabatan->pegawai->count();

            return [
                'id' => $jabatan->id,
                'parent_id' => $jabatan->induk_jabatan_id,
                'level' => $this->hitungLevel($jabatan),
                'nama_jabatan' => $jabatan->nama_jabatan,
                'jenis_jabatan' => $jabatan->jenis_jabatan,
                'kelas_jabatan' => $jabatan->kelas_jabatan,
                'kebutuhan' => $jabatan->kebutuhan,
                'bezetting' => $bezetting,
                'selisih' => $jabatan->kebutuhan !== null ? $bezetting - $jabatan->kebutuhan : null,
                'pegawai' => $jabatan->pegawai->map(fn($p) => [
                    'nip' => $p->nip,
                    'nama' => $p->nama,
                ])->toArray(),
            ];
        })->toArray();
    }
}
