<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unor;
use Illuminate\Http\Request;

class UnorController extends Controller
{
    public function index()
    {
        $allUnor = Unor::with('children')->get()->keyBy('id');

        // Build tree: depth-first flat array — iterasi SEMUA root
        $tree = [];
        $rootUnors = $allUnor->filter(fn($u) => $u->parent_id === null);

        foreach ($rootUnors as $root) {
            $rootChildren = $allUnor->filter(fn($u) => $u->parent_id === $root->id)
                ->sortBy('nama_unor');

            $rootIdStr = 'u-' . $root->id;
            $tree[] = [
                'id' => $rootIdStr,
                'parent_id' => '',
                'level' => 0,
                'nama' => $root->nama_unor,
                'kode' => $root->kode_unor,
                'has_children' => $rootChildren->isNotEmpty(),
                'unor_id' => $root->id,
            ];

            foreach ($rootChildren as $child) {
                $this->flattenUnor($child, $root->id, 1, $tree, $allUnor);
            }
        }

        // ID semua root (format string 'u-{id}') untuk inisialisasi expandedItems di view
        $rootIds = $rootUnors->pluck('id')->map(fn($id) => 'u-' . $id)->values()->toArray();

        return view('admin.unor.index', compact('tree', 'rootIds'));
    }

    private function flattenUnor(Unor $unor, ?int $parentId, int $level, array &$result, $allUnor): void
    {
        $unorIdStr = 'u-' . $unor->id;

        // Get children of this UNOR, sorted by kode_unor
        $children = $allUnor->filter(fn($u) => $u->parent_id === $unor->id)
            ->sortBy('kode_unor');

        $result[] = [
            'id' => $unorIdStr,
            'parent_id' => $parentId ? 'u-' . $parentId : '',
            'level' => $level,
            'nama' => $unor->nama_unor,
            'kode' => $unor->kode_unor,
            'has_children' => $children->isNotEmpty(),
            'unor_id' => $unor->id,
        ];

        foreach ($children as $child) {
            $this->flattenUnor($child, $unor->id, $level + 1, $result, $allUnor);
        }
    }

    public function create()
    {
        $allUnor = Unor::with('parent')->get()->keyBy('id');
        $rootUnor = Unor::whereNull('parent_id')->first();
        $parentList = $allUnor
            ->mapWithKeys(fn($u) => [$u->id => $u->pathLabel($allUnor)])
            ->sort()
            ->all();

        // Jika root sudah ada, jadikan root sebagai parent default
        $defaultParentId = old('parent_id') ?: ($rootUnor?->id);

        return view('admin.unor.create', compact('parentList', 'rootUnor', 'defaultParentId'));
    }

    public function store(Request $request)
    {
        $parentId = $request->parent_id;
        $rootExists = Unor::whereNull('parent_id')->exists();

        $parentRules = $rootExists
            ? ['required', 'exists:unor,id']
            : ['nullable', 'exists:unor,id'];

        $validated = $request->validate([
            'nama_unor' => 'required|string|max:255|unique:unor,nama_unor,NULL,id,parent_id,' . ($parentId ?? 'NULL'),
            'kode_unor' => 'nullable|string|max:255|unique:unor,kode_unor|regex:/^[A-Z0-9_-]+$/',
            'parent_id' => $parentRules,
        ]);

        // Auto-generate kode UNOR jika tidak diisi
        if (empty($validated['kode_unor'])) {
            $lastKode = Unor::where('kode_unor', 'LIKE', 'U-%')
                ->orderByRaw('CAST(SUBSTRING(kode_unor, 3) AS UNSIGNED) DESC')
                ->value('kode_unor');
            $nextNum = 1;
            if ($lastKode && preg_match('/U-(\d+)/', $lastKode, $m)) {
                $nextNum = (int) $m[1] + 1;
            }
            $validated['kode_unor'] = 'U-' . str_pad((string) $nextNum, 3, '0', STR_PAD_LEFT);
        }

        Unor::create($validated);
        return redirect()->route('admin.unor.index')->with('success', 'Unit Organisasi berhasil ditambahkan.');
    }

