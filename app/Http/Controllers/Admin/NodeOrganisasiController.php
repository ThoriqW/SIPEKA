<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NodeOrganisasi;
use App\Services\KodeNodeGenerator;
use Illuminate\Http\Request;

class NodeOrganisasiController extends Controller
{
    public function __construct(
        private KodeNodeGenerator $kodeGenerator,
    ) {}

    /**
     * Halaman UNOR (Unit Organisasi) — struktur organisasi (hanya node UNIT).
     */
    public function unorIndex(Request $request)
    {
        $query = NodeOrganisasi::unit()->with(['parent', 'children', 'pegawai']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
        }

        $allNodes = $query->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('nama')
            ->get();

        // Build tree
        $childrenMap = [];
        foreach ($allNodes as $node) {
            $childrenMap[$node->parent_id ?? 0][] = $node;
        }

        $rootNode = $allNodes->firstWhere('parent_id', null);
        $roots = $childrenMap[$rootNode?->id ?? 0] ?? [];

        return view('admin.node-organisasi.unit', compact('allNodes', 'childrenMap', 'roots', 'rootNode'));
    }

    /**
     * Halaman KEBUTUHAN JABATAN — POSISI dikelompokkan per UNIT induk.
     * Menampilkan jumlah posisi, kelas, dan status terisi/kosong.
     */
    public function kebutuhanIndex(Request $request)
    {
        // Ambil semua UNIT untuk filter
        $unitList = NodeOrganisasi::unit()->orderBy('nama')->get();

        // Ambil semua POSISI dengan parent UNIT
        $posisiQuery = NodeOrganisasi::posisi()
            ->with(['parent', 'pegawai', 'pegawai.jabatanAsn']);

        if ($request->filled('search')) {
            $search = $request->search;
            $posisiQuery->where('nama', 'like', "%{$search}%");
        }

        if ($request->filled('unit_id')) {
            // Filter POSISI yang berada di bawah unit tertentu (subtree)
            $unit = NodeOrganisasi::find($request->unit_id);
            if ($unit) {
                $descendantIds = $unit->getDescendantIds();
                $descendantIds[] = $unit->id;
                $posisiQuery->whereIn('parent_id', $descendantIds);
            }
        }

        $posisiList = $posisiQuery->orderBy('parent_id')
            ->orderBy('nama')
            ->get()
            ->groupBy('parent_id');

        // Hitung kebutuhan: jumlah POSISI per parent unit
        $kebutuhanPerUnit = [];
        foreach ($posisiList as $parentId => $items) {
            $kebutuhanPerUnit[$parentId] = $items->count();
        }

        return view('admin.node-organisasi.kebutuhan', compact(
            'posisiList', 'unitList', 'kebutuhanPerUnit'
        ));
    }

    /**
     * Tampilkan form create.
     */
    public function create()
    {
        // OPD list (Unor level-1) untuk dropdown pertama
        $rootNode = NodeOrganisasi::whereNull('parent_id')->first();
        $opdList = collect();
        if ($rootNode) {
            $opdList = NodeOrganisasi::unit()
                ->where('parent_id', $rootNode->id)
                ->orderBy('nama')
                ->get()
                ->map(fn($n) => [
                    'id' => $n->id,
                    'nama' => $n->nama,
                ]);
        }

        // Tentukan route untuk store berdasarkan halaman asal
        $storeRoute = request()->routeIs('admin.unor.*') ? 'admin.unor.store' : 'admin.kebutuhan-jabatan.store';

        return view('admin.node-organisasi.create', [
            'opdList' => $opdList,
            'isUnor' => request()->routeIs('admin.unor.*'),
            'storeRoute' => $storeRoute,
        ]);
    }

