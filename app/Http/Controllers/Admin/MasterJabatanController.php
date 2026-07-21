<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterJabatan;
use App\Enums\JenisJabatan;
use Illuminate\Http\Request;

class MasterJabatanController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterJabatan::with('parent')
            ->orderBy('jenis_jabatan')
            ->orderBy('parent_id')
            ->orderBy('nama_jabatan');

        if ($request->filled('jenis_jabatan')) {
            $query->where('jenis_jabatan', $request->jenis_jabatan);
        }

        $masterList = $query->whereNull('parent_id')->get();

        // Ambil semua children
        $parentIds = $masterList->pluck('id');
        $children = MasterJabatan::whereIn('parent_id', $parentIds)
            ->orderBy('nama_jabatan')
            ->get()
            ->groupBy('parent_id');
        $jenisJabatanList = JenisJabatan::labels();

        return view('admin.master-jabatan.index', compact('masterList', 'children', 'jenisJabatanList'));
    }

    public function create()
    {
        $parentList = MasterJabatan::orderBy('jenis_jabatan')
            ->orderBy('nama_jabatan')
            ->get()
            ->mapWithKeys(fn($m) => [$m->id => $m->nama_jabatan . ' (' . $m->jenis_jabatan . ')']);

        return view('admin.master-jabatan.create', [
            'jenisJabatanList' => JenisJabatan::labels(),
            'parentList' => $parentList,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_jabatan' => 'required|string|max:255',
            'jenis_jabatan' => 'required|in:Struktural,Fungsional,Pelaksana',
            'parent_id' => 'nullable|exists:master_jabatan,id',
        ]);

        if ($validated['jenis_jabatan'] !== 'Fungsional') {
            $validated['parent_id'] = null;
        }

        // Cek duplikat: nama + jenis + parent_id harus unik
        $exists = MasterJabatan::where('nama_jabatan', $validated['nama_jabatan'])
            ->where('jenis_jabatan', $validated['jenis_jabatan'])
            ->where('parent_id', $validated['parent_id'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Master jabatan "' . $validated['nama_jabatan'] . '" sudah ada untuk jenis dan induk yang sama.');
        }

        MasterJabatan::create($validated);

        return redirect()->route('admin.master-jabatan.index')
            ->with('success', 'Master jabatan berhasil ditambahkan.');
    }

    public function edit(MasterJabatan $masterJabatan)
    {
        $parentList = MasterJabatan::where('id', '!=', $masterJabatan->id)
            ->orderBy('jenis_jabatan')
            ->orderBy('nama_jabatan')
            ->get()
            ->mapWithKeys(fn($m) => [$m->id => $m->nama_jabatan . ' (' . $m->jenis_jabatan . ')']);

        return view('admin.master-jabatan.edit', [
            'masterJabatan' => $masterJabatan,
            'jenisJabatanList' => JenisJabatan::labels(),
            'parentList' => $parentList,
        ]);
    }

    public function update(Request $request, MasterJabatan $masterJabatan)
    {
        $validated = $request->validate([
            'nama_jabatan' => 'required|string|max:255',
            'jenis_jabatan' => 'required|in:Struktural,Fungsional,Pelaksana',
            'parent_id' => 'nullable|exists:master_jabatan,id',
        ]);

        if ($validated['jenis_jabatan'] !== 'Fungsional') {
            $validated['parent_id'] = null;
        }

        // Prevent self-reference
        if (!empty($validated['parent_id']) && $validated['parent_id'] == $masterJabatan->id) {
            return back()->withInput()->with('error', 'Sub jabatan tidak bisa menjadi induk dari dirinya sendiri.');
        }

        // Cek duplikat (kecuali record sendiri)
        $exists = MasterJabatan::where('nama_jabatan', $validated['nama_jabatan'])
            ->where('jenis_jabatan', $validated['jenis_jabatan'])
            ->where('parent_id', $validated['parent_id'])
            ->where('id', '!=', $masterJabatan->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Master jabatan "' . $validated['nama_jabatan'] . '" sudah ada untuk jenis dan induk yang sama.');
        }

        $masterJabatan->update($validated);

        return redirect()->route('admin.master-jabatan.index')
            ->with('success', 'Master jabatan berhasil diperbarui.');
    }

    public function destroy(MasterJabatan $masterJabatan)
    {
        if ($masterJabatan->children()->exists()) {
            return back()->with('error', 'Master jabatan tidak dapat dihapus karena masih memiliki sub jabatan.');
        }

        $masterJabatan->delete();

        return redirect()->route('admin.master-jabatan.index')
            ->with('success', 'Master jabatan berhasil dihapus.');
    }
}
