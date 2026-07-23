<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NodeOrganisasi;
use App\Models\Opd;
use Illuminate\Http\Request;

class OpdController extends Controller
{
    public function index(Request $request)
    {
        $query = Opd::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_opd', 'like', "%{$search}%")
                  ->orWhere('kode_opd', 'like', "%{$search}%");
            });
        }
        $opdList = $query->withCount(['jabatan', 'pegawai'])->orderBy('nama_opd')->paginate(15)->withQueryString();
        return view('admin.opd.index', compact('opdList'));
    }

    public function create() { return view('admin.opd.create'); }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_opd' => 'required|string|max:255|unique:opd,nama_opd',
            'kode_opd' => 'required|string|max:50|unique:opd,kode_opd',
        ]);
        $opd = Opd::create($validated);

        // Auto-create root jika belum ada
        $root = NodeOrganisasi::whereNull('parent_id')->first();
        if (!$root) {
            $root = NodeOrganisasi::create([
                'nama' => 'Pemerintah Kota Palu',
                'kode' => 'PEMKOT-PALU',
                'jenis' => 'UNIT',
                'parent_id' => null,
            ]);
        }

        // Auto-create Unor (UNIT node) di bawah root
        NodeOrganisasi::create([
            'nama' => $opd->nama_opd,
            'kode' => $opd->kode_opd,
            'jenis' => 'UNIT',
            'parent_id' => $root->id,
        ]);

        return redirect()->route('admin.opd.index')->with('success', 'OPD berhasil ditambahkan.');
    }

    public function show(Opd $opd)
    {
        $opd->load(['jabatan' => function ($q) { $q->where('jenis_jabatan', 'Struktural'); }]);
        return view('admin.opd.show', compact('opd'));
    }

    public function edit(Opd $opd) { return view('admin.opd.edit', compact('opd')); }

    public function update(Request $request, Opd $opd)
    {
        $oldKode = $opd->kode_opd;

        $validated = $request->validate([
            'nama_opd' => 'required|string|max:255|unique:opd,nama_opd,' . $opd->id,
            'kode_opd' => 'required|string|max:50|unique:opd,kode_opd,' . $opd->id,
        ]);
        $opd->update($validated);

        // Update atau buat ulang Unor terkait
        $unor = NodeOrganisasi::where('kode', $oldKode)->where('jenis', 'UNIT')->first();
        if ($unor) {
            $unor->update(['nama' => $opd->nama_opd, 'kode' => $opd->kode_opd]);
        } else {
            // Unor hilang — buat ulang
            $root = NodeOrganisasi::whereNull('parent_id')->first();
            if ($root) {
                NodeOrganisasi::create([
                    'nama' => $opd->nama_opd, 'kode' => $opd->kode_opd,
                    'jenis' => 'UNIT', 'parent_id' => $root->id,
                ]);
            }
        }

        return redirect()->route('admin.opd.index')->with('success', 'OPD berhasil diperbarui.');
    }

    public function destroy(Opd $opd)
    {
        if ($opd->pegawai()->exists()) return back()->with('error', 'OPD tidak dapat dihapus karena masih memiliki pegawai.');
        if ($opd->jabatan()->exists()) return back()->with('error', 'OPD tidak dapat dihapus karena masih memiliki jabatan.');

        // Cek Unor terkait — jangan hapus kalau masih punya sub-unit/posisi
        $unor = NodeOrganisasi::where('kode', $opd->kode_opd)->where('jenis', 'UNIT')->first();
        if ($unor) {
            if ($unor->children()->exists()) {
                return back()->with('error', 'OPD tidak dapat dihapus karena Unor terkait masih memiliki sub-unit/posisi. Hapus terlebih dahulu dari menu Unor.');
            }
            // Hapus Unor terkait
            $unor->delete();
        }

        $opd->delete();
        return redirect()->route('admin.opd.index')->with('success', 'OPD berhasil dihapus.');
    }
}
