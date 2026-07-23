<?php

namespace App\Services;

use App\Models\NodeOrganisasi;
use Illuminate\Support\Collection;

/**
 * Membangun flat tree dari tabel node_organisasi untuk ditampilkan
 * di layar Kebutuhan dan Bezetting.
 *
 * Menggantikan FlattenedTreeService yang menggunakan model jabatan lama.
 *
 * Konsep kunci:
 *  - UNIT  = wadah organisasi (container), tidak bisa diisi pegawai
 *  - POSISI = posisi yang dapat diisi tepat 1 pegawai
 *  - Kebutuhan POSISI selalu 1 (satu posisi = satu kebutuhan)
 *  - Bezetting POSISI = 1 jika ada pegawai, 0 jika kosong
 *  - Selisih POSISI = kebutuhan - bezetting = 1 - (0|1)
 */
class NodeTreeBuilder
{
    public function __construct(
        private ProjectionService $projectionService,
        private BupCalculator $bupCalculator,
    ) {}

    /**
     * Build flat depth-first tree array dari hierarki node_organisasi.
     *
     * @param int|null $unitId        Filter by unit ancestor (null = all)
     * @param bool     $includeRoot   Prepend virtual level-0 root row "Pemerintah Kota Palu"
     * @param bool     $withProjections Compute pensiun & kebutuhan proyeksi Thn 1-5 per row
     * @param bool     $onlyPosisi    Hanya tampilkan node POSISI (untuk layar Bezetting)
     * @return array   Flat ordered rows
     */
    public function buildFlatTree(
        ?int $unitId = null,
        bool $includeRoot = false,
        bool $withProjections = false,
        bool $onlyPosisi = false,
    ): array {
        $query = NodeOrganisasi::with(['pegawai.jabatanAsn', 'children']);

        $allNodes = $query->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('nama')
            ->get();

        // Build parent → children map
        $childrenMap = [];
        foreach ($allNodes as $node) {
            $parentKey = $node->parent_id ?? 0;
            $childrenMap[$parentKey][] = $node;
        }

        // Jika filter unitId diberikan, kita perlu membatasi tree ke subtree unit tersebut
        if ($unitId !== null) {
            $allNodes = $this->filterSubtree($allNodes, $unitId, $childrenMap);
            // Rebuild children map setelah filter
            $childrenMap = [];
            foreach ($allNodes as $node) {
                $parentKey = $node->parent_id ?? 0;
                $childrenMap[$parentKey][] = $node;
            }
        }

        // Pre-compute proyeksi pensiun per posisi
        $proyeksiPensiunPerPosisi = $withProjections
            ? $this->projectionService->hitungProyeksiPensiunPerPosisi($unitId)
            : [];

        $result = [];

        // Optional virtual root
        if ($includeRoot) {
            $result[] = $this->makeRootRow($allNodes, $proyeksiPensiunPerPosisi, $withProjections, $onlyPosisi);
        }

        // Depth-first traversal dari root-level nodes
        $roots = $childrenMap[0] ?? [];
        foreach ($roots as $root) {
            $this->flattenNode(
                node: $root,
                parentId: $includeRoot ? 0 : null,
                level: 1,
                result: $result,
                childrenMap: $childrenMap,
                proyeksiPensiunPerPosisi: $proyeksiPensiunPerPosisi,
                withProjections: $withProjections,
                onlyPosisi: $onlyPosisi,
            );
        }

        return $result;
    }

