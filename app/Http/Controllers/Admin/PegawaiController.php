<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\Opd;
use App\Models\Jabatan;
use App\Enums\GolonganPangkat;
use App\Enums\JenisKepegawaian;
use App\Enums\Jenjang;
use App\Enums\Pendidikan;
use App\Services\NipParser;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    public function index(Request $request)
    {
        $query = Pegawai::query()->with(['opd', 'jabatan']);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%");
            });
        }
        if ($request->filled('opd_id')) $query->where('opd_id', $request->opd_id);
        if (auth()->user()->role === 'admin_opd') $query->where('opd_id', auth()->user()->opd_id);
        $pegawaiList = $query->orderBy('nama')->paginate(15)->withQueryString();
        $opdList = auth()->user()->isBkd() ? Opd::orderBy('nama_opd')->pluck('nama_opd', 'id') : collect();
        return view('admin.pegawai.index', compact('pegawaiList', 'opdList'));
    }

    public function create()
    {
        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');
        $jabatanQuery = Jabatan::orderBy('nama_jabatan');
        if (auth()->user()->role === 'admin_opd') $jabatanQuery->where('opd_id', auth()->user()->opd_id);
        $jabatanList = $jabatanQuery->pluck('nama_jabatan', 'id');
        return view('admin.pegawai.create', [
            'opdList' => $opdList,
            'jabatanList' => $jabatanList,
            'golonganPangkatList' => GolonganPangkat::labels(),
            'jenisKepegawaianList' => JenisKepegawaian::labels(),
            'jenjangList' => Jenjang::labels(),
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
            'jenjang' => 'required',
            'opd_id' => 'required|exists:opd,id',
            'jabatan_id' => 'nullable|exists:jabatan,id',
        ]);
        if (auth()->user()->role === 'admin_opd') $validated['opd_id'] = auth()->user()->opd_id;
        Pegawai::create($validated);
        return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function edit(Pegawai $pegawai)
    {
        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');
        $jabatanQuery = Jabatan::orderBy('nama_jabatan');
        if (auth()->user()->role === 'admin_opd') $jabatanQuery->where('opd_id', auth()->user()->opd_id);
        $jabatanList = $jabatanQuery->pluck('nama_jabatan', 'id');
        return view('admin.pegawai.edit', [
            'pegawai' => $pegawai,
            'opdList' => $opdList,
            'jabatanList' => $jabatanList,
            'golonganPangkatList' => GolonganPangkat::labels(),
            'jenisKepegawaianList' => JenisKepegawaian::labels(),
            'jenjangList' => Jenjang::labels(),
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
            'jenjang' => 'required',
            'opd_id' => 'required|exists:opd,id',
            'jabatan_id' => 'nullable|exists:jabatan,id',
        ]);
        if (auth()->user()->role === 'admin_opd') $validated['opd_id'] = auth()->user()->opd_id;
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
}
