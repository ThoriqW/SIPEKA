<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferensiJabatan;
use App\Enums\JenisJabatan;
use Illuminate\Http\Request;

class ReferensiJabatanController extends Controller
{
    public function index(Request $request)
    {
        $query = ReferensiJabatan::with('parent')
            ->orderBy('jenis_jabatan')
            ->orderBy('parent_id')
            ->orderBy('nama_jabatan');

        if ($request->filled('search')) {
            $query->where('nama_jabatan', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('jenis_jabatan')) {
            $query->where('jenis_jabatan', $request->jenis_jabatan);
        }

        $masterList = $query->whereNull('parent_id')->get();

        // Ambil semua children
        $parentIds = $masterList->pluck('id');
        $children = ReferensiJabatan::whereIn('parent_id', $parentIds)
            ->orderBy('nama_jabatan')
            ->get()
            ->groupBy('parent_id');
        $jenisJabatanList = JenisJabatan::labels();

        return view('admin.referensi-jabatan.index', compact('masterList', 'children', 'jenisJabatanList'));
    }

    public function create()
    {
        // Hanya root-level (parent_id = null) yang bisa jadi induk
        $parentList = ReferensiJabatan::whereNull('parent_id')
            ->where('jenis_jabatan', 'Fungsional')
            ->orderBy('nama_jabatan')
            ->get()
            ->mapWithKeys(fn($m) => [$m->id => $m->nama_jabatan . ' (' . $m->jenis_jabatan . ')']);

        return view('admin.referensi-jabatan.create', [
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
        $exists = ReferensiJabatan::where('nama_jabatan', $validated['nama_jabatan'])
            ->where('jenis_jabatan', $validated['jenis_jabatan'])
            ->where('parent_id', $validated['parent_id'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Referensi jabatan "' . $validated['nama_jabatan'] . '" sudah ada untuk jenis dan induk yang sama.');
        }

        // Validasi: induk harus root-level (parent_id = null)
        if (!empty($validated['parent_id'])) {
            $induk = ReferensiJabatan::find($validated['parent_id']);
            if ($induk && $induk->parent_id !== null) {
                return back()->withInput()->with('error', 'Induk yang dipilih adalah sub-jabatan. Sub-jabatan tidak bisa menjadi induk. Pilih jabatan utama.');
            }
        }

        ReferensiJabatan::create($validated);

        return redirect()->route('admin.referensi-jabatan.index')
            ->with('success', 'Referensi jabatan berhasil ditambahkan.');
    }

    public function edit(ReferensiJabatan $referensiJabatan)
    {
        $parentList = ReferensiJabatan::whereNull('parent_id')
            ->where('id', '!=', $referensiJabatan->id)
            ->orderBy('jenis_jabatan')
            ->orderBy('nama_jabatan')
            ->get()
            ->mapWithKeys(fn($m) => [$m->id => $m->nama_jabatan . ' (' . $m->jenis_jabatan . ')']);

        return view('admin.referensi-jabatan.edit', [
            'referensiJabatan' => $referensiJabatan,
            'jenisJabatanList' => JenisJabatan::labels(),
            'parentList' => $parentList,
        ]);
    }

    public function update(Request $request, ReferensiJabatan $referensiJabatan)
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
        if (!empty($validated['parent_id']) && $validated['parent_id'] == $referensiJabatan->id) {
            return back()->withInput()->with('error', 'Sub jabatan tidak bisa menjadi induk dari dirinya sendiri.');
        }

        // Validasi: induk harus root-level (parent_id = null)
        if (!empty($validated['parent_id'])) {
            $induk = ReferensiJabatan::find($validated['parent_id']);
            if ($induk && $induk->parent_id !== null) {
                return back()->withInput()->with('error', 'Induk yang dipilih adalah sub-jabatan. Sub-jabatan tidak bisa menjadi induk. Pilih jabatan utama.');
            }
        }

        // Cek duplikat (kecuali record sendiri)
        $exists = ReferensiJabatan::where('nama_jabatan', $validated['nama_jabatan'])
            ->where('jenis_jabatan', $validated['jenis_jabatan'])
            ->where('parent_id', $validated['parent_id'])
            ->where('id', '!=', $referensiJabatan->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Referensi jabatan "' . $validated['nama_jabatan'] . '" sudah ada untuk jenis dan induk yang sama.');
        }

        $referensiJabatan->update($validated);

        return redirect()->route('admin.referensi-jabatan.index')
            ->with('success', 'Referensi jabatan berhasil diperbarui.');
    }

    public function destroy(ReferensiJabatan $referensiJabatan)
    {
        if ($referensiJabatan->children()->exists()) {
            return back()->with('error', 'Referensi jabatan tidak dapat dihapus karena masih memiliki sub jabatan.');
        }

        $referensiJabatan->delete();

        return redirect()->route('admin.referensi-jabatan.index')
            ->with('success', 'Referensi jabatan berhasil dihapus.');
    }
}