    public function edit(Unor $unor)
    {
        $excludeIds = $this->getDescendantIds($unor);
        $excludeIds[] = $unor->id;

        $allUnor = Unor::with('parent')->whereNotIn('id', $excludeIds)->get()->keyBy('id');
        $rootUnor = Unor::whereNull('parent_id')->first();
        $parentList = $allUnor
            ->mapWithKeys(fn($u) => [$u->id => $u->pathLabel($allUnor)])
            ->sort()
            ->all();
        return view('admin.unor.edit', compact('unor', 'parentList', 'rootUnor'));
    }

    public function update(Request $request, Unor $unor)
    {
        $parentId = $request->parent_id;

        // Cegah menjadikan UNOR non-root sebagai root baru jika root sudah ada
        $rootLainExists = Unor::whereNull('parent_id')->where('id', '!=', $unor->id)->exists();
        $parentRules = ($unor->parent_id !== null && $rootLainExists)
            ? ['required', 'exists:unor,id']
            : ['nullable', 'exists:unor,id'];

        $validated = $request->validate([
            'nama_unor' => 'required|string|max:255|unique:unor,nama_unor,' . $unor->id . ',id,parent_id,' . ($parentId ?? 'NULL'),
            'kode_unor' => 'required|string|max:255|unique:unor,kode_unor,' . $unor->id . '|regex:/^[A-Z0-9_-]+$/',
            'parent_id' => $parentRules,
        ]);

        $newParentId = $validated['parent_id'] ? (int) $validated['parent_id'] : null;
        if ($newParentId && $this->wouldCreateCycle($unor, $newParentId)) {
            return back()->withInput()->with('error',
                'Tidak dapat menjadikan Unit Organisasi ini atau turunannya sebagai induk.');
        }

        $unor->update($validated);
        return redirect()->route('admin.unor.index')->with('success', 'Unit Organisasi berhasil diperbarui.');
    }

    public function destroy(Unor $unor)
    {
        if ($unor->children()->exists()) return back()->with('error', 'Tidak dapat dihapus karena masih memiliki sub-Unit Organisasi.');
        if ($unor->sotkEntries()->exists()) return back()->with('error', 'Tidak dapat dihapus karena masih memiliki jabatan di SOTK.');
        if ($unor->penempatanPegawai()->exists()) return back()->with('error', 'Tidak dapat dihapus karena masih memiliki data penempatan pegawai.');
        if ($unor->tugasTambahanPegawai()->exists()) return back()->with('error', 'Tidak dapat dihapus karena masih memiliki data tugas tambahan pegawai.');
        if ($unor->kebutuhanPegawai()->exists()) return back()->with('error', 'Tidak dapat dihapus karena masih memiliki data kebutuhan pegawai.');

        try {
            $unor->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'Unit Organisasi tidak dapat dihapus karena masih digunakan oleh data lain.');
        }

        return redirect()->route('admin.unor.index')->with('success', 'Unit Organisasi berhasil dihapus.');
    }

    // ── Helpers ──

    private function wouldCreateCycle(Unor $unor, int $newParentId): bool
    {
        if ($newParentId === $unor->id) return true;
        $currentId = $newParentId;
        $visited = [];
        while ($currentId !== null) {
            if (in_array($currentId, $visited)) return true;
            if ($currentId === $unor->id) return true;
            $visited[] = $currentId;
            $parent = Unor::find($currentId);
            $currentId = $parent ? $parent->parent_id : null;
        }
        return false;
    }

    private function getDescendantIds(Unor $unor): array
    {
        $ids = [];
        $queue = $unor->children()->pluck('id')->toArray();
        while (!empty($queue)) {
            $ids = array_merge($ids, $queue);
            $queue = Unor::whereIn('parent_id', $queue)->pluck('id')->toArray();
        }
        return $ids;
    }
}
