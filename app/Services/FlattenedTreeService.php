<?php

namespace App\Services;

use App\Models\Unor;
use App\Models\KebutuhanPegawai;
use App\Models\PenempatanPegawai;

class FlattenedTreeService
{
    public function __construct(
        private ProjectionService $projectionService,
        private BupCalculator $bupCalculator,
    ) {}

    /**
     * Build flat depth-first tree array from UNOR hierarchy + SOTK junction.
     *
     * Tree structure:
     *   UNOR (level 0, 1, 2, ...) — folder nodes
     *   └── Jabatan (leaf nodes, level = parent UNOR level + 1)
     *
     * The root UNOR (parent_id = null) sits at level 0.
     * UNOR rows aggregate kebutuhan, bezetting, and pegawai from all descendants.
     *
     * @param int|null $unorId         Filter by UNOR (null = all UNORs)
     * @param bool     $withProjections Compute pensiun & kebutuhan proyeksi Thn 1-5 per row
     * @return array   Flat ordered rows with keys: id, parent_id, type, level,
     *                 nama_jabatan, jenis_jabatan, jenjang, kelas_jabatan,
     *                 kebutuhan, bezetting, selisih, pegawai[], has_children,
     *                 unor_id, kode_unor, kebutuhan_proyeksi[], pensiun_proyeksi[],
     *                 pegawai_pensiun[]
     */
    public function buildFlatTree(
        ?int $unorId = null,
        bool $withProjections = false,
    ): array {
        // ── Load UNORs ──
        $unorQuery = Unor::with(['children', 'sotkEntries.jabatan']);
        if ($unorId !== null) {
            $ids = $this->getAllDescendantUnorIds($unorId);
            $ids[] = $unorId;
            $unorQuery->whereIn('id', $ids);
        }
        $allUnor = $unorQuery->orderBy('kode_unor')->get()->keyBy('id');

        // ── Build UNOR parent→children map ──
        $unorChildrenMap = [];
        $rootUnors = [];
        foreach ($allUnor as $unor) {
            $parentKey = $unor->parent_id ?? 0;
            if ($unorId !== null && $unor->id === $unorId) {
                $parentKey = 0;
            }
            $unorChildrenMap[$parentKey][] = $unor;
            if ($parentKey === 0) {
                $rootUnors[] = $unor;
            }
        }

        // ── Compute descendant IDs for map filtering ──
        $descendantIds = ($unorId !== null) ? [...$this->getAllDescendantUnorIds($unorId), $unorId] : null;

        // ── Pre-load kebutuhan, bezetting & pegawai per (unor_id, jabatan_id) ──
        $kebutuhanMap = $this->buildKebutuhanMap($descendantIds);
        $bezettingMap = $this->buildBezettingMap($descendantIds);
        $pegawaiMap   = $this->buildPegawaiMap($descendantIds);

        // ── Pre-compute pensiun projections per jabatan ──
        $proyeksiPensiunPerJabatan = $withProjections
            ? $this->projectionService->hitungProyeksiPensiunPerJabatan($unorId)
            : [];

        $result = [];

        // ── Traverse UNOR tree depth-first ──
        foreach ($rootUnors as $rootUnor) {
            $this->flattenUnor(
                unor: $rootUnor,
                parentId: null,
                level: 0,
                result: $result,
                unorChildrenMap: $unorChildrenMap,
                kebutuhanMap: $kebutuhanMap,
                bezettingMap: $bezettingMap,
                pegawaiMap: $pegawaiMap,
                proyeksiPensiunPerJabatan: $proyeksiPensiunPerJabatan,
                withProjections: $withProjections,
            );
        }

        // ── Post-process: propagate child UNOR totals upward ──
        $this->propagateTotalsUpward($result);

        return $result;
    }

