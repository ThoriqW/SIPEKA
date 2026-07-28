<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\KebutuhanPegawai;
use App\Models\Sotk;
use App\Models\Unor;
use Illuminate\Http\Request;

class OpdController extends Controller
{
    public function index(Request $request)
    {
        $query = Unor::query()->with('parent');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_unor', 'like', "%{$search}%")
                  ->orWhere('kode_unor', 'like', "%{$search}%");
            });
        }
        $opdList = $query->withCount(['jabatan', 'pegawai', 'children'])
            ->orderBy('nama_unor')->paginate(15)->withQueryString();
        return view('admin.opd.index', compact('opdList'));
    }

    public function create()
    {
        $parentList = Unor::orderBy('nama_unor')->pluck('nama_unor', 'id');
        return view('admin.opd.create', compact('parentList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_unor' => 'required|string|max:255|unique:unor,nama_unor',
            'kode_unor' => 'required|string|max:50|unique:unor,kode_unor',
            'singkatan' => 'nullable|string|max:10',
            'parent_id' => 'nullable|exists:unor,id',
        ]);

        // Prevent circular reference: parent must exist and not create a cycle
        if ($validated['parent_id']) {
            $parent = Unor::findOrFail($validated['parent_id']);
            // No cycle possible for new UNOR (it has no children yet)
        }

        Unor::create($validated);
        return redirect()->route('admin.opd.index')->with('success', 'UNOR berhasil ditambahkan.');
    }

    public function show(Unor $opd)
    {
        $opd->load(['sotkEntries.jabatan', 'children', 'parent']);

        // Load kebutuhan existing untuk UNOR ini
        $kebutuhanList = KebutuhanPegawai::where('unor_id', $opd->id)
            ->whereNull('tahun')
            ->pluck('jumlah', 'jabatan_id');

        $availableJabatan = Jabatan::orderBy('nama_jabatan')->get();
        return view('admin.opd.show', compact('opd', 'availableJabatan', 'kebutuhanList'));
    }

    public function edit(Unor $opd)
    {
        // Exclude self + descendants from parent selection
        $excludeIds = $this->getDescendantIds($opd);
        $excludeIds[] = $opd->id;

        $parentList = Unor::whereNotIn('id', $excludeIds)
            ->orderBy('nama_unor')->pluck('nama_unor', 'id');
        return view('admin.opd.edit', compact('opd', 'parentList'));
    }

    public function update(Request $request, Unor $opd)
    {
        $validated = $request->validate([
            'nama_unor' => 'required|string|max:255|unique:unor,nama_unor,' . $opd->id,
            'kode_unor' => 'required|string|max:50|unique:unor,kode_unor,' . $opd->id,
            'singkatan' => 'nullable|string|max:10',
            'parent_id' => 'nullable|exists:unor,id',
        ]);

        // Prevent circular reference
        $newParentId = $validated['parent_id'] ? (int) $validated['parent_id'] : null;
        if ($newParentId && $this->wouldCreateCycle($opd, $newParentId)) {
            return back()->withInput()->with('error',
                'Tidak dapat menjadikan UNOR ini atau turunannya sebagai induk.');
        }

        $opd->update($validated);
        return redirect()->route('admin.opd.index')->with('success', 'UNOR berhasil diperbarui.');
    }

    public function destroy(Unor $opd)
    {
        if ($opd->children()->exists()) return back()->with('error', 'UNOR tidak dapat dihapus karena masih memiliki sub-UNOR.');
        if ($opd->pegawai()->exists()) return back()->with('error', 'UNOR tidak dapat dihapus karena masih memiliki pegawai.');
        if ($opd->sotkEntries()->exists()) return back()->with('error', 'UNOR tidak dapat dihapus karena masih memiliki jabatan di SOTK.');
        $opd->delete();
        return redirect()->route('admin.opd.index')->with('success', 'UNOR berhasil dihapus.');
    }

    // ────────────────────────────────────────────
    // SOTK Management
    // ────────────────────────────────────────────

    /**
     * Assign jabatan ke UNOR (tambah ke SOTK).
     */
    public function assignJabatan(Request $request, Unor $opd)
    {
        $validated = $request->validate([
            'jabatan_id' => 'required|exists:jabatan,id',
        ]);

        $exists = Sotk::where('unor_id', $opd->id)
            ->where('jabatan_id', $validated['jabatan_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Jabatan ini sudah terdaftar di UNOR ini.');
        }

        Sotk::create([
            'unor_id' => $opd->id,
            'jabatan_id' => $validated['jabatan_id'],
        ]);

        return back()->with('success', 'Jabatan berhasil ditambahkan ke UNOR.');
    }

    /**
     * Remove jabatan dari UNOR (hapus dari SOTK).
     */
    public function removeJabatan(Unor $opd, Sotk $sotk)
    {
        if ($sotk->unor_id !== $opd->id) {
            abort(404);
        }
        $sotk->delete();
        return back()->with('success', 'Jabatan berhasil dihapus dari UNOR.');
    }

    // ────────────────────────────────────────────
    // Kebutuhan Management
    // ────────────────────────────────────────────

    /**
     * Update kebutuhan pegawai untuk suatu jabatan di UNOR ini.
     */
    public function updateKebutuhan(Request $request, Unor $opd)
    {
        $validated = $request->validate([
            'jabatan_id' => 'required|exists:jabatan,id',
            'jumlah' => 'required|integer|min:0',
        ]);

        KebutuhanPegawai::updateOrCreate(
            [
                'unor_id' => $opd->id,
                'jabatan_id' => $validated['jabatan_id'],
                'tahun' => null,
            ],
            [
                'jumlah' => $validated['jumlah'],
            ]
        );

        return back()->with('success', 'Kebutuhan berhasil diperbarui.');
    }

    // ────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────

    /**
     * Cek apakah mengubah parent ke newParentId akan membuat siklus.
     */
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

    /**
     * Dapatkan semua ID turunan (anak, cucu, dst) dari suatu UNOR.
     */
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
