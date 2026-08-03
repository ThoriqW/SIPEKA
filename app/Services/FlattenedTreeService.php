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
            // Load the specific UNOR and all its descendants
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
            // When filtering by unorId, treat the filtered UNOR as root
            // (ignore its actual parent since parent isn't in the result set)
            if ($unorId !== null && $unor->id === $unorId) {
                $parentKey = 0;
            }
            $unorChildrenMap[$parentKey][] = $unor;
            if ($parentKey === 0) {
                $rootUnors[] = $unor;
            }
        }

        // ── Pre-load kebutuhan & penempatan per (unor_id, jabatan_id) ──
        $kebutuhanMap = $this->buildKebutuhanMap($unorId);
        $bezettingMap = $this->buildBezettingMap($unorId);

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
                proyeksiPensiunPerJabatan: $proyeksiPensiunPerJabatan,
                withProjections: $withProjections,
            );
        }

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
        array $proyeksiPensiunPerJabatan,
        bool $withProjections,
    ): void {
        $unorIdStr = 'u-' . $unor->id;
        $childUnors = $unorChildrenMap[$unor->id] ?? [];
        $sotkEntries = $unor->sotkEntries ?? collect();

        $hasChildren = !empty($childUnors) || $sotkEntries->isNotEmpty();

        // ── Aggregate totals for UNOR row ──
        $aggKebutuhan = 0;
        $aggBezetting = 0;
        $allPegawaiNip = []; // dedup pegawai for UNOR aggregate

        foreach ($sotkEntries as $sotk) {
            $jabId = $sotk->jabatan_id;
            $aggKebutuhan += $kebutuhanMap[$unor->id][$jabId] ?? 0;
            $aggBezetting += $bezettingMap[$unor->id][$jabId] ?? 0;
            // Pegawai dari jabatan (via penempatan atau relasi langsung)
            if ($sotk->jabatan && $sotk->jabatan->relationLoaded('pegawai')) {
                foreach ($sotk->jabatan->pegawai as $p) {
                    $allPegawaiNip[$p->nip] = ['nip' => $p->nip, 'nama' => $p->nama];
                }
            }
        }

        // Juga aggregate dari child UNORs (rekursi dulu baru hitung totals? tidak — UNOR row shown first)
        // Untuk UNOR row, tampilkan aggregate langsung (children + jabatan langsung)
        // Children UNOR totals dihitung saat flattenNode rekursif

        // ── UNOR row ──
        $result[] = [
            'id'              => $unorIdStr,
            'parent_id'       => $parentId,
            'type'            => 'unor',
            'level'           => $level,
            'nama_jabatan'    => $unor->nama_unor,
            'jenis_jabatan'   => null,
            'jenjang'         => null,
            'kelas_jabatan'   => null,
            'kebutuhan'       => $aggKebutuhan,
            'bezetting'       => $aggBezetting,
            'selisih'         => $aggBezetting - $aggKebutuhan,
            'pegawai'         => array_values($allPegawaiNip),
            'has_children'    => $hasChildren,
            'unor_id'         => $unor->id,
            'kode_unor'       => $unor->kode_unor,
            'kebutuhan_proyeksi' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'pensiun_proyeksi'   => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'pegawai_pensiun'    => [],
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

            // Pegawai yang pensiun dalam 5 tahun ke depan
            $t = (int) date('Y');
            $pegawaiPensiun = [];
            if ($withProjections && $jabatan->relationLoaded('pegawai')) {
                foreach ($jabatan->pegawai as $p) {
                    $tglPensiun = $this->bupCalculator->hitungTanggalPensiun(
                        $p->tanggal_lahir,
                        $p->jabatan?->jenjang ?? '',
                        $p->jenis_kepegawaian,
                        $jabatan->nama_jabatan
                    );
                    $tahunPensiun = (int) $tglPensiun->format('Y');
                    if ($tahunPensiun >= $t && $tahunPensiun <= $t + 4) {
                        $pegawaiPensiun[] = [
                            'nip' => $p->nip,
                            'nama' => $p->nama,
                            'tahun_pensiun' => $tahunPensiun,
                        ];
                    }
                }
            }

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
                'pegawai'         => $jabatan->relationLoaded('pegawai')
                    ? $jabatan->pegawai->map(fn($p) => ['nip' => $p->nip, 'nama' => $p->nama])->toArray()
                    : [],
                'has_children'    => false,
                'unor_id'         => $unor->id,
                'kode_unor'       => $unor->kode_unor,
                'kebutuhan_proyeksi' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                'pensiun_proyeksi'   => $jabatanPensiun,
                'pegawai_pensiun'    => $pegawaiPensiun,
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
                proyeksiPensiunPerJabatan: $proyeksiPensiunPerJabatan,
                withProjections: $withProjections,
            );
        }
    }

    /**
     * Build map of kebutuhan per (unor_id, jabatan_id) from kebutuhan_pegawai table.
     */
    private function buildKebutuhanMap(?int $unorId = null): array
    {
        $query = KebutuhanPegawai::whereNull('tahun');
        if ($unorId !== null) {
            $query->where('unor_id', $unorId);
        }
        $map = [];
        foreach ($query->get() as $k) {
            $map[$k->unor_id][$k->jabatan_id] = $k->jumlah;
        }
        return $map;
    }

    /**
     * Build map of bezetting per (unor_id, jabatan_id) from penempatan_pegawai table.
     * Falls back to counting pegawai directly on jabatan if penempatan is empty.
     */
    private function buildBezettingMap(?int $unorId = null): array
    {
        $query = PenempatanPegawai::where('is_active', true);
        if ($unorId !== null) {
            $query->where('unor_id', $unorId);
        }
        $map = [];
        foreach ($query->get() as $p) {
            $map[$p->unor_id][$p->jabatan_id] = ($map[$p->unor_id][$p->jabatan_id] ?? 0) + 1;
        }
        return $map;
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