    /**
     * Rekursif — flatten satu node dan turunannya ke result array.
     */
    private function flattenNode(
        NodeOrganisasi $node,
        ?int $parentId,
        int $level,
        array &$result,
        array $childrenMap,
        array $proyeksiPensiunPerPosisi,
        bool $withProjections,
        bool $onlyPosisi,
    ): void {
        $hasChildren = !empty($childrenMap[$node->id]);

        // Jika onlyPosisi=true, UNIT node tetap di-skip tapi kita tetap
        // traverse children-nya (UNIT hanya container)
        if ($onlyPosisi && $node->isUnit()) {
            if ($hasChildren) {
                foreach ($childrenMap[$node->id] as $child) {
                    $this->flattenNode(
                        node: $child,
                        parentId: $parentId,  // pertahankan parentId UNIT
                        level: $level + 1,
                        result: $result,
                        childrenMap: $childrenMap,
                        proyeksiPensiunPerPosisi: $proyeksiPensiunPerPosisi,
                        withProjections: $withProjections,
                        onlyPosisi: $onlyPosisi,
                    );
                }
            }
            return;
        }

        $isPosisi = $node->isPosisi();
        $bezetting = $node->pegawai->count();

        // POSISI: kebutuhan = 1, UNIT: kebutuhan = null (agregat dari anak)
        $kebutuhan = $isPosisi ? 1 : null;
        $selisih = $isPosisi ? ($bezetting - 1) : null;

        // Proyeksi pensiun dari pegawai pada posisi ini
        $pensiunProyeksi = $proyeksiPensiunPerPosisi[$node->id] ?? [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        // Hitung pegawai yang pensiun dalam 5 tahun
        $t = (int) date('Y');
        $pegawaiPensiun = [];
        if ($withProjections && $isPosisi) {
            foreach ($node->pegawai as $p) {
                $tglPensiun = $this->bupCalculator->hitungTanggalPensiun(
                    $p->tanggal_lahir,
                    $p->jenjang,
                    $p->jenis_kepegawaian,
                    $p->jabatanAsn->nama_jabatan_asn ?? null
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
            'id'              => $node->id,
            'parent_id'       => $parentId,
            'level'           => $level,
            'nama'            => $node->nama,
            'nama_jabatan'    => $node->nama,             // backward compat
            'jenis'           => $node->jenis,
            'jenis_jabatan'   => null,                     // backward compat (tidak relevan di model baru)
            'jenjang'         => null,                     // backward compat (dari jabatan_asn)
            'kelas_jabatan'   => $node->kelas_jabatan,
            'kebutuhan'       => $kebutuhan,
            'bezetting'       => $bezetting,
            'selisih'         => $selisih,
            'pegawai'         => $node->pegawai->map(fn($p) => [
                'nip'  => $p->nip,
                'nama' => $p->nama,
                'jabatan_asn' => $p->jabatanAsn->nama_jabatan_asn ?? null,
            ])->toArray(),
            'has_children'    => $hasChildren,
            'is_unit'         => $node->isUnit(),
            'is_posisi'       => $isPosisi,
            'opd_id'          => null,                     // backward compat
            'kebutuhan_proyeksi' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'pensiun_proyeksi'   => $pensiunProyeksi,
            'pegawai_pensiun'    => $pegawaiPensiun,
        ];

        // Kebutuhan proyeksi: hanya untuk POSISI, berdasarkan pegawai pensiun
        if ($withProjections && $isPosisi) {
            $row['kebutuhan_proyeksi'] = [
                1 => $pensiunProyeksi[1] ?? 0,
                2 => $pensiunProyeksi[2] ?? 0,
                3 => $pensiunProyeksi[3] ?? 0,
                4 => $pensiunProyeksi[4] ?? 0,
                5 => $pensiunProyeksi[5] ?? 0,
            ];
        }

        // Agregasi untuk UNIT node (opsional)
        if (!$isPosisi && $withProjections) {
            $row['kebutuhan'] = $this->aggregateKebutuhan($node, $childrenMap);
            $row['bezetting'] = $this->aggregateBezetting($node, $childrenMap);
            $row['selisih'] = $row['bezetting'] - ($row['kebutuhan'] ?? 0);
            $row['pensiun_proyeksi'] = $this->aggregateProyeksi($node, $childrenMap, $proyeksiPensiunPerPosisi);
            $row['kebutuhan_proyeksi'] = $row['pensiun_proyeksi']; // asumsi 1:1 replacement
        }

        $result[] = $row;

        // Recurse ke children
        if ($hasChildren) {
            foreach ($childrenMap[$node->id] as $child) {
                $this->flattenNode(
                    node: $child,
                    parentId: $node->id,
                    level: $level + 1,
                    result: $result,
                    childrenMap: $childrenMap,
                    proyeksiPensiunPerPosisi: $proyeksiPensiunPerPosisi,
                    withProjections: $withProjections,
                    onlyPosisi: $onlyPosisi,
                );
            }
        }
    }

    /**
     * Virtual root row (Level 0: "Pemerintah Kota Palu").
     */
    private function makeRootRow(
        Collection $allNodes,
        array $proyeksiPensiunPerPosisi,
        bool $withProjections,
        bool $onlyPosisi,
    ): array {
        $totalPensiun = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        if ($withProjections) {
            foreach ($proyeksiPensiunPerPosisi as $years) {
                for ($n = 1; $n <= 5; $n++) {
                    $totalPensiun[$n] += $years[$n] ?? 0;
                }
            }
        }

        $totalKebutuhan = $allNodes->where('jenis', 'POSISI')->count();
        $totalBezetting = $allNodes->flatMap->pegawai->unique('id')->count();

        return [
            'id'                  => 0,
            'parent_id'           => null,
            'level'               => 0,
            'nama'                => 'Pemerintah Kota Palu',
            'nama_jabatan'        => 'Pemerintah Kota Palu',     // backward compat
            'jenis'               => 'UNIT',
            'jenis_jabatan'       => null,
            'jenjang'             => null,
            'kelas_jabatan'       => null,
            'kebutuhan'           => $totalKebutuhan,
            'bezetting'           => $totalBezetting,
            'selisih'             => $totalBezetting - $totalKebutuhan,
            'pegawai'             => [],
            'pegawai_pensiun'     => [],
            'has_children'        => true,
            'is_unit'             => true,
            'is_posisi'           => false,
            'kebutuhan_proyeksi'  => $withProjections ? array_map(fn($v) => $v, $totalPensiun) : [1=>0,2=>0,3=>0,4=>0,5=>0],
            'pensiun_proyeksi'    => $totalPensiun,
        ];
    }

    /**
     * Filter collection ke subtree dari unit tertentu.
     */
    private function filterSubtree(Collection $allNodes, int $unitId, array $childrenMap): Collection
    {
        $ids = [$unitId];
        $queue = [$unitId];

        while (!empty($queue)) {
            $current = array_shift($queue);
            if (!empty($childrenMap[$current])) {
                foreach ($childrenMap[$current] as $child) {
                    $ids[] = $child->id;
                    $queue[] = $child->id;
                }
            }
        }

        return $allNodes->whereIn('id', $ids);
    }

    /*
     * -------------------------------------------------------------------------
     * Agregasi untuk UNIT node
     * -------------------------------------------------------------------------
     */

    private function aggregateKebutuhan(NodeOrganisasi $unit, array $childrenMap): int
    {
        $total = 0;
        if (!empty($childrenMap[$unit->id])) {
            foreach ($childrenMap[$unit->id] as $child) {
                if ($child->isPosisi()) {
                    $total += 1;
                } else {
                    $total += $this->aggregateKebutuhan($child, $childrenMap);
                }
            }
        }
        return $total;
    }

    private function aggregateBezetting(NodeOrganisasi $unit, array $childrenMap): int
    {
        $total = 0;
        if (!empty($childrenMap[$unit->id])) {
            foreach ($childrenMap[$unit->id] as $child) {
                $total += $child->pegawai->count();
                if ($child->isUnit()) {
                    $total += $this->aggregateBezetting($child, $childrenMap) - $child->pegawai->count();
                }
            }
        }
        return $total;
    }

    private function aggregateProyeksi(NodeOrganisasi $unit, array $childrenMap, array $proyeksi): array
    {
        $total = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        if (!empty($childrenMap[$unit->id])) {
            foreach ($childrenMap[$unit->id] as $child) {
                $childProyeksi = $proyeksi[$child->id] ?? [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
                for ($n = 1; $n <= 5; $n++) {
                    $total[$n] += $childProyeksi[$n] ?? 0;
                }
                if ($child->isUnit()) {
                    $grandChild = $this->aggregateProyeksi($child, $childrenMap, $proyeksi);
                    for ($n = 1; $n <= 5; $n++) {
                        $total[$n] += $grandChild[$n];
                    }
                }
            }
        }
        return $total;
    }
}
