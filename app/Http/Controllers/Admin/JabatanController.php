<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\MasterJabatan;
use App\Models\Opd;
use App\Enums\Jenjang;
use App\Enums\JenisJabatan;
use App\Services\KodeJabatanGenerator;
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

        $jabatanList = $query->withCount('pegawai')->orderBy('nama_jabatan')->paginate(15)->withQueryString();
        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');
        return view('admin.jabatan.index', compact('jabatanList', 'opdList'));
    }

    public function create()
    {
        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');
        $indukQuery = Jabatan::with('opd')->where('jenis_jabatan', 'Struktural')->orderBy('nama_jabatan');
        $indukList = $indukQuery->get()->mapWithKeys(fn($j) => [$j->id => ($j->opd->nama_opd ?? '?') . ' › ' . $j->nama_jabatan]);

        // Data untuk Alpine.js: induk dikelompokkan per OPD
        $indukGrouped = $indukQuery->get()->groupBy('opd_id');
        $indukByOpd = [];
        foreach ($indukGrouped as $opdId => $items) {
            $indukByOpd[$opdId] = $items->map(fn($j) => [
                'id' => $j->id,
                'nama' => ($j->opd->nama_opd ?? '?') . ' › ' . $j->nama_jabatan,
            ])->values()->toArray();
        }

        return view('admin.jabatan.create', [
            'opdList' => $opdList,
            'indukList' => $indukList,
            'indukByOpd' => $indukByOpd,
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
            'kebutuhan' => 'required_if:jenis_jabatan,Fungsional,Pelaksana|integer|min:0|nullable',
            'opd_id' => 'required|exists:opd,id',
            'induk_jabatan_id' => 'nullable|exists:jabatan,id',
        ]);

        // Validasi: nama_jabatan harus ada di master_jabatan (cek parent-sub format)
        $parts = explode(' - ', $validated['nama_jabatan']);
        $namaParent = $parts[0];
        $namaSub = count($parts) > 1 ? $parts[1] : null;

        $parentMaster = MasterJabatan::where('nama_jabatan', $namaParent)
            ->where('jenis_jabatan', $validated['jenis_jabatan'])
            ->whereNull('parent_id')
            ->first();

        if (!$parentMaster) {
            return back()->withInput()->with('error', 'Nama jabatan "' . $namaParent . '" tidak ditemukan di Master Jabatan. Silakan pilih dari daftar yang tersedia.');
        }

        // Jika ada sub-jabatan, validasi bahwa sub adalah child valid dari parent
        if ($namaSub) {
            $subExists = MasterJabatan::where('nama_jabatan', $namaSub)
                ->where('jenis_jabatan', $validated['jenis_jabatan'])
                ->where('parent_id', $parentMaster->id)
                ->exists();

            if (!$subExists) {
                return back()->withInput()->with('error', 'Sub jabatan "' . $namaSub . '" tidak valid untuk "' . $namaParent . '". Silakan pilih dari daftar yang tersedia.');
            }
        }

        // Validasi: induk WAJIB kecuali untuk Pimpinan Tinggi Pratama (Kepala OPD)
        $isPratama = $validated['jenis_jabatan'] === 'Struktural' && ($validated['jenjang'] ?? '') === 'Pimpinan Tinggi Pratama';
        if (!$isPratama && empty($validated['induk_jabatan_id'])) {
            return back()->withInput()->with('error', 'Unit Organisasi (induk) wajib dipilih. Hanya jabatan Pimpinan Tinggi Pratama (Kepala OPD) yang boleh tanpa induk.');
        }

        // Validasi: satu OPD hanya boleh memiliki satu JPTP (Kepala OPD)
        if ($isPratama) {
            $existingPratama = Jabatan::where('opd_id', $validated['opd_id'])
                ->where('jenis_jabatan', 'Struktural')
                ->where('jenjang', 'Pimpinan Tinggi Pratama')
                ->exists();
            if ($existingPratama) {
                return back()->withInput()->with('error', 'OPD ini sudah memiliki Jabatan Pimpinan Tinggi Pratama (Kepala OPD). Setiap OPD hanya boleh memiliki satu Kepala OPD.');
            }
        }

        // Validasi: hanya jabatan Struktural yang boleh menjadi induk
        if (!empty($validated['induk_jabatan_id'])) {
            $induk = Jabatan::find($validated['induk_jabatan_id']);
            if ($induk && $induk->jenis_jabatan !== 'Struktural') {
                return back()->withInput()->with('error', 'Induk jabatan harus berjenis Struktural. Fungsional dan Pelaksana tidak dapat menjadi induk.');
            }
            // Validasi: induk harus satu OPD dengan jabatan (cross-OPD prevention)
            if ($induk && (int) $induk->opd_id !== (int) $validated['opd_id']) {
                return back()->withInput()->with('error', 'Induk jabatan harus berada di OPD yang sama dengan jabatan ini.');
            }
        }

        if ($validated['jenis_jabatan'] === 'Struktural') $validated['kebutuhan'] = 1;
        if ($validated['jenis_jabatan'] === 'Pelaksana') $validated['jenjang'] = 'Pelaksana';

        // Auto-generate kode_jabatan
        $opd = Opd::findOrFail($validated['opd_id']);
        $validated['kode_jabatan'] = app(KodeJabatanGenerator::class)->generate(
            $opd->kode_opd,
            $validated['jenis_jabatan']
        );

        Jabatan::create($validated);
        return redirect()->route('admin.jabatan.index')->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function edit(Jabatan $jabatan)
    {
        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');

        // Exclude diri sendiri dan semua turunan dari daftar induk
        $excludeIds = $this->getDescendantIds($jabatan);
        $excludeIds[] = $jabatan->id;

        $indukQuery = Jabatan::with('opd')
            ->whereNotIn('id', $excludeIds)
            ->where('jenis_jabatan', 'Struktural')
            ->orderBy('nama_jabatan');
        $indukList = $indukQuery->get()->mapWithKeys(fn($j) => [$j->id => ($j->opd->nama_opd ?? '?') . ' › ' . $j->nama_jabatan]);

        // Data untuk Alpine.js: induk dikelompokkan per OPD
        $indukGrouped = $indukQuery->get()->groupBy('opd_id');
        $indukByOpd = [];
        foreach ($indukGrouped as $opdId => $items) {
            $indukByOpd[$opdId] = $items->map(fn($j) => [
                'id' => $j->id,
                'nama' => ($j->opd->nama_opd ?? '?') . ' › ' . $j->nama_jabatan,
            ])->values()->toArray();
        }

        return view('admin.jabatan.edit', [
            'jabatan' => $jabatan,
            'opdList' => $opdList,
            'indukList' => $indukList,
            'indukByOpd' => $indukByOpd,
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
            'kebutuhan' => 'required_if:jenis_jabatan,Fungsional,Pelaksana|integer|min:0|nullable',
            'opd_id' => 'required|exists:opd,id',
            'induk_jabatan_id' => 'nullable|exists:jabatan,id',
        ]);
        // Validasi: nama_jabatan harus ada di master_jabatan
        $namaUntukCek = explode(' - ', $validated['nama_jabatan'])[0];
        $existsInMaster = MasterJabatan::where('nama_jabatan', $namaUntukCek)
            ->where('jenis_jabatan', $validated['jenis_jabatan'])
            ->whereNull('parent_id')
            ->exists();

        if (!$existsInMaster) {
            return back()->withInput()->with('error', 'Nama jabatan "' . $namaUntukCek . '" tidak ditemukan di Master Jabatan. Silakan pilih dari daftar yang tersedia.');
        }

        // Validasi: induk WAJIB kecuali untuk Pimpinan Tinggi Pratama (Kepala OPD)
        $isPratama = $validated['jenis_jabatan'] === 'Struktural' && ($validated['jenjang'] ?? '') === 'Pimpinan Tinggi Pratama';
        if (!$isPratama && empty($validated['induk_jabatan_id'])) {
            return back()->withInput()->with('error', 'Unit Organisasi (induk) wajib dipilih. Hanya jabatan Pimpinan Tinggi Pratama (Kepala OPD) yang boleh tanpa induk.');
        }

        // Validasi: satu OPD hanya boleh memiliki satu JPTP (Kepala OPD)
        if ($isPratama) {
            $existingPratama = Jabatan::where('opd_id', $validated['opd_id'])
                ->where('jenis_jabatan', 'Struktural')
                ->where('jenjang', 'Pimpinan Tinggi Pratama')
                ->where('id', '!=', $jabatan->id)
                ->exists();
            if ($existingPratama) {
                return back()->withInput()->with('error', 'OPD ini sudah memiliki Jabatan Pimpinan Tinggi Pratama (Kepala OPD). Setiap OPD hanya boleh memiliki satu Kepala OPD.');
            }
        }

        // Validasi: Struktural yang memiliki turunan tidak boleh diubah ke non-Struktural
        if ($jabatan->jenis_jabatan === 'Struktural'
            && $validated['jenis_jabatan'] !== 'Struktural'
            && $jabatan->anak()->exists()) {
            return back()->withInput()->with('error',
                'Jabatan ini tidak dapat diubah menjadi ' . $validated['jenis_jabatan']
                . ' karena masih memiliki ' . $jabatan->anak()->count()
                . ' jabatan turunan. Hanya jabatan Struktural yang boleh menjadi induk. '
                . 'Pindahkan atau hapus turunannya terlebih dahulu.');
        }

        // Validasi: hanya jabatan Struktural yang boleh menjadi induk
        if (!empty($validated['induk_jabatan_id'])) {
            $induk = Jabatan::find($validated['induk_jabatan_id']);
            if ($induk && $induk->jenis_jabatan !== 'Struktural') {
                return back()->withInput()->with('error', 'Induk jabatan harus berjenis Struktural. Fungsional dan Pelaksana tidak dapat menjadi induk.');
            }
            // Validasi: induk harus satu OPD dengan jabatan (cross-OPD prevention)
            if ($induk && (int) $induk->opd_id !== (int) $validated['opd_id']) {
                return back()->withInput()->with('error', 'Induk jabatan harus berada di OPD yang sama dengan jabatan ini.');
            }
            // Validasi: induk tidak boleh turunan dari jabatan ini (circular reference)
            if ($induk && in_array((int) $validated['induk_jabatan_id'], $this->getDescendantIds($jabatan), true)) {
                return back()->withInput()->with('error', 'Induk jabatan tidak valid karena merupakan turunan dari jabatan ini.');
            }
        }

        if ($validated['jenis_jabatan'] === 'Struktural') $validated['kebutuhan'] = 1;
        if ($validated['jenis_jabatan'] === 'Pelaksana') $validated['jenjang'] = 'Pelaksana';

        // Pastikan kode_jabatan tidak dapat diubah
        unset($validated['kode_jabatan']);

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

    /**
     * Dapatkan semua ID turunan (anak, cucu, dst) dari suatu jabatan.
     * Menggunakan BFS untuk menghindari rekursi. Aman karena max level = 4.
     *
     * @return int[]
     */
    private function getDescendantIds(Jabatan $jabatan): array
    {
        $ids = [];
        $queue = $jabatan->anak()->pluck('id')->toArray();

        while (!empty($queue)) {
            $ids = array_merge($ids, $queue);
            $queue = Jabatan::whereIn('induk_jabatan_id', $queue)->pluck('id')->toArray();
        }

        return $ids;
    }

    /**
     * Build master jabatan data: root entries (parent_id=null) with their children.
     * Returns { Struktural: [{id, nama}], Fungsional: [{id, nama, children}], Pelaksana: [{id, nama}] }
     */
    private function buildMasterJabatanData(): array
    {
        $result = [];
        foreach (['Struktural', 'Fungsional', 'Pelaksana'] as $jenis) {
            $all = MasterJabatan::where('jenis_jabatan', $jenis)
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