    /**
     * Simpan node baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('node_organisasi', 'nama')
                    ->where('parent_id', $request->parent_id),
            ],
            'jenis' => 'required|in:UNIT,POSISI',
            'parent_id' => [
                'required', 'exists:node_organisasi,id',
                function (string $attr, mixed $value, \Closure $fail) {
                    $parent = NodeOrganisasi::find($value);
                    if ($parent && !$parent->isUnit() && $parent->parent_id !== null) {
                        $fail('Induk harus berupa Unit Organisasi (Unor).');
                    }
                },
            ],
            'kelas_jabatan' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Generate kode otomatis
        if ($validated['jenis'] === 'POSISI') {
            $parentUnit = !empty($validated['parent_id'])
                ? NodeOrganisasi::find($validated['parent_id'])
                : NodeOrganisasi::whereNull('parent_id')->first();

            $validated['kode'] = $this->kodeGenerator->generateKodePosisi(
                $parentUnit,
                $validated['nama']
            );
        } elseif ($validated['jenis'] === 'UNIT') {
            // UNIT: generate kode singkatan
            $validated['kode'] = $this->kodeGenerator->generateKodeUnit($validated['nama']);
        }

        // UNIT tidak perlu kelas_jabatan
        if ($validated['jenis'] === 'UNIT') {
            $validated['kelas_jabatan'] = null;
        }

        NodeOrganisasi::create($validated);

        $redirectRoute = request()->routeIs('admin.unor.*') ? 'admin.unor.index' : 'admin.kebutuhan-jabatan.index';
        return redirect()->route($redirectRoute)
            ->with('success', 'Unit organisasi berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit.
     */
    public function edit(NodeOrganisasi $nodeOrganisasi)
    {
        // Exclude diri sendiri dan semua turunan dari daftar parent
        $excludeIds = $nodeOrganisasi->getDescendantIds();
        $excludeIds[] = $nodeOrganisasi->id;

        $parentOptions = NodeOrganisasi::unit()
            ->whereNotIn('id', $excludeIds)
            ->orderBy('nama')
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'nama' => $n->nama,
            ]);

        $updateRoute = request()->routeIs('admin.unor.*') ? 'admin.unor.update' : 'admin.kebutuhan-jabatan.update';
        $destroyRoute = request()->routeIs('admin.unor.*') ? 'admin.unor.destroy' : 'admin.kebutuhan-jabatan.destroy';

        return view('admin.node-organisasi.edit', [
            'node' => $nodeOrganisasi,
            'parentOptions' => $parentOptions,
            'jenisOptions' => ['UNIT' => 'Unit Organisasi', 'POSISI' => 'Posisi Organisasi'],
            'updateRoute' => $updateRoute,
            'destroyRoute' => $destroyRoute,
        ]);
    }

    /**
     * Update node.
     */
    public function update(Request $request, NodeOrganisasi $nodeOrganisasi)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'jenis' => 'required|in:UNIT,POSISI',
            'parent_id' => 'required|exists:node_organisasi,id',
            'kelas_jabatan' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Validasi: POSISI tidak boleh diubah ke UNIT jika sudah diisi pegawai
        if ($validated['jenis'] === 'UNIT' && $nodeOrganisasi->isPosisi() && $nodeOrganisasi->isTerisi()) {
            return back()->withInput()->with('error',
                'Posisi ini sedang diisi pegawai. Lepaskan pegawai terlebih dahulu sebelum mengubah menjadi Unit.');
        }

        // Validasi: UNIT yang punya anak tidak boleh diubah ke POSISI
        if ($validated['jenis'] === 'POSISI' && $nodeOrganisasi->isUnit() && $nodeOrganisasi->children()->exists()) {
            return back()->withInput()->with('error',
                'Unit ini memiliki sub-node. Pindahkan atau hapus sub-node terlebih dahulu sebelum mengubah menjadi Posisi.');
        }

        // Validasi: circular reference
        if (!empty($validated['parent_id'])) {
            if ((int) $validated['parent_id'] === $nodeOrganisasi->id) {
                return back()->withInput()->with('error', 'Node tidak bisa menjadi parent dari dirinya sendiri.');
            }
            if (in_array((int) $validated['parent_id'], $nodeOrganisasi->getDescendantIds())) {
                return back()->withInput()->with('error', 'Parent tidak valid karena merupakan turunan dari node ini.');
            }
        }

        // UNIT tidak perlu kelas_jabatan
        if ($validated['jenis'] === 'UNIT') {
            $validated['kelas_jabatan'] = null;
        }

        // Kode tidak dapat diubah
        unset($validated['kode']);

        $nodeOrganisasi->update($validated);

        $redirectRoute = request()->routeIs('admin.unor.*') ? 'admin.unor.index' : 'admin.kebutuhan-jabatan.index';
        return redirect()->route($redirectRoute)
            ->with('success', 'Unit organisasi berhasil diperbarui.');
    }

    /**
     * Hapus node.
     */
    public function destroy(NodeOrganisasi $nodeOrganisasi)
    {
        if ($nodeOrganisasi->children()->exists()) {
            return back()->with('error', 'Node tidak dapat dihapus karena masih memiliki sub-node.');
        }
        if ($nodeOrganisasi->isTerisi()) {
            return back()->with('error', 'Posisi tidak dapat dihapus karena sedang diisi pegawai.');
        }

        $nodeOrganisasi->delete();

        $redirectRoute = request()->routeIs('admin.unor.*') ? 'admin.unor.index' : 'admin.kebutuhan-jabatan.index';
        return redirect()->route($redirectRoute)
            ->with('success', 'Unit organisasi berhasil dihapus.');
    }

    /**
     * AJAX: Dapatkan daftar POSISI berdasarkan parent UNIT (untuk form pegawai).
     */
    public function getPosisiByParent(Request $request)
    {
        $request->validate(['parent_id' => 'required|exists:node_organisasi,id']);

        $parentId = (int) $request->parent_id;

        // Ambil semua node POSISI dalam subtree parent
        $ids = $this->getSubtreeIds($parentId);
        $ids[] = $parentId;

        $posisiList = NodeOrganisasi::with('pegawai')
            ->whereIn('id', $ids)
            ->where('jenis', 'POSISI')
            ->orderBy('nama')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'kode' => $p->kode,
                'unit_path' => $this->resolveUnitPath($p),
                'terisi' => $p->isTerisi(),
            ]);

        return response()->json(['success' => true, 'data' => $posisiList]);
    }

    /**
     * AJAX: Get semua Unor (UNIT) dalam subtree OPD — untuk cascading dropdown.
     */
    public function getUnorByOpd(Request $request)
    {
        $request->validate([
            'opd_id' => [
                'required', 'exists:node_organisasi,id',
                function (string $attr, mixed $value, \Closure $fail) {
                    $node = NodeOrganisasi::find($value);
                    if (!$node || !$node->isUnit()) {
                        $fail('Node yang dipilih bukan Unit Organisasi.');
                    }
                },
            ],
        ]);

        $opd = NodeOrganisasi::find($request->opd_id);
        $ids = $opd->getDescendantIds();
        $ids[] = (int) $request->opd_id;

        $unorList = NodeOrganisasi::unit()
            ->whereIn('id', $ids)
            ->orderBy('nama')
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'nama' => $n->nama,
                'kode' => $n->kode,
            ]);

        return response()->json(['success' => true, 'data' => $unorList]);
    }

    /**
     * AJAX: Get node children (untuk tree expand di UI).
     */
    public function children(NodeOrganisasi $nodeOrganisasi)
    {
        $children = $nodeOrganisasi->children()
            ->with('pegawai')
            ->withCount('children')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nama' => $c->nama,
                'jenis' => $c->jenis,
                'kode' => $c->kode,
                'kelas_jabatan' => $c->kelas_jabatan,
                'pegawai_count' => $c->pegawai->count(),
                'has_children' => $c->children_count > 0,
            ]);

        return response()->json(['success' => true, 'data' => $children]);
    }

    /*
     * -------------------------------------------------------------------------
     * Helper
     * -------------------------------------------------------------------------
     */

    private function getSubtreeIds(int $nodeId): array
    {
        $ids = [];
        $queue = NodeOrganisasi::where('parent_id', $nodeId)->pluck('id')->toArray();

        while (!empty($queue)) {
            $ids = array_merge($ids, $queue);
            $queue = NodeOrganisasi::whereIn('parent_id', $queue)->pluck('id')->toArray();
        }

        return $ids;
    }

    private function resolveUnitPath(NodeOrganisasi $node): string
    {
        $parts = [];
        $current = $node;

        while ($current) {
            if ($current->isUnit()) {
                $parts[] = $current->nama;
            }
            $current = $current->parent;
        }

        return implode(' → ', array_reverse($parts));
    }
}
