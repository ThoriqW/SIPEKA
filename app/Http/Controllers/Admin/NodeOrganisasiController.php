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
     * Tampilkan daftar node organisasi dalam bentuk pohon.
     */
    public function index(Request $request)
    {
        $query = NodeOrganisasi::query()->with(['parent', 'pegawai']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $allNodes = $query->orderBy('parent_id')
            ->orderBy('jenis')
            ->orderBy('sort_order')
            ->orderBy('nama')
            ->get();

        // Build tree untuk tampilan
        $childrenMap = [];
        foreach ($allNodes as $node) {
            $childrenMap[$node->parent_id ?? 0][] = $node;
        }

        // Roots: anak langsung dari "Pemerintah Kota Palu"
        $rootNode = NodeOrganisasi::whereNull('parent_id')->first();
        $roots = $childrenMap[$rootNode?->id ?? 0] ?? [];

        return view('admin.node-organisasi.index', compact('allNodes', 'childrenMap', 'roots', 'rootNode'));
    }

    /**
     * Tampilkan form create.
     */
    public function create()
    {
        // Parent options: semua node (UNIT dan POSISI) kecuali yang tidak relevan
        $parentOptions = NodeOrganisasi::orderBy('jenis')
            ->orderBy('nama')
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'nama' => $n->nama . ' (' . $n->jenis . ')',
            ]);

        return view('admin.node-organisasi.create', [
            'parentOptions' => $parentOptions,
            'jenisOptions' => ['UNIT' => 'Unit Organisasi', 'POSISI' => 'Posisi Organisasi'],
        ]);
    }

    /**
     * Simpan node baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'jenis' => 'required|in:UNIT,POSISI',
            'parent_id' => 'nullable|exists:node_organisasi,id',
            'kelas_jabatan' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Validasi: cegah circular reference
        if (!empty($validated['parent_id'])) {
            $parent = NodeOrganisasi::find($validated['parent_id']);
            if ($parent && $parent->parent_id !== null) {
                // Parent valid (bukan root)
            }
        }

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

        return redirect()
            ->route('admin.node-organisasi.index')
            ->with('success', 'Node organisasi berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit.
     */
    public function edit(NodeOrganisasi $nodeOrganisasi)
    {
        // Exclude diri sendiri dan semua turunan dari daftar parent
        $excludeIds = $nodeOrganisasi->getDescendantIds();
        $excludeIds[] = $nodeOrganisasi->id;

        $parentOptions = NodeOrganisasi::whereNotIn('id', $excludeIds)
            ->orderBy('jenis')
            ->orderBy('nama')
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'nama' => $n->nama . ' (' . $n->jenis . ')',
            ]);

        return view('admin.node-organisasi.edit', [
            'node' => $nodeOrganisasi,
            'parentOptions' => $parentOptions,
            'jenisOptions' => ['UNIT' => 'Unit Organisasi', 'POSISI' => 'Posisi Organisasi'],
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
            'parent_id' => 'nullable|exists:node_organisasi,id',
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

        return redirect()
            ->route('admin.node-organisasi.index')
            ->with('success', 'Node organisasi berhasil diperbarui.');
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

        return redirect()
            ->route('admin.node-organisasi.index')
            ->with('success', 'Node organisasi berhasil dihapus.');
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
