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
        $query = Jabatan::query()->with(['sotkEntries.unor.parent']);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_jabatan', 'like', "%{$search}%")->orWhere('kode_jabatan', 'like', "%{$search}%");
            });
        }
        if ($request->filled('opd_id')) {
            $indukId = $request->opd_id;
            $allUnor = Unor::whereNotNull('parent_id')->get()->keyBy('id');
            $descendantIds = $this->collectDescendants($indukId, $allUnor);
            $targetIds = array_merge([$indukId], $descendantIds);
            $query->whereHas('sotkEntries', fn($q) => $q->whereIn('unor_id', $targetIds));
        }

        $jabatanList = $query->withCount('pegawai')->orderBy('nama_jabatan')->paginate(15)->withQueryString();

        // Filter Unor Induk saja (level OPD, bukan root dan bukan sub-unit)
        $pemkot = Unor::whereNull('parent_id')->first();
        $opdList = Unor::where('parent_id', $pemkot?->id)
            ->orderBy('nama_unor')->pluck('nama_unor', 'id');

        return view('admin.jabatan.index', compact('jabatanList', 'opdList', 'pemkot'));
    }

    public function create()
    {
        $pemkot = Unor::whereNull('parent_id')->first();
        $indukList = Unor::where('parent_id', $pemkot?->id)
            ->orderBy('nama_unor')->pluck('nama_unor', 'id');

        // Tambahkan Pemkot di awal sebagai opsi Unor Induk (untuk JPTP)
        if ($pemkot) {
            $indukList = collect([$pemkot->id => $pemkot->nama_unor])->union($indukList);
        }

        $unorByInduk = $this->buildUnorByInduk($indukList, $pemkot);

        return view('admin.jabatan.create', [
            'indukList' => $indukList,
            'unorByInduk' => $unorByInduk,
            'jenisJabatanList' => JenisJabatan::labels(),
            'jenjangOptions' => [
                'Struktural' => Jenjang::forJenisJabatan('Struktural'),
                'Fungsional' => Jenjang::forJenisJabatan('Fungsional'),
                'Pelaksana' => Jenjang::forJenisJabatan('Pelaksana'),
            ],
            'referensiJabatanData' => $this->buildReferensiJabatanData(),
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

        $unorId = (int) $validated['opd_id'];
        unset($validated['opd_id'], $validated['kebutuhan']);

        // Validasi: nama_jabatan harus ada di referensi jabatan (cek parent-sub format)
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
            $subExists = ReferensiJabatan::where('sub_jabatan', $namaSub)
                ->where('parent_id', $parentMaster->id)
                ->exists();

            if (!$subExists) {
                return back()->withInput()->with('error', 'Sub jabatan "' . $namaSub . '" tidak valid untuk "' . $namaParent . '". Silakan pilih dari daftar yang tersedia.');
            }
        } else {
            // Validasi: jika induk memiliki sub-jabatan, sub wajib dipilih
            $hasSubs = ReferensiJabatan::where('parent_id', $parentMaster->id)->exists();
            if ($hasSubs) {
                return back()->withInput()->with('error', 'Sub jabatan wajib dipilih untuk "' . $namaParent . '". Silakan pilih sub jabatan yang sesuai.');
            }
        }

        // Validasi: jabatan Struktural tidak boleh duplikat dalam UNOR yang sama
        if ($validated['jenis_jabatan'] === 'Struktural') {
            $exists = Jabatan::whereHas('sotkEntries', fn($q) => $q->where('unor_id', $unorId))
                ->where('nama_jabatan', $validated['nama_jabatan'])
                ->where('jenjang', $validated['jenjang'] ?? '')
                ->exists();
            if ($exists) {
                return back()->withInput()->with('error', 'Jabatan Struktural "' . $validated['nama_jabatan'] . '" dengan jenjang "' . ($validated['jenjang'] ?? '') . '" sudah ada di UNOR ini.');
            }
        }

        if ($validated['jenis_jabatan'] === 'Pelaksana') $validated['jenjang'] = 'Pelaksana';

        // Auto-generate kode_jabatan
        $opd = Unor::findOrFail($unorId);
        $validated['kode_jabatan'] = app(KodeJabatanGenerator::class)->generate(
            $opd->kode_unor,
            $validated['jenis_jabatan']
        );

        $jabatan = Jabatan::create($validated);

        // Otomatis daftarkan ke SOTK
        \App\Models\Sotk::firstOrCreate([
            'unor_id' => $unorId,
            'jabatan_id' => $jabatan->id,
        ]);

        // Simpan kebutuhan ke tabel kebutuhan_pegawai
        $kebutuhan = $request->input('kebutuhan');
        if ($kebutuhan !== null && $kebutuhan !== '') {
            KebutuhanPegawai::updateOrCreate(
                ['unor_id' => $unorId, 'jabatan_id' => $jabatan->id, 'tahun' => null],
                ['jumlah' => (int) $kebutuhan]
            );
        }

        return redirect()->route('admin.jabatan.index')->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function edit(Jabatan $jabatan)
    {
        $jabatan->load('sotkEntries.unor');
        $pemkot = Unor::whereNull('parent_id')->first();
        $indukList = Unor::where('parent_id', $pemkot?->id)
            ->orderBy('nama_unor')->pluck('nama_unor', 'id');

        // Tambahkan Pemkot di awal sebagai opsi Unor Induk
        if ($pemkot) {
            $indukList = collect([$pemkot->id => $pemkot->nama_unor])->union($indukList);
        }

        $unorByInduk = $this->buildUnorByInduk($indukList, $pemkot);

        // Resolve primary UNOR dari SOTK
        $currentUnitId = $jabatan->sotkEntries->first()?->unor_id;

        // Load kebutuhan existing
        $kebutuhan = null;
        if ($currentUnitId) {
            $kebutuhan = KebutuhanPegawai::where('unor_id', $currentUnitId)
                ->where('jabatan_id', $jabatan->id)
                ->whereNull('tahun')
                ->value('jumlah');
        }

        // Determine current induk
        $currentOpd = $jabatan->sotkEntries->first()?->unor;
        $currentIndukId = null;
        if ($currentOpd) {
            // JPTP: induk adalah Pemkot
            if ($jabatan->jenis_jabatan === 'Struktural'
                && $jabatan->jenjang === 'Pimpinan Tinggi Pratama'
                && $pemkot) {
                $currentIndukId = $pemkot->id;
            } else {
                $currentIndukId = ($currentOpd->parent_id == $pemkot->id)
                    ? $currentOpd->id
                    : $currentOpd->parent_id;
            }
        }

        return view('admin.jabatan.edit', [
            'jabatan' => $jabatan,
            'kebutuhan' => $kebutuhan,
            'indukList' => $indukList,
            'unorByInduk' => $unorByInduk,
            'currentIndukId' => $currentIndukId,
            'currentUnitId' => $currentUnitId,
            'jenisJabatanList' => JenisJabatan::labels(),
            'jenjangOptions' => [
                'Struktural' => Jenjang::forJenisJabatan('Struktural'),
                'Fungsional' => Jenjang::forJenisJabatan('Fungsional'),
                'Pelaksana' => Jenjang::forJenisJabatan('Pelaksana'),
            ],
            'referensiJabatanData' => $this->buildReferensiJabatanData(),
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

        $unorId = (int) $validated['opd_id'];
        unset($validated['opd_id'], $validated['kebutuhan']);

        // Validasi: nama_jabatan harus ada di referensi jabatan
        $namaUntukCek = explode(' - ', $validated['nama_jabatan'])[0];
        $existsInMaster = ReferensiJabatan::where('nama_jabatan', $namaUntukCek)
            ->where('jenis_jabatan', $validated['jenis_jabatan'])
            ->whereNull('parent_id')
            ->exists();

        if (!$existsInMaster) {
            return back()->withInput()->with('error', 'Nama jabatan "' . $namaUntukCek . '" tidak ditemukan di Referensi Jabatan. Silakan pilih dari daftar yang tersedia.');
        }

        // Validasi sub-jabatan wajib (sama seperti store)
        $parts = explode(' - ', $validated['nama_jabatan']);
        $namaSub = count($parts) > 1 ? $parts[1] : null;
        $parentMaster = ReferensiJabatan::where('nama_jabatan', $namaUntukCek)
            ->where('jenis_jabatan', $validated['jenis_jabatan'])
            ->whereNull('parent_id')
            ->first();

        if ($namaSub) {
            $subExists = ReferensiJabatan::where('sub_jabatan', $namaSub)
                ->where('parent_id', $parentMaster->id)
                ->exists();
            if (!$subExists) {
                return back()->withInput()->with('error', 'Sub jabatan "' . $namaSub . '" tidak valid untuk "' . $namaUntukCek . '". Silakan pilih dari daftar yang tersedia.');
            }
        } elseif ($parentMaster) {
            $hasSubs = ReferensiJabatan::where('parent_id', $parentMaster->id)->exists();
            if ($hasSubs) {
                return back()->withInput()->with('error', 'Sub jabatan wajib dipilih untuk "' . $namaUntukCek . '". Silakan pilih sub jabatan yang sesuai.');
            }
        }

        // Validasi: jabatan Struktural tidak boleh duplikat dalam UNOR yang sama
        if ($validated['jenis_jabatan'] === 'Struktural') {
            $exists = Jabatan::whereHas('sotkEntries', fn($q) => $q->where('unor_id', $unorId))
                ->where('nama_jabatan', $validated['nama_jabatan'])
                ->where('jenjang', $validated['jenjang'] ?? '')
                ->where('id', '!=', $jabatan->id)
                ->exists();
            if ($exists) {
                return back()->withInput()->with('error', 'Jabatan Struktural "' . $validated['nama_jabatan'] . '" dengan jenjang "' . ($validated['jenjang'] ?? '') . '" sudah ada di UNOR ini.');
            }
        }

        if ($validated['jenis_jabatan'] === 'Pelaksana') $validated['jenjang'] = 'Pelaksana';

        // Pastikan kode_jabatan tidak dapat diubah
        unset($validated['kode_jabatan']);

        $jabatan->update($validated);

        // Sync SOTK jika UNOR berubah
        $oldUnorId = $jabatan->sotkEntries->first()?->unor_id;
        if ($oldUnorId && (int) $oldUnorId !== $unorId) {
            // Hapus SOTK entry lama untuk UNOR sebelumnya
            $jabatan->sotkEntries()->where('unor_id', $oldUnorId)->delete();
            \App\Models\Sotk::create([
                'unor_id' => $unorId,
                'jabatan_id' => $jabatan->id,
            ]);
        }

        // Update kebutuhan
        $kebutuhan = $request->input('kebutuhan');
        if ($kebutuhan !== null && $kebutuhan !== '') {
            KebutuhanPegawai::updateOrCreate(
                ['unor_id' => $unorId, 'jabatan_id' => $jabatan->id, 'tahun' => null],
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
     * Build mapping induk → semua turunan (induk + children + grandchildren ...) untuk dropdown bertingkat.
     */
    private function buildUnorByInduk($indukList, $pemkot = null): array
    {
        $allUnor = Unor::whereNotNull('parent_id')->orderBy('nama_unor')->get()->keyBy('id');
        $result = [];

        foreach ($indukList as $indukId => $indukNama) {
            // Pemkot sebagai induk: tampilkan semua OPD (anak langsung Pemkot)
            if ($pemkot && (int) $indukId === (int) $pemkot->id) {
                $items = [];
                foreach ($allUnor as $unor) {
                    if ((int) $unor->parent_id === (int) $pemkot->id) {
                        $items[] = ['id' => $unor->id, 'nama' => $unor->nama_unor];
                    }
                }
                $result[$indukId] = $items;
                continue;
            }

            $items = [['id' => $indukId, 'nama' => $indukNama]];
            $descendantIds = $this->collectDescendants($indukId, $allUnor);
            foreach ($descendantIds as $id) {
                $unor = $allUnor->get($id);
                if ($unor) {
                    $items[] = ['id' => $unor->id, 'nama' => $unor->nama_unor];
                }
            }
            $result[$indukId] = $items;
        }

        return $result;
    }

    /**
     * Rekursif kumpulkan semua ID turunan dari suatu Unor.
     */
    private function collectDescendants($parentId, $allUnor): array
    {
        $ids = [];
        foreach ($allUnor as $unor) {
            if ($unor->parent_id == $parentId) {
                $ids[] = $unor->id;
                $ids = array_merge($ids, $this->collectDescendants($unor->id, $allUnor));
            }
        }
        return $ids;
    }

    /**
     * Build referensi jabatan data: root entries (parent_id=null) with their children.
     * Returns { Struktural: [{id, nama}], Fungsional: [{id, nama, children}], Pelaksana: [{id, nama}] }
     */
    private function buildReferensiJabatanData(): array
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
                    $childrenMap[$item->parent_id][] = ['id' => $item->id, 'nama' => $item->sub_jabatan];
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

        $indukId = $request->opd_id;
        $allUnor = Unor::whereNotNull('parent_id')->get()->keyBy('id');
        $descendantIds = $this->collectDescendants($indukId, $allUnor);
        $targetIds = array_merge([$indukId], $descendantIds);

        $jabatanList = Jabatan::withCount('pegawai')
            ->whereHas('sotkEntries', fn($q) => $q->whereIn('unor_id', $targetIds))
            ->orderBy('nama_jabatan')->get()
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
