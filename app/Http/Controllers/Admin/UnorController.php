<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unor;
use Illuminate\Http\Request;

class UnorController extends Controller
{
    public function index()
    {
        $allUnor = Unor::with('children')->orderBy('nama_unor')->get()->keyBy('id');

        // Build tree: depth-first flat array (skip root level — show only OPDs)
        $tree = [];
        $rootUnors = $allUnor->filter(fn($u) => $u->parent_id === null);
        foreach ($rootUnors as $root) {
            // Skip root UNOR, start from its children (level 0)
            foreach ($allUnor->filter(fn($u) => $u->parent_id === $root->id) as $child) {
                $this->flattenUnor($child, null, 0, $tree, $allUnor);
            }
        }

        return view('admin.unor.index', compact('tree'));
    }

    private function flattenUnor(Unor $unor, ?int $parentId, int $level, array &$result, $allUnor): void
    {
        $unorIdStr = 'u-' . $unor->id;

        // Get children of this UNOR
        $children = $allUnor->filter(fn($u) => $u->parent_id === $unor->id);

        $result[] = [
            'id' => $unorIdStr,
            'parent_id' => $parentId ? 'u-' . $parentId : '',
            'level' => $level,
            'nama' => $unor->nama_unor,
            'kode' => $unor->kode_unor,
            'singkatan' => $unor->singkatan,
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
            ->mapWithKeys(fn($u) => [$u->id => $this->buildBreadcrumb($u, $allUnor)])
            ->sort()
            ->all();
        return view('admin.unor.create', compact('parentList', 'rootUnor'));
    }

    public function store(Request $request)
    {
        $parentId = $request->parent_id;
        $validated = $request->validate([
            'nama_unor' => 'required|string|max:255|unique:unor,nama_unor,NULL,id,parent_id,' . ($parentId ?? 'NULL'),
            'singkatan' => 'nullable|string|max:10',
            'parent_id' => 'nullable|exists:unor,id',
        ]);

        // Auto-generate kode UNOR
        $lastKode = Unor::where('kode_unor', 'LIKE', 'U-%')
            ->orderByRaw('CAST(SUBSTRING(kode_unor, 3) AS UNSIGNED) DESC')
            ->value('kode_unor');
        $nextNum = 1;
        if ($lastKode && preg_match('/U-(\d+)/', $lastKode, $m)) {
            $nextNum = (int) $m[1] + 1;
        }
        $validated['kode_unor'] = 'U-' . str_pad((string) $nextNum, 3, '0', STR_PAD_LEFT);

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
            ->mapWithKeys(fn($u) => [$u->id => $this->buildBreadcrumb($u, $allUnor)])
            ->sort()
            ->all();
        return view('admin.unor.edit', compact('unor', 'parentList', 'rootUnor'));
    }

    public function update(Request $request, Unor $unor)
    {
        $parentId = $request->parent_id;
        $validated = $request->validate([
            'nama_unor' => 'required|string|max:255|unique:unor,nama_unor,' . $unor->id . ',id,parent_id,' . ($parentId ?? 'NULL'),
            'singkatan' => 'nullable|string|max:10',
            'parent_id' => 'nullable|exists:unor,id',
        ]);

        // Kode UNOR tidak dapat diubah (auto-generated)
        unset($validated['kode_unor']);

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
        if ($unor->pegawai()->exists()) return back()->with('error', 'Tidak dapat dihapus karena masih memiliki pegawai.');
        if ($unor->sotkEntries()->exists()) return back()->with('error', 'Tidak dapat dihapus karena masih memiliki jabatan di SOTK.');
        if ($unor->penempatanPegawai()->exists()) return back()->with('error', 'Tidak dapat dihapus karena masih memiliki data penempatan pegawai.');
        $unor->delete();
        return redirect()->route('admin.unor.index')->with('success', 'Unit Organisasi berhasil dihapus.');
    }

    // ── Helpers ──

    /**
     * Build breadcrumb path: "OPD » Sub » Sub" (tanpa PEMKOT root).
     */
    private function buildBreadcrumb(Unor $unor, $allUnor): string
    {
        $parts = [$unor->nama_unor];
        $cursor = $unor;
        while ($cursor->parent_id) {
            $parent = $allUnor->get($cursor->parent_id);
            if (!$parent || !$parent->parent_id) break; // stop di root
            array_unshift($parts, $parent->nama_unor);
            $cursor = $parent;
        }
        return implode(' » ', $parts);
    }

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
