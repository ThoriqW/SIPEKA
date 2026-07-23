<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JabatanAsn;
use App\Enums\JenisJabatan;
use App\Enums\Jenjang;
use App\Services\KodeNodeGenerator;
use Illuminate\Http\Request;

class JabatanAsnController extends Controller
{
    public function __construct(
        private KodeNodeGenerator $kodeGenerator,
    ) {}

    /**
     * Daftar semua jabatan ASN.
     */
    public function index(Request $request)
    {
        $query = JabatanAsn::query()->with('parent');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nama_jabatan_asn', 'like', "%{$search}%");
        }

        if ($request->filled('jenis_jabatan')) {
            $query->where('jenis_jabatan', $request->jenis_jabatan);
        }

        $jabatanAsnList = $query->orderBy('jenis_jabatan')
            ->orderBy('nama_jabatan_asn')
            ->paginate(20)
            ->withQueryString();

        $jenisJabatanList = JenisJabatan::labels();

        return view('admin.jabatan-asn.index', compact('jabatanAsnList', 'jenisJabatanList'));
    }

    /**
     * Form create jabatan ASN.
     */
    public function create()
    {
        // Parent: hanya root-level entries untuk grouping
        $parentList = JabatanAsn::root()
            ->orderBy('nama_jabatan_asn')
            ->pluck('nama_jabatan_asn', 'id');

        return view('admin.jabatan-asn.create', [
            'jenisJabatanList' => JenisJabatan::labels(),
            'jenjangOptions' => [
                'Struktural' => Jenjang::forJenisJabatan('Struktural'),
                'Fungsional' => Jenjang::forJenisJabatan('Fungsional'),
                'Pelaksana' => Jenjang::forJenisJabatan('Pelaksana'),
            ],
            'parentList' => $parentList,
        ]);
    }

    /**
     * Simpan jabatan ASN baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_jabatan_asn' => 'required|string|max:255',
            'jenis_jabatan' => 'required|in:Struktural,Fungsional,Pelaksana',
            'jenjang' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:jabatan_asn,id',
        ]);

        // Cek duplikat
        $exists = JabatanAsn::where('nama_jabatan_asn', $validated['nama_jabatan_asn'])
            ->where('jenis_jabatan', $validated['jenis_jabatan'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error',
                'Jabatan ASN "' . $validated['nama_jabatan_asn'] . '" sudah ada untuk jenis ' . $validated['jenis_jabatan'] . '.');
        }

        // Auto-generate kode
        $validated['kode_jabatan_asn'] = $this->kodeGenerator->generateKodeJabatanAsn();

        JabatanAsn::create($validated);

        return redirect()
            ->route('admin.jabatan-asn.index')
            ->with('success', 'Jabatan ASN berhasil ditambahkan.');
    }

    /**
     * Form edit jabatan ASN.
     */
    public function edit(JabatanAsn $jabatanAsn)
    {
        // Parent: root-level, exclude self
        $parentList = JabatanAsn::root()
            ->where('id', '!=', $jabatanAsn->id)
            ->orderBy('nama_jabatan_asn')
            ->pluck('nama_jabatan_asn', 'id');

        return view('admin.jabatan-asn.edit', [
            'jabatanAsn' => $jabatanAsn,
            'jenisJabatanList' => JenisJabatan::labels(),
            'jenjangOptions' => [
                'Struktural' => Jenjang::forJenisJabatan('Struktural'),
                'Fungsional' => Jenjang::forJenisJabatan('Fungsional'),
                'Pelaksana' => Jenjang::forJenisJabatan('Pelaksana'),
            ],
            'parentList' => $parentList,
        ]);
    }

    /**
     * Update jabatan ASN.
     */
    public function update(Request $request, JabatanAsn $jabatanAsn)
    {
        $validated = $request->validate([
            'nama_jabatan_asn' => 'required|string|max:255',
            'jenis_jabatan' => 'required|in:Struktural,Fungsional,Pelaksana',
            'jenjang' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:jabatan_asn,id',
        ]);

        // Cek duplikat (kecuali diri sendiri)
        $exists = JabatanAsn::where('nama_jabatan_asn', $validated['nama_jabatan_asn'])
            ->where('jenis_jabatan', $validated['jenis_jabatan'])
            ->where('id', '!=', $jabatanAsn->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error',
                'Jabatan ASN "' . $validated['nama_jabatan_asn'] . '" sudah ada.');
        }

        // Cegah circular reference
        if (!empty($validated['parent_id']) && (int) $validated['parent_id'] === $jabatanAsn->id) {
            return back()->withInput()->with('error', 'Tidak bisa menjadi parent dari diri sendiri.');
        }

        // Kode tidak dapat diubah
        unset($validated['kode_jabatan_asn']);

        $jabatanAsn->update($validated);

        return redirect()
            ->route('admin.jabatan-asn.index')
            ->with('success', 'Jabatan ASN berhasil diperbarui.');
    }

    /**
     * Hapus jabatan ASN.
     */
    public function destroy(JabatanAsn $jabatanAsn)
    {
        if ($jabatanAsn->children()->exists()) {
            return back()->with('error', 'Jabatan ASN tidak dapat dihapus karena masih memiliki sub-jabatan.');
        }
        if ($jabatanAsn->pegawai()->exists()) {
            return back()->with('error', 'Jabatan ASN tidak dapat dihapus karena masih digunakan oleh pegawai.');
        }

        $jabatanAsn->delete();

        return redirect()
            ->route('admin.jabatan-asn.index')
            ->with('success', 'Jabatan ASN berhasil dihapus.');
    }
}
