<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\JabatanAsn;
use App\Models\NodeOrganisasi;
use App\Models\Pegawai;
use App\Models\Opd;
use App\Enums\GolonganPangkat;
use App\Enums\JenisKepegawaian;
use App\Enums\Pendidikan;
use App\Services\NipParser;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    public function index(Request $request)
    {
        $query = Pegawai::query()->with(['opd', 'jabatan.induk']);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%");
            });
        }
        if ($request->filled('opd_id')) $query->where('opd_id', $request->opd_id);
        $pegawaiList = $query->orderBy('nama')->paginate(15)->withQueryString();
        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');
        return view('admin.pegawai.index', compact('pegawaiList', 'opdList'));
    }

    public function create()
    {
        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');

        // Data untuk dropdown Jabatan ASN
        $jabatanAsnList = JabatanAsn::orderBy('jenis_jabatan')
            ->orderBy('nama_jabatan_asn')
            ->get()
            ->mapWithKeys(fn($j) => [
                $j->id => $j->nama_jabatan_asn . ' (' . $j->jenis_jabatan . ' - ' . ($j->jenjang ?? '-') . ')'
            ]);

        // Data untuk dropdown Posisi Organisasi (semua POSISI yang belum terisi)
        // Akan di-load via AJAX per OPD, tapi kita berikan initial data
        $posisiList = NodeOrganisasi::posisi()
            ->with('pegawai')
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn($p) => [
                $p->id => $p->nama . ($p->isTerisi() ? ' [TERISI]' : '')
            ]);

        return view('admin.pegawai.create', [
            'opdList' => $opdList,
            'jabatanAsnList' => $jabatanAsnList,
            'posisiList' => $posisiList,
            'golonganPangkatList' => GolonganPangkat::labels(),
            'pppkGolonganList' => GolonganPangkat::pppkLabels(),
            'jenisKepegawaianList' => JenisKepegawaian::labels(),
            'pendidikanList' => Pendidikan::labels(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'required|string|size:18|unique:pegawai,nip',
            'nama' => 'required|string|max:255',
            'jenis_kepegawaian' => 'required|in:PNS,PPPK',
            'tanggal_lahir' => 'required|date',
            'golongan_pangkat' => 'required',
            'pendidikan' => 'required',
            'kualifikasi_pendidikan' => 'nullable|string|max:255',
            'opd_id' => 'required|exists:opd,id',
            'jabatan_id' => 'nullable|exists:jabatan,id',            // @deprecated
            'jabatan_asn_id' => 'nullable|exists:jabatan_asn,id',    // NEW
            'posisi_organisasi_id' => 'nullable|exists:node_organisasi,id', // NEW
        ]);

        // --- Model baru: validasi POSISI ---
        if (!empty($validated['posisi_organisasi_id'])) {
            $posisi = NodeOrganisasi::find($validated['posisi_organisasi_id']);

            // Hanya POSISI yang bisa diisi, bukan UNIT
            if ($posisi && $posisi->isUnit()) {
                return back()->withInput()->with('error',
                    'Node "' . $posisi->nama . '" adalah Unit Organisasi, bukan Posisi. Unit tidak dapat diisi pegawai.');
            }

            // Satu POSISI hanya 1 pegawai
            if ($posisi && $posisi->isTerisi()) {
                return back()->withInput()->with('error',
                    'Posisi "' . $posisi->nama . '" sudah terisi oleh pegawai lain. Satu posisi hanya boleh diisi 1 pegawai.');
            }
        }

        // --- Model lama (backward compat): validasi jabatan struktural ---
        if (!empty($validated['jabatan_id'])) {
            $jabatan = Jabatan::withCount('pegawai')->find($validated['jabatan_id']);
            if ($jabatan && $jabatan->jenis_jabatan === 'Struktural' && $jabatan->pegawai_count >= 1) {
                return back()->withInput()->with('error',
                    'Jabatan Struktural "' . $jabatan->nama_jabatan . '" sudah terisi. Hanya boleh 1 pegawai per jabatan struktural.');
            }
        }

        // Auto-fill jenjang dari Jabatan ASN (prioritas) atau jabatan lama
        if (!empty($validated['jabatan_asn_id'])) {
            $jabatanAsn = JabatanAsn::find($validated['jabatan_asn_id']);
            $validated['jenjang'] = $jabatanAsn?->jenjang;
        } elseif (!empty($validated['jabatan_id'])) {
            $jabatan = Jabatan::withCount('pegawai')->find($validated['jabatan_id']);
            $validated['jenjang'] = $jabatan?->jenjang;
        } else {
            $validated['jenjang'] = null;
        }

        Pegawai::create($validated);
        return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function edit(Pegawai $pegawai)
    {
        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');

        $jabatanAsnList = JabatanAsn::orderBy('jenis_jabatan')
            ->orderBy('nama_jabatan_asn')
            ->get()
            ->mapWithKeys(fn($j) => [
                $j->id => $j->nama_jabatan_asn . ' (' . $j->jenis_jabatan . ' - ' . ($j->jenjang ?? '-') . ')'
            ]);

        $posisiList = NodeOrganisasi::posisi()
            ->with('pegawai')
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn($p) => [
                $p->id => $p->nama . ($p->isTerisi() ? ' [TERISI]' : '')
            ]);

        return view('admin.pegawai.edit', [
            'pegawai' => $pegawai,
            'opdList' => $opdList,
            'jabatanAsnList' => $jabatanAsnList,
            'posisiList' => $posisiList,
            'golonganPangkatList' => GolonganPangkat::labels(),
            'pppkGolonganList' => GolonganPangkat::pppkLabels(),
            'jenisKepegawaianList' => JenisKepegawaian::labels(),
            'pendidikanList' => Pendidikan::labels(),
        ]);
    }

    public function update(Request $request, Pegawai $pegawai)
    {
        $validated = $request->validate([
            'nip' => 'required|string|size:18|unique:pegawai,nip,' . $pegawai->id,
            'nama' => 'required|string|max:255',
            'jenis_kepegawaian' => 'required|in:PNS,PPPK',
            'tanggal_lahir' => 'required|date',
            'golongan_pangkat' => 'required',
            'pendidikan' => 'required',
            'kualifikasi_pendidikan' => 'nullable|string|max:255',
            'opd_id' => 'required|exists:opd,id',
            'jabatan_id' => 'nullable|exists:jabatan,id',            // @deprecated
            'jabatan_asn_id' => 'nullable|exists:jabatan_asn,id',    // NEW
            'posisi_organisasi_id' => 'nullable|exists:node_organisasi,id', // NEW
        ]);

        // --- Model baru: validasi POSISI ---
        if (!empty($validated['posisi_organisasi_id'])
            && $validated['posisi_organisasi_id'] != $pegawai->posisi_organisasi_id) {

            $posisi = NodeOrganisasi::find($validated['posisi_organisasi_id']);

            if ($posisi && $posisi->isUnit()) {
                return back()->withInput()->with('error',
                    'Node "' . $posisi->nama . '" adalah Unit Organisasi, bukan Posisi. Unit tidak dapat diisi pegawai.');
            }

            if ($posisi && $posisi->isTerisi()) {
                return back()->withInput()->with('error',
                    'Posisi "' . $posisi->nama . '" sudah terisi oleh pegawai lain. Satu posisi hanya boleh diisi 1 pegawai.');
            }
        }

        // --- Model lama (backward compat) ---
        if (!empty($validated['jabatan_id']) && $validated['jabatan_id'] != $pegawai->jabatan_id) {
            $jabatan = Jabatan::withCount('pegawai')->find($validated['jabatan_id']);
            if ($jabatan && $jabatan->jenis_jabatan === 'Struktural' && $jabatan->pegawai_count >= 1) {
                return back()->withInput()->with('error',
                    'Jabatan Struktural "' . $jabatan->nama_jabatan . '" sudah terisi.');
            }
        }

        // Auto-fill jenjang
        if (!empty($validated['jabatan_asn_id'])) {
            $jabatanAsn = JabatanAsn::find($validated['jabatan_asn_id']);
            $validated['jenjang'] = $jabatanAsn?->jenjang;
        } elseif (!empty($validated['jabatan_id'])) {
            $jabatan = Jabatan::find($validated['jabatan_id']);
            $validated['jenjang'] = $jabatan?->jenjang;
        } else {
            $validated['jenjang'] = null;
        }

        $pegawai->update($validated);
        return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai berhasil diperbarui.');
    }

    public function destroy(Pegawai $pegawai)
    {
        $pegawai->delete();
        return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai berhasil dihapus.');
    }

    public function extractTanggalLahir(Request $request)
    {
        $request->validate(['nip' => 'required|string|size:18']);
        $tanggalLahir = app(NipParser::class)->extractTanggalLahir($request->nip);
        if (!$tanggalLahir) return response()->json(['success' => false, 'message' => 'NIP tidak valid.'], 422);
        return response()->json(['success' => true, 'tanggal_lahir' => $tanggalLahir]);
    }

    /**
     * AJAX: Dapatkan daftar POSISI yang tersedia.
     * Menerima parent_id (unit organisasi) dan kembalikan semua POSISI dalam subtree.
     */
    public function getPosisiByUnit(Request $request)
    {
        $request->validate(['parent_id' => 'nullable|exists:node_organisasi,id']);

        $query = NodeOrganisasi::posisi()->with('pegawai');

        if ($request->filled('parent_id')) {
            // Ambil semua node dalam subtree parent
            $parent = NodeOrganisasi::find($request->parent_id);
            $descendantIds = $parent->getDescendantIds();
            $descendantIds[] = (int) $request->parent_id;
            $query->whereIn('id', $descendantIds);
        }

        $posisiList = $query->orderBy('nama')->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'kode' => $p->kode,
                'terisi' => $p->isTerisi(),
            ]);

        return response()->json(['success' => true, 'data' => $posisiList]);
    }
}
