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
        $allUnor = $unorQuery->get()->keyBy('id');

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

        // ── Sort children: OPD level by nama_unor, deeper levels by kode_unor ──
        $pemkotId = Unor::whereNull('parent_id')->value('id');
        foreach ($unorChildrenMap as $parentKey => &$children) {
            if ($parentKey == $pemkotId) {
                // PEMKOT's direct children = OPDs → sort by nama_unor
                usort($children, fn($a, $b) => strcmp($a->nama_unor, $b->nama_unor));
            } else {
                // All other levels → sort by kode_unor
                usort($children, fn($a, $b) => strcmp($a->kode_unor, $b->kode_unor));
            }
        }
        unset($children);

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

        // ── Build jabatan rows first to compute UNOR-level aggregates ──
        $jabatanLevel = $level + 1;
        $jabatanRows = [];

        $aggKebutuhan = 0;
        $aggBezetting = 0;
        $aggPensiunProyeksi = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $aggKebutuhanProyeksi = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($sotkEntries as $sotk) {
            if (!$sotk->jabatan) continue;
            $jabatan = $sotk->jabatan;
            $jabId = $jabatan->id;

            $kebutuhan = $kebutuhanMap[$unor->id][$jabId] ?? 0;
            $bezetting = $bezettingMap[$unor->id][$jabId] ?? 0;
            $selisih = $bezetting - $kebutuhan;

            // Proyeksi dari struktur baru: [unor_id][jabatan_id]['counts'|'pegawai']
            $proyeksiData = $proyeksiPensiunPerJabatan[$unor->id][$jabId] ?? null;
            $jabatanPensiunCounts = $proyeksiData['counts'] ?? [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
            $jabatanPensiunPegawai = $proyeksiData['pegawai'] ?? [];

            // Akumulasi untuk UNOR row
            $aggKebutuhan += $kebutuhan;
            $aggBezetting += $bezetting;
            for ($n = 1; $n <= 5; $n++) {
                $aggPensiunProyeksi[$n] += $jabatanPensiunCounts[$n];
            }

            $row = [
                'id'                  => $jabId,
                'parent_id'           => $unorIdStr,
                'type'                => 'jabatan',
                'level'               => $jabatanLevel,
                'nama_jabatan'        => $jabatan->nama_jabatan,
                'jenis_jabatan'       => $jabatan->jenis_jabatan,
                'jenjang'             => $jabatan->jenjang,
                'kelas_jabatan'       => $jabatan->kelas_jabatan,
                'kebutuhan'           => $kebutuhan,
                'bezetting'           => $bezetting,
                'selisih'             => $selisih,
                'pegawai'             => $pegawaiMap[$unor->id][$jabId] ?? [],
                'has_children'        => false,
                'unor_id'             => $unor->id,
                'kode_unor'           => $unor->kode_unor,
                'kebutuhan_proyeksi'  => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                'pensiun_proyeksi'    => $jabatanPensiunCounts,
                'pegawai_pensiun'     => $jabatanPensiunPegawai,
            ];

            if ($withProjections) {
                $row['kebutuhan_proyeksi'] = [
                    1 => $jabatanPensiunCounts[1] ?? 0,
                    2 => $jabatanPensiunCounts[2] ?? 0,
                    3 => $jabatanPensiunCounts[3] ?? 0,
                    4 => $jabatanPensiunCounts[4] ?? 0,
                    5 => $jabatanPensiunCounts[5] ?? 0,
                ];
            }

            $jabatanRows[] = $row;
        }

        // kebutuhan_proyeksi pada UNOR = pensiun_proyeksi (rasio 1:1)
        $aggKebutuhanProyeksi = $aggPensiunProyeksi;

        // ── UNOR row ──
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
            'kebutuhan_proyeksi'  => $aggKebutuhanProyeksi,
            'pensiun_proyeksi'    => $aggPensiunProyeksi,
            'pegawai_pensiun'     => [],
        ];

        // ── Append jabatan rows ──
        foreach ($jabatanRows as $row) {
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
     * @return array<int, array<int, array<int, array{ nip: string, nama: string, tugas_tambahan: string[] }>>>
     */
    private function buildPegawaiMap(?array $unorIds = null): array
    {
        $query = PenempatanPegawai::with('pegawai.tugasTambahan.tugasTambahan')
            ->where('is_active', true);
        if ($unorIds !== null) {
            $query->whereIn('unor_id', $unorIds);
        }
        $map = [];
        $today = now()->toDateString();
        foreach ($query->get() as $p) {
            if (!$p->pegawai) continue;

            // Kumpulkan tugas tambahan aktif (yang belum expired)
            $tugasAktif = [];
            if ($p->pegawai->tugasTambahan) {
                foreach ($p->pegawai->tugasTambahan as $tt) {
                    if (!$tt->is_active) continue;
                    if ($tt->tanggal_selesai !== null && $tt->tanggal_selesai->format('Y-m-d') < $today) continue;
                    if ($tt->tugasTambahan) {
                        $tugasAktif[] = $tt->tugasTambahan->nama_tugas;
                    }
                }
            }

            $map[$p->unor_id][$p->jabatan_id][] = [
                'nip'            => $p->pegawai->nip,
                'nama'           => $p->pegawai->nama,
                'tugas_tambahan' => $tugasAktif,
            ];
        }
        return $map;
    }

    /**
     * Propagate child UNOR totals (kebutuhan, bezetting, pegawai, proyeksi) upward to parents.
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

            // Propagasi proyeksi anak UNOR ke parent UNOR
            for ($n = 1; $n <= 5; $n++) {
                $result[$pIdx]['pensiun_proyeksi'][$n] += $row['pensiun_proyeksi'][$n];
                $result[$pIdx]['kebutuhan_proyeksi'][$n] += $row['kebutuhan_proyeksi'][$n];
            }
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
