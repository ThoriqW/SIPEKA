<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\Opd;
use App\Enums\Jenjang;
use App\Enums\JenisJabatan;
use Illuminate\Http\Request;

class JabatanController extends Controller
{
    public function index(Request $request)
    {
        $query = Jabatan::query()->with(['opd', 'induk']);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_jabatan', 'like', "%{$search}%")->orWhere('kode_jabatan', 'like', "%{$search}%");
            });
        }
        if ($request->filled('opd_id')) $query->where('opd_id', $request->opd_id);
        if (auth()->user()->role === 'admin_opd') $query->where('opd_id', auth()->user()->opd_id);
        $jabatanList = $query->withCount('pegawai')->orderBy('nama_jabatan')->paginate(15)->withQueryString();
        $opdList = auth()->user()->isBkd() ? Opd::orderBy('nama_opd')->pluck('nama_opd', 'id') : collect();
        return view('admin.jabatan.index', compact('jabatanList', 'opdList'));
    }

    public function create()
    {
        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');
        $indukQuery = Jabatan::with('opd')->where('jenis_jabatan', 'Struktural')->orderBy('nama_jabatan');
        if (auth()->user()->role === 'admin_opd') $indukQuery->where('opd_id', auth()->user()->opd_id);
        $indukList = $indukQuery->get()->mapWithKeys(fn($j) => [$j->id => ($j->opd->nama_opd ?? '?') . ' › ' . $j->nama_jabatan]);
        return view('admin.jabatan.create', [
            'opdList' => $opdList,
            'indukList' => $indukList,
            'jenisJabatanList' => JenisJabatan::labels(),
            'jenjangOptions' => json_encode([
                'Struktural' => Jenjang::forJenisJabatan('Struktural'),
                'Fungsional' => Jenjang::forJenisJabatan('Fungsional'),
                'Pelaksana' => Jenjang::forJenisJabatan('Pelaksana'),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_jabatan' => 'required|string|max:255',
            'kode_jabatan' => 'required|string|max:50',
            'jenis_jabatan' => 'required|in:Struktural,Fungsional,Pelaksana',
            'kelas_jabatan' => 'required|integer|min:1',
            'jenjang' => 'required|string|max:255',
            'kebutuhan' => 'required_if:jenis_jabatan,Fungsional,Pelaksana|integer|min:0|nullable',
            'opd_id' => 'required|exists:opd,id',
            'induk_jabatan_id' => 'nullable|exists:jabatan,id',
        ]);

        // Validasi: hanya jabatan Struktural yang boleh menjadi induk
        if (!empty($validated['induk_jabatan_id'])) {
            $induk = Jabatan::find($validated['induk_jabatan_id']);
            if ($induk && $induk->jenis_jabatan !== 'Struktural') {
                return back()->withInput()->with('error', 'Induk jabatan harus berjenis Struktural. Fungsional dan Pelaksana tidak dapat menjadi induk.');
            }
        }

        if (auth()->user()->role === 'admin_opd') $validated['opd_id'] = auth()->user()->opd_id;
        if ($validated['jenis_jabatan'] === 'Struktural') $validated['kebutuhan'] = null;
        Jabatan::create($validated);
        return redirect()->route('admin.jabatan.index')->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function edit(Jabatan $jabatan)
    {
        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');
        $indukQuery = Jabatan::with('opd')->where('id', '!=', $jabatan->id)->where('jenis_jabatan', 'Struktural')->orderBy('nama_jabatan');
        if (auth()->user()->role === 'admin_opd') $indukQuery->where('opd_id', auth()->user()->opd_id);
        $indukList = $indukQuery->get()->mapWithKeys(fn($j) => [$j->id => ($j->opd->nama_opd ?? '?') . ' › ' . $j->nama_jabatan]);
        return view('admin.jabatan.edit', [
            'jabatan' => $jabatan,
            'opdList' => $opdList,
            'indukList' => $indukList,
            'jenisJabatanList' => JenisJabatan::labels(),
            'jenjangOptions' => json_encode([
                'Struktural' => Jenjang::forJenisJabatan('Struktural'),
                'Fungsional' => Jenjang::forJenisJabatan('Fungsional'),
                'Pelaksana' => Jenjang::forJenisJabatan('Pelaksana'),
            ]),
        ]);
    }

    public function update(Request $request, Jabatan $jabatan)
    {
        $validated = $request->validate([
            'nama_jabatan' => 'required|string|max:255',
            'kode_jabatan' => 'required|string|max:50',
            'jenis_jabatan' => 'required|in:Struktural,Fungsional,Pelaksana',
            'kelas_jabatan' => 'required|integer|min:1',
            'jenjang' => 'required|string|max:255',
            'kebutuhan' => 'required_if:jenis_jabatan,Fungsional,Pelaksana|integer|min:0|nullable',
            'opd_id' => 'required|exists:opd,id',
            'induk_jabatan_id' => 'nullable|exists:jabatan,id',
        ]);
        // Validasi: hanya jabatan Struktural yang boleh menjadi induk
        if (!empty($validated['induk_jabatan_id'])) {
            $induk = Jabatan::find($validated['induk_jabatan_id']);
            if ($induk && $induk->jenis_jabatan !== 'Struktural') {
                return back()->withInput()->with('error', 'Induk jabatan harus berjenis Struktural. Fungsional dan Pelaksana tidak dapat menjadi induk.');
            }
        }

        if (auth()->user()->role === 'admin_opd') $validated['opd_id'] = auth()->user()->opd_id;
        if ($validated['jenis_jabatan'] === 'Struktural') $validated['kebutuhan'] = null;
        $jabatan->update($validated);
        return redirect()->route('admin.jabatan.index')->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Jabatan $jabatan)
    {
        if ($jabatan->anak()->exists()) return back()->with('error', 'Jabatan tidak dapat dihapus karena masih memiliki turunan.');
        if ($jabatan->pegawai()->exists()) return back()->with('error', 'Jabatan tidak dapat dihapus karena masih memiliki pegawai.');
        $jabatan->delete();
        return redirect()->route('admin.jabatan.index')->with('success', 'Jabatan berhasil dihapus.');
    }

    public function getByOpd(Request $request)
    {
        $request->validate(['opd_id' => 'required|exists:opd,id']);
        $jabatanList = Jabatan::with('induk')->withCount('pegawai')->where('opd_id', $request->opd_id)->orderBy('nama_jabatan')->get()
            ->map(fn($j) => [
                'id' => $j->id,
                'nama' => ($j->induk ? $j->induk->nama_jabatan . ' › ' : '') . $j->nama_jabatan,
                'jenis_jabatan' => $j->jenis_jabatan,
                'jenjang' => $j->jenjang,
                'pegawai_count' => $j->pegawai_count,
            ]);
        return response()->json(['success' => true, 'data' => $jabatanList]);
    }
}