    /**
     * Recursively flatten a UNOR node: UNOR row first, then jabatan rows, then child UNORs.
     */
    private function flattenUnor(
        Unor $unor,
        ?string $parentId,
        int $level,
        array &$result,
        array $unorChildrenMap,
        array $kebutuhanMap,
        array $bezettingMap,
        array $pegawaiMap,
        array $proyeksiPensiunPerJabatan,
        bool $withProjections,
    ): void {
        $unorIdStr = 'u-' . $unor->id;
        $childUnors = $unorChildrenMap[$unor->id] ?? [];
        $sotkEntries = $unor->sotkEntries ?? collect();

        $hasChildren = !empty($childUnors) || $sotkEntries->isNotEmpty();

        // ── Aggregate totals for UNOR row (direct SOTK only) ──
        $aggKebutuhan = 0;
        $aggBezetting = 0;

        foreach ($sotkEntries as $sotk) {
            $jabId = $sotk->jabatan_id;
            $aggKebutuhan += $kebutuhanMap[$unor->id][$jabId] ?? 0;
            $aggBezetting += $bezettingMap[$unor->id][$jabId] ?? 0;
        }

        // ── UNOR row (tidak menampilkan pegawai — hanya jabatan) ──
        $result[] = [
            'id'                  => $unorIdStr,
            'parent_id'           => $parentId,
            'type'                => 'unor',
            'level'               => $level,
            'nama_jabatan'        => $unor->nama_unor,
            'jenis_jabatan'       => null,
            'jenjang'             => null,
            'kelas_jabatan'       => null,
            'kebutuhan'           => $aggKebutuhan,
            'bezetting'           => $aggBezetting,
            'selisih'             => $aggBezetting - $aggKebutuhan,
            'pegawai'             => [],
            'has_children'        => $hasChildren,
            'unor_id'             => $unor->id,
            'kode_unor'           => $unor->kode_unor,
            'kebutuhan_proyeksi'  => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'pensiun_proyeksi'    => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'pegawai_pensiun'     => [],
        ];

        // ── Jabatan rows (leaf nodes under this UNOR) ──
        $jabatanLevel = $level + 1;
        foreach ($sotkEntries as $sotk) {
            if (!$sotk->jabatan) continue;
            $jabatan = $sotk->jabatan;
            $jabId = $jabatan->id;

            $kebutuhan = $kebutuhanMap[$unor->id][$jabId] ?? 0;
            $bezetting = $bezettingMap[$unor->id][$jabId] ?? 0;
            $selisih = $bezetting - $kebutuhan;

            $jabatanPensiun = $proyeksiPensiunPerJabatan[$jabId] ?? [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

            $row = [
                'id'              => $jabId,
                'parent_id'       => $unorIdStr,
                'type'            => 'jabatan',
                'level'           => $jabatanLevel,
                'nama_jabatan'    => $jabatan->nama_jabatan,
                'jenis_jabatan'   => $jabatan->jenis_jabatan,
                'jenjang'         => $jabatan->jenjang,
                'kelas_jabatan'   => $jabatan->kelas_jabatan,
                'kebutuhan'       => $kebutuhan,
                'bezetting'       => $bezetting,
                'selisih'         => $selisih,
                'pegawai'         => $pegawaiMap[$unor->id][$jabId] ?? [],
                'has_children'    => false,
                'unor_id'         => $unor->id,
                'kode_unor'       => $unor->kode_unor,
                'kebutuhan_proyeksi' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                'pensiun_proyeksi'   => $jabatanPensiun,
                'pegawai_pensiun'    => [],
            ];

            if ($withProjections) {
                $row['kebutuhan_proyeksi'] = [
                    1 => $jabatanPensiun[1] ?? 0,
                    2 => $jabatanPensiun[2] ?? 0,
                    3 => $jabatanPensiun[3] ?? 0,
                    4 => $jabatanPensiun[4] ?? 0,
                    5 => $jabatanPensiun[5] ?? 0,
                ];
            }

            $result[] = $row;
        }

        // ── Recurse into child UNORs ──
        foreach ($childUnors as $childUnor) {
            $this->flattenUnor(
                unor: $childUnor,
                parentId: $unorIdStr,
                level: $level + 1,
                result: $result,
                unorChildrenMap: $unorChildrenMap,
                kebutuhanMap: $kebutuhanMap,
                bezettingMap: $bezettingMap,
                pegawaiMap: $pegawaiMap,
                proyeksiPensiunPerJabatan: $proyeksiPensiunPerJabatan,
                withProjections: $withProjections,
            );
        }
    }

    /**
     * Build map of kebutuhan per (unor_id, jabatan_id) from kebutuhan_pegawai table.
     */
    private function buildKebutuhanMap(?array $unorIds = null): array
    {
        $query = KebutuhanPegawai::whereNull('tahun');
        if ($unorIds !== null) {
            $query->whereIn('unor_id', $unorIds);
        }
        $map = [];
        foreach ($query->get() as $k) {
            $map[$k->unor_id][$k->jabatan_id] = $k->jumlah;
        }
        return $map;
    }

    /**
     * Build map of bezetting per (unor_id, jabatan_id) from penempatan_pegawai table.
     */
    private function buildBezettingMap(?array $unorIds = null): array
    {
        $query = PenempatanPegawai::where('is_active', true);
        if ($unorIds !== null) {
            $query->whereIn('unor_id', $unorIds);
        }
        $map = [];
        foreach ($query->get() as $p) {
            $map[$p->unor_id][$p->jabatan_id] = ($map[$p->unor_id][$p->jabatan_id] ?? 0) + 1;
        }
        return $map;
    }

    /**
     * Build map of pegawai per (unor_id, jabatan_id) from active penempatan.
     *
     * @return array<int, array<int, array<int, array{ nip: string, nama: string }>>>
     */
    private function buildPegawaiMap(?array $unorIds = null): array
    {
        $query = PenempatanPegawai::with('pegawai')->where('is_active', true);
        if ($unorIds !== null) {
            $query->whereIn('unor_id', $unorIds);
        }
        $map = [];
        foreach ($query->get() as $p) {
            if (!$p->pegawai) continue;
            $map[$p->unor_id][$p->jabatan_id][] = [
                'nip'  => $p->pegawai->nip,
                'nama' => $p->pegawai->nama,
            ];
        }
        return $map;
    }

    /**
     * Propagate child UNOR totals (kebutuhan, bezetting, pegawai) upward to parents.
     * Walks the flat array in reverse so children are processed before parents.
     */
    private function propagateTotalsUpward(array &$result): void
    {
        $index = [];
        foreach ($result as $i => $row) {
            $index[$row['id']] = $i;
        }

        for ($i = count($result) - 1; $i >= 0; $i--) {
            $row = $result[$i];
            if ($row['type'] !== 'unor') continue;

            $parentId = $row['parent_id'];
            if ($parentId === null || !isset($index[$parentId])) continue;

            $pIdx = $index[$parentId];
            $result[$pIdx]['kebutuhan'] += $row['kebutuhan'];
            $result[$pIdx]['bezetting'] += $row['bezetting'];
            $result[$pIdx]['selisih'] = $result[$pIdx]['bezetting'] - $result[$pIdx]['kebutuhan'];
        }
    }

    /**
     * Get all descendant UNOR IDs for a given UNOR (BFS).
     *
     * @return int[]
     */
    private function getAllDescendantUnorIds(int $unorId): array
    {
        $ids = [];
        $queue = Unor::where('parent_id', $unorId)->pluck('id')->toArray();

        while (!empty($queue)) {
            $ids = array_merge($ids, $queue);
            $queue = Unor::whereIn('parent_id', $queue)->pluck('id')->toArray();
        }

        return $ids;
    }
}
