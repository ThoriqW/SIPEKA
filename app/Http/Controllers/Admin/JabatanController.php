<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\KebutuhanPegawai;
use App\Models\ReferensiJabatan;
use App\Models\Unor;
use App\Enums\Jenjang;
use App\Enums\JenisJabatan;
use App\Services\KodeJabatanGenerator;
use Illuminate\Http\Request;

class JabatanController extends Controller
{
    public function index(Request $request)
    {
        $query = Jabatan::query()->with(['opd']);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_jabatan', 'like', "%{$search}%")->orWhere('kode_jabatan', 'like', "%{$search}%");
            });
        }
        if ($request->filled('opd_id')) $query->where('opd_id', $request->opd_id);

        $jabatanList = $query->withCount('pegawai')->orderBy('nama_jabatan')->paginate(15)->withQueryString();
        $opdList = Unor::orderBy('nama_unor')->pluck('nama_unor', 'id');
        return view('admin.jabatan.index', compact('jabatanList', 'opdList'));
    }

    public function create()
    {
        $opdList = Unor::orderBy('nama_unor')->pluck('nama_unor', 'id');

        return view('admin.jabatan.create', [
            'opdList' => $opdList,
            'indukList' => collect(),
            'indukByOpd' => [],
            'jenisJabatanList' => JenisJabatan::labels(),
            'jenjangOptions' => [
                'Struktural' => Jenjang::forJenisJabatan('Struktural'),
                'Fungsional' => Jenjang::forJenisJabatan('Fungsional'),
                'Pelaksana' => Jenjang::forJenisJabatan('Pelaksana'),
            ],
            'masterJabatanData' => $this->buildMasterJabatanData(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_jabatan' => 'required|string|max:255',
            'jenis_jabatan' => 'required|in:Struktural,Fungsional,Pelaksana',
            'kelas_jabatan' => 'required|integer|min:1',
            'jenjang' => 'nullable|string|max:255',
            'kebutuhan' => 'nullable|integer|min:0',
            'opd_id' => 'required|exists:unor,id',
        ]);

        unset($validated['kebutuhan']);

        // Validasi: nama_jabatan harus ada di master_jabatan (cek parent-sub format)
        $parts = explode(' - ', $validated['nama_jabatan']);
        $namaParent = $parts[0];
        $namaSub = count($parts) > 1 ? $parts[1] : null;

        $parentMaster = ReferensiJabatan::where('nama_jabatan', $namaParent)
            ->where('jenis_jabatan', $validated['jenis_jabatan'])
            ->whereNull('parent_id')
            ->first();

        if (!$parentMaster) {
            return back()->withInput()->with('error', 'Nama jabatan "' . $namaParent . '" tidak ditemukan di Referensi Jabatan. Silakan pilih dari daftar yang tersedia.');
        }

        // Jika ada sub-jabatan, validasi bahwa sub adalah child valid dari parent
        if ($namaSub) {
            $subExists = ReferensiJabatan::where('nama_jabatan', $namaSub)
                ->where('jenis_jabatan', $validated['jenis_jabatan'])
                ->where('parent_id', $parentMaster->id)
                ->exists();

            if (!$subExists) {
                return back()->withInput()->with('error', 'Sub jabatan "' . $namaSub . '" tidak valid untuk "' . $namaParent . '". Silakan pilih dari daftar yang tersedia.');
            }
        }

        // Validasi: satu UNOR hanya boleh memiliki satu JPTP (Kepala OPD)
        $isPratama = $validated['jenis_jabatan'] === 'Struktural' && ($validated['jenjang'] ?? '') === 'Pimpinan Tinggi Pratama';
        if ($isPratama) {
            $existingPratama = Jabatan::where('opd_id', $validated['opd_id'])
                ->where('jenis_jabatan', 'Struktural')
                ->where('jenjang', 'Pimpinan Tinggi Pratama')
                ->exists();
            if ($existingPratama) {
                return back()->withInput()->with('error', 'UNOR ini sudah memiliki Jabatan Pimpinan Tinggi Pratama (Kepala OPD). Setiap UNOR hanya boleh memiliki satu Kepala OPD.');
            }
        }

        if ($validated['jenis_jabatan'] === 'Pelaksana') $validated['jenjang'] = 'Pelaksana';

        // Auto-generate kode_jabatan
        $opd = Unor::findOrFail($validated['opd_id']);
        $validated['kode_jabatan'] = app(KodeJabatanGenerator::class)->generate(
            $opd->kode_unor,
            $validated['jenis_jabatan']
        );

        $jabatan = Jabatan::create($validated);

        // Otomatis daftarkan ke SOTK
        \App\Models\Sotk::firstOrCreate([
            'unor_id' => $jabatan->opd_id,
            'jabatan_id' => $jabatan->id,
        ]);

        // Simpan kebutuhan ke tabel kebutuhan_pegawai
        $kebutuhan = $request->input('kebutuhan');
        if ($kebutuhan !== null && $kebutuhan !== '') {
            KebutuhanPegawai::updateOrCreate(
                ['unor_id' => $jabatan->opd_id, 'jabatan_id' => $jabatan->id, 'tahun' => null],
                ['jumlah' => (int) $kebutuhan]
            );
        }

        return redirect()->route('admin.jabatan.index')->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function edit(Jabatan $jabatan)
    {
        $opdList = Unor::orderBy('nama_unor')->pluck('nama_unor', 'id');

        // Load kebutuhan existing
        $kebutuhan = KebutuhanPegawai::where('unor_id', $jabatan->opd_id)
            ->where('jabatan_id', $jabatan->id)
            ->whereNull('tahun')
            ->value('jumlah');

        return view('admin.jabatan.edit', [
            'jabatan' => $jabatan,
            'kebutuhan' => $kebutuhan,
            'opdList' => $opdList,
            'indukList' => collect(),
            'indukByOpd' => [],
            'jenisJabatanList' => JenisJabatan::labels(),
            'jenjangOptions' => [
                'Struktural' => Jenjang::forJenisJabatan('Struktural'),
                'Fungsional' => Jenjang::forJenisJabatan('Fungsional'),
                'Pelaksana' => Jenjang::forJenisJabatan('Pelaksana'),
            ],
            'masterJabatanData' => $this->buildMasterJabatanData(),
        ]);
    }

    public function update(Request $request, Jabatan $jabatan)
    {
        $validated = $request->validate([
            'nama_jabatan' => 'required|string|max:255',
            'jenis_jabatan' => 'required|in:Struktural,Fungsional,Pelaksana',
            'kelas_jabatan' => 'required|integer|min:1',
            'jenjang' => 'nullable|string|max:255',
            'kebutuhan' => 'nullable|integer|min:0',
            'opd_id' => 'required|exists:unor,id',
        ]);
        unset($validated['kebutuhan']);

        // Validasi: nama_jabatan harus ada di master_jabatan
        $namaUntukCek = explode(' - ', $validated['nama_jabatan'])[0];
        $existsInMaster = ReferensiJabatan::where('nama_jabatan', $namaUntukCek)
            ->where('jenis_jabatan', $validated['jenis_jabatan'])
            ->whereNull('parent_id')
            ->exists();

        if (!$existsInMaster) {
            return back()->withInput()->with('error', 'Nama jabatan "' . $namaUntukCek . '" tidak ditemukan di Referensi Jabatan. Silakan pilih dari daftar yang tersedia.');
        }

        // Validasi: satu UNOR hanya boleh memiliki satu JPTP (Kepala OPD)
        $isPratama = $validated['jenis_jabatan'] === 'Struktural' && ($validated['jenjang'] ?? '') === 'Pimpinan Tinggi Pratama';
        if ($isPratama) {
            $existingPratama = Jabatan::where('opd_id', $validated['opd_id'])
                ->where('jenis_jabatan', 'Struktural')
                ->where('jenjang', 'Pimpinan Tinggi Pratama')
                ->where('id', '!=', $jabatan->id)
                ->exists();
            if ($existingPratama) {
                return back()->withInput()->with('error', 'UNOR ini sudah memiliki Jabatan Pimpinan Tinggi Pratama (Kepala OPD). Setiap UNOR hanya boleh memiliki satu Kepala OPD.');
            }
        }

        if ($validated['jenis_jabatan'] === 'Pelaksana') $validated['jenjang'] = 'Pelaksana';

        // Pastikan kode_jabatan tidak dapat diubah
        unset($validated['kode_jabatan']);

        $jabatan->update($validated);

        // Sync SOTK jika OPD berubah
        if ((int) $jabatan->opd_id !== (int) $validated['opd_id']) {
            $jabatan->sotkEntries()->delete();
            \App\Models\Sotk::create([
                'unor_id' => $validated['opd_id'],
                'jabatan_id' => $jabatan->id,
            ]);
        }

        // Update kebutuhan
        $kebutuhan = $request->input('kebutuhan');
        if ($kebutuhan !== null && $kebutuhan !== '') {
            KebutuhanPegawai::updateOrCreate(
                ['unor_id' => $jabatan->opd_id, 'jabatan_id' => $jabatan->id, 'tahun' => null],
                ['jumlah' => (int) $kebutuhan]
            );
        }

        return redirect()->route('admin.jabatan.index')->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Jabatan $jabatan)
    {
        if ($jabatan->pegawai()->exists()) return back()->with('error', 'Jabatan tidak dapat dihapus karena masih memiliki pegawai.');

        // Hapus data terkait dulu (FK constraint), baru hapus jabatan
        \App\Models\PenempatanPegawai::where('jabatan_id', $jabatan->id)->delete();
        $jabatan->sotkEntries()->delete();
        $jabatan->kebutuhanPegawai()->delete();
        $jabatan->delete();

        return redirect()->route('admin.jabatan.index')->with('success', 'Jabatan berhasil dihapus.');
    }

    /**
     * Build referensi jabatan data: root entries (parent_id=null) with their children.
     * Returns { Struktural: [{id, nama}], Fungsional: [{id, nama, children}], Pelaksana: [{id, nama}] }
     */
    private function buildMasterJabatanData(): array
    {
        $result = [];
        foreach (['Struktural', 'Fungsional', 'Pelaksana'] as $jenis) {
            $all = ReferensiJabatan::where('jenis_jabatan', $jenis)
                ->orderBy('parent_id')
                ->orderBy('nama_jabatan')
                ->get();

            $childrenMap = [];
            $roots = [];
            foreach ($all as $item) {
                if ($item->parent_id) {
                    $childrenMap[$item->parent_id][] = ['id' => $item->id, 'nama' => $item->nama_jabatan];
                } else {
                    $roots[] = $item;
                }
            }

            $tree = [];
            foreach ($roots as $root) {
                $node = ['id' => $root->id, 'nama' => $root->nama_jabatan];
                if (isset($childrenMap[$root->id])) {
                    $node['children'] = $childrenMap[$root->id];
                }
                $tree[] = $node;
            }
            $result[$jenis] = $tree;
        }

        return $result;
    }

    public function getByOpd(Request $request)
    {
        $request->validate(['opd_id' => 'required|exists:unor,id']);
        $jabatanList = Jabatan::withCount('pegawai')->where('opd_id', $request->opd_id)->orderBy('nama_jabatan')->get()
            ->map(fn($j) => [
                'id' => $j->id,
                'nama' => $j->nama_jabatan,
                'jenis_jabatan' => $j->jenis_jabatan,
                'jenjang' => $j->jenjang,
                'pegawai_count' => $j->pegawai_count,
            ]);
        return response()->json(['success' => true, 'data' => $jabatanList]);
    }
}
