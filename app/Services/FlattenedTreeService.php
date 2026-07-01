<?php

namespace App\Services;

use App\Models\Jabatan;
use App\Models\Opd;
use Illuminate\Support\Collection;

class FlattenedTreeService
{
    public function __construct(
        private ProjectionService $projectionService,
    ) {}

    /**
     * Build flat depth-first tree array from jabatan hierarchy.
     *
     * @param int|null $opdId         Filter by OPD (null = all OPDs)
     * @param bool     $includeRoot   Prepend virtual level-0 root row "Instansi Pemerintah Kota Palu"
     * @param bool     $withProjections Compute pensiun & kebutuhan proyeksi Thn 1-5 per row
     * @return array   Flat ordered rows with keys: id, parent_id, level, nama_jabatan,
     *                 jenis_jabatan, kelas_jabatan, kebutuhan, bezetting, selisih,
     *                 pegawai[], has_children, opd_id, kebutuhan_proyeksi[], pensiun_proyeksi[]
     */
    public function buildFlatTree(
        ?int $opdId = null,
        bool $includeRoot = false,
        bool $withProjections = false,
    ): array {
        $query = Jabatan::with(['pegawai', 'opd']);

        if ($opdId !== null) {
            $query->where('opd_id', $opdId);
        }

        $allJabatan = $query->orderBy('opd_id')->orderBy('nama_jabatan')->get();

        // Build parent → children map (key 0 = root-level: induk_jabatan_id = null)
        $childrenMap = [];
        foreach ($allJabatan as $j) {
            $parentKey = $j->induk_jabatan_id ?? 0;
            $childrenMap[$parentKey][] = $j;
        }

        // Pre-compute per-jabatan pensiun projections
        $proyeksiPensiunPerJabatan = $withProjections
            ? $this->projectionService->hitungProyeksiPensiunPerJabatan($opdId)
            : [];

        $result = [];

        // Optional virtual root
        if ($includeRoot) {
            $result[] = $this->makeRootRow($allJabatan, $proyeksiPensiunPerJabatan, $withProjections);
        }

        // Depth-first traversal from level-1 roots
        $roots = $childrenMap[0] ?? [];
        foreach ($roots as $root) {
            $this->flattenNode(
                jabatan: $root,
                parentId: $includeRoot ? 0 : null,
                level: 1,
                result: $result,
                childrenMap: $childrenMap,
                proyeksiPensiunPerJabatan: $proyeksiPensiunPerJabatan,
                withProjections: $withProjections,
            );
        }

        return $result;
    }

    /**
     * Recursively flatten a jabatan node and its children into the result array.
     */
    private function flattenNode(
        Jabatan $jabatan,
        ?int $parentId,
        int $level,
        array &$result,
        array $childrenMap,
        array $proyeksiPensiunPerJabatan,
        bool $withProjections,
    ): void {
        if ($level > 4) {
            return; // hard constraint max level 4
        }

        $bezetting = $jabatan->pegawai->count();
        $kebutuhan = $jabatan->kebutuhan;
        $selisih = $kebutuhan !== null ? $bezetting - $kebutuhan : null;
        $jabatanPensiun = $proyeksiPensiunPerJabatan[$jabatan->id] ?? [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        $row = [
            'id'              => $jabatan->id,
            'parent_id'       => $parentId,
            'level'           => $level,
            'nama_jabatan'    => $jabatan->nama_jabatan,
            'jenis_jabatan'   => $jabatan->jenis_jabatan,
            'jenjang'         => $jabatan->jenjang,
            'kelas_jabatan'   => $jabatan->kelas_jabatan,
            'kebutuhan'       => $kebutuhan,
            'bezetting'       => $bezetting,
            'selisih'         => $selisih,
            'pegawai'         => $jabatan->pegawai->map(fn($p) => [
                'nip'  => $p->nip,
                'nama' => $p->nama,
            ])->toArray(),
            'has_children'    => !empty($childrenMap[$jabatan->id]),
            'opd_id'          => $jabatan->opd_id,
            'kebutuhan_proyeksi' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'pensiun_proyeksi'   => $jabatanPensiun,
        ];

        if ($withProjections && $kebutuhan !== null) {
            // Kebutuhan Thn 1 = max(kebutuhan - bezetting, 0) + Pensiun Thn 1
            // Kebutuhan Thn 2-5 = Pensiun Thn N (per jabatan)
            $row['kebutuhan_proyeksi'] = [
                1 => max($kebutuhan - $bezetting, 0) + ($jabatanPensiun[1] ?? 0),
                2 => $jabatanPensiun[2] ?? 0,
                3 => $jabatanPensiun[3] ?? 0,
                4 => $jabatanPensiun[4] ?? 0,
                5 => $jabatanPensiun[5] ?? 0,
            ];
        }

        $result[] = $row;

        // Recurse into children
        if (!empty($childrenMap[$jabatan->id])) {
            foreach ($childrenMap[$jabatan->id] as $child) {
                $this->flattenNode(
                    jabatan: $child,
                    parentId: $jabatan->id,
                    level: $level + 1,
                    result: $result,
                    childrenMap: $childrenMap,
                    proyeksiPensiunPerJabatan: $proyeksiPensiunPerJabatan,
                    withProjections: $withProjections,
                );
            }
        }
    }

    /**
     * Build the virtual root row (Level 0: "Instansi Pemerintah Kota Palu").
     */
    private function makeRootRow(Collection $allJabatan, array $proyeksiPensiunPerJabatan, bool $withProjections): array
    {
        // Aggregate totals from all jabatans
        $totalPensiun = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $totalKebutuhan = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        if ($withProjections) {
            foreach ($proyeksiPensiunPerJabatan as $jabatanId => $years) {
                for ($n = 1; $n <= 5; $n++) {
                    $totalPensiun[$n] += $years[$n] ?? 0;
                }
            }
            // Kebutuhan Thn 1 = SUM(max(kebutuhan - bezetting, 0)) + total Pensiun Thn 1
            $totalShortfall = 0;
            foreach ($allJabatan as $j) {
                if ($j->kebutuhan !== null) {
                    $bez = $j->pegawai->count();
                    $totalShortfall += max($j->kebutuhan - $bez, 0);
                }
            }
            $totalKebutuhan[1] = $totalShortfall + $totalPensiun[1];
            for ($n = 2; $n <= 5; $n++) {
                $totalKebutuhan[$n] = $totalPensiun[$n];
            }
        }

        $totalBezetting = $allJabatan->flatMap->pegawai->unique('id')->count();
        $totalKebutuhanCount = 0;
        foreach ($allJabatan as $j) {
            if ($j->kebutuhan !== null) {
                $totalKebutuhanCount += $j->kebutuhan;
            }
        }

        return [
            'id'                  => 0,
            'parent_id'           => null,
            'level'               => 0,
            'nama_jabatan'        => 'Instansi Pemerintah Kota Palu',
            'jenis_jabatan'       => null,
            'jenjang'             => null,
            'kelas_jabatan'       => null,
            'kebutuhan'           => $totalKebutuhanCount,
            'bezetting'           => $totalBezetting,
            'selisih'             => $totalBezetting - $totalKebutuhanCount,
            'pegawai'             => [],
            'has_children'        => true,
            'opd_id'              => null,
            'kebutuhan_proyeksi'  => $totalKebutuhan,
            'pensiun_proyeksi'    => $totalPensiun,
        ];
    }
}
