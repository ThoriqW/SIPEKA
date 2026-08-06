<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\MasterTugasTambahan;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\TugasTambahanPegawai;
use App\Models\Unor;
use App\Enums\GolonganPangkat;
use App\Enums\JenisKepegawaian;
use App\Enums\Pendidikan;
use App\Services\NipParser;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    public function index(Request $request)
    {
        $query = Pegawai::query()->with(['jabatan', 'penempatanAktif.unor.parent', 'tugasTambahan.tugasTambahan']);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%");
            });
        }
        if ($request->filled('unor_id')) {
            $indukId = $request->unor_id;
            $allUnor = Unor::whereNotNull('parent_id')->get()->keyBy('id');
            $descendantIds = $this->collectDescendants($indukId, $allUnor);
            $targetIds = array_merge([$indukId], $descendantIds);
            $query->whereHas('penempatanAktif', fn($q) => $q->whereIn('unor_id', $targetIds));
        }
        $pegawaiList = $query->orderBy('nama')->paginate(15)->withQueryString();

        // Filter Unor Induk saja (level OPD, bukan root dan bukan sub-unit)
        $pemkot = Unor::whereNull('parent_id')->first();
        $opdList = Unor::where('parent_id', $pemkot?->id)
            ->orderBy('nama_unor')->pluck('nama_unor', 'id');
        return view('admin.pegawai.index', compact('pegawaiList', 'opdList', 'pemkot'));
    }

    public function create()
    {
        $pemkot = Unor::whereNull('parent_id')->first();
        $opdList = Unor::where('parent_id', $pemkot?->id)
            ->orderBy('nama_unor')->pluck('nama_unor', 'id');
        return view('admin.pegawai.create', [
            'opdList' => $opdList,
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
            'induk_id' => 'nullable|required_with:jabatan_id|exists:unor,id',
            'jabatan_id' => 'nullable|exists:jabatan,id',
        ]);

        // Resolve UNOR dari SOTK jabatan
        $jabatan = null;
        if (!empty($validated['jabatan_id'])) {
            $jabatan = Jabatan::with('sotkEntries')->find($validated['jabatan_id']);
        }
        $unorId = $jabatan?->sotkEntries->first()?->unor_id ?? null;

        $pegawai = Pegawai::create($validated);

        // Buat penempatan otomatis
        if ($unorId && $pegawai->jabatan_id) {
            PenempatanPegawai::create([
                'pegawai_id' => $pegawai->id,
                'unor_id' => $unorId,
                'jabatan_id' => $pegawai->jabatan_id,
                'tanggal_mulai' => now()->toDateString(),
                'is_active' => true,
            ]);
        }

        return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function edit(Pegawai $pegawai)
    {
        $pegawai->load(['penempatanAktif.unor', 'tugasTambahan.tugasTambahan', 'tugasTambahan.unor']);
        $pemkot = Unor::whereNull('parent_id')->first();
        $opdList = Unor::where('parent_id', $pemkot?->id)
            ->orderBy('nama_unor')->pluck('nama_unor', 'id');

        // Komputasi Unor Induk dari penempatan (walk-up)
        $currentIndukId = null;
        if ($pegawai->penempatanAktif?->unor && $pemkot) {
            $induk = $pegawai->penempatanAktif->unor;
            while ($induk && $induk->parent_id !== $pemkot->id) {
                $induk = $induk->parent;
            }
            $currentIndukId = $induk?->id;
        }

        $tugasTambahanList = MasterTugasTambahan::orderBy('nama_tugas')->get();

        // Semua UNOR (kecuali root) dengan breadcrumb — untuk dropdown Tugas Tambahan
        $allUnor = Unor::with('parent')->get()->keyBy('id');
        $unorList = $allUnor
            ->reject(fn($u) => $u->parent_id === null) // exclude root
            ->mapWithKeys(fn($u) => [$u->id => $this->buildBreadcrumb($u, $allUnor)])
            ->sort()
            ->all();

        return view('admin.pegawai.edit', [
            'pegawai' => $pegawai,
            'opdList' => $opdList,
            'currentIndukId' => $currentIndukId,
            'golonganPangkatList' => GolonganPangkat::labels(),
            'pppkGolonganList' => GolonganPangkat::pppkLabels(),
            'jenisKepegawaianList' => JenisKepegawaian::labels(),
            'pendidikanList' => Pendidikan::labels(),
            'tugasTambahanList' => $tugasTambahanList,
            'unorList' => $unorList,
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
            'induk_id' => 'nullable|required_with:jabatan_id|exists:unor,id',
            'jabatan_id' => 'nullable|exists:jabatan,id',
        ]);

        // Deteksi perubahan jabatan
        $jabatanChanged = (int) ($validated['jabatan_id'] ?? 0) !== (int) ($pegawai->jabatan_id ?? 0);

        $pegawai->update($validated);

        // Jika jabatan berubah, nonaktifkan penempatan lama & buat baru
        if ($jabatanChanged) {
            // Nonaktifkan penempatan aktif sebelumnya
            PenempatanPegawai::where('pegawai_id', $pegawai->id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'tanggal_selesai' => now()->toDateString()]);

            // Resolve UNOR dari SOTK jabatan baru
            $jabatan = Jabatan::with('sotkEntries')->find($validated['jabatan_id']);
            $unorId = $jabatan?->sotkEntries->first()?->unor_id ?? null;

            // Buat penempatan baru
            if ($unorId && $pegawai->jabatan_id) {
                PenempatanPegawai::create([
                    'pegawai_id' => $pegawai->id,
                    'unor_id' => $unorId,
                    'jabatan_id' => $pegawai->jabatan_id,
                    'tanggal_mulai' => now()->toDateString(),
                    'is_active' => true,
                ]);
            }
        }

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
     * Tambah tugas tambahan ke pegawai.
     */
    public function storeTugasTambahan(Request $request, Pegawai $pegawai)
    {
        $validated = $request->validate([
            'tugas_tambahan_id' => 'required|exists:master_tugas_tambahan,id',
            'unor_id'           => 'required|exists:unor,id',
            'tanggal_mulai'     => 'required|date',
            'tanggal_selesai'   => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        // Cek A: Pegawai sudah punya tugas tambahan jenis ini di UNOR lain?
        $konflikPegawai = TugasTambahanPegawai::where('pegawai_id', $pegawai->id)
            ->where('tugas_tambahan_id', $validated['tugas_tambahan_id'])
            ->where('is_active', true)
            ->where('unor_id', '!=', $validated['unor_id'])
            ->with('unor', 'tugasTambahan')
            ->first();

        if ($konflikPegawai) {
            $namaTugas = $konflikPegawai->tugasTambahan->nama_tugas ?? 'tugas ini';
            $namaUnor = $konflikPegawai->unor->nama_unor ?? 'UNOR lain';
            return redirect()->route('admin.pegawai.edit', $pegawai)
                ->with('error', "Pegawai ini sudah memiliki tugas \"{$namaTugas}\" di {$namaUnor}. Tidak dapat menambahkan tugas yang sama di UNOR berbeda.");
        }

        // Cek B: UNOR ini sudah punya tugas tambahan jenis ini oleh pegawai lain?
        $konflikUnor = TugasTambahanPegawai::where('tugas_tambahan_id', $validated['tugas_tambahan_id'])
            ->where('unor_id', $validated['unor_id'])
            ->where('is_active', true)
            ->where('pegawai_id', '!=', $pegawai->id)
            ->with('pegawai', 'tugasTambahan')
            ->first();

        if ($konflikUnor) {
            $namaTugas = $konflikUnor->tugasTambahan->nama_tugas ?? 'tugas ini';
            $namaPegawai = $konflikUnor->pegawai->nama ?? 'pegawai lain';
            return redirect()->route('admin.pegawai.edit', $pegawai)
                ->with('error', "UNOR ini sudah memiliki tugas \"{$namaTugas}\" oleh {$namaPegawai}. Satu UNOR hanya boleh memiliki satu pemegang tugas tambahan dengan jenis yang sama.");
        }

        // Jika tugas + UNOR sama persis sudah aktif → nonaktifkan yang lama (perbarui)
        $existing = TugasTambahanPegawai::where('pegawai_id', $pegawai->id)
            ->where('tugas_tambahan_id', $validated['tugas_tambahan_id'])
            ->where('unor_id', $validated['unor_id'])
            ->where('is_active', true)
            ->first();

        if ($existing) {
            $existing->update([
                'is_active'       => false,
                'tanggal_selesai' => $validated['tanggal_mulai'],
            ]);
        }

        TugasTambahanPegawai::create([
            'pegawai_id'        => $pegawai->id,
            'tugas_tambahan_id' => $validated['tugas_tambahan_id'],
            'unor_id'           => $validated['unor_id'],
            'tanggal_mulai'     => $validated['tanggal_mulai'],
            'tanggal_selesai'   => $validated['tanggal_selesai'] ?? null,
            'is_active'         => true,
        ]);

        return redirect()->route('admin.pegawai.edit', $pegawai)
            ->with('success', 'Tugas Tambahan berhasil ditambahkan.');
    }

    /**
     * Cabut tugas tambahan dari pegawai (soft — set is_active=false).
     * Record tetap tersimpan sebagai riwayat.
     * Tanggal selesai hanya di-set ke now() jika memang belum di-set atau sudah lewat.
     */
    public function cabutTugasTambahan(Pegawai $pegawai, TugasTambahanPegawai $tugasTambahan)
    {
        if ($tugasTambahan->pegawai_id !== $pegawai->id) {
            abort(404);
        }

        $data = ['is_active' => false];

        // Jangan overwrite tanggal_selesai jika sudah di-set dan belum lewat
        if ($tugasTambahan->tanggal_selesai === null || $tugasTambahan->tanggal_selesai->isPast()) {
            $data['tanggal_selesai'] = now()->toDateString();
        }

        $tugasTambahan->update($data);

        return redirect()->route('admin.pegawai.edit', $pegawai)
            ->with('success', 'Tugas Tambahan berhasil dicabut.');
    }

    /**
     * Hapus permanen record tugas tambahan dari database.
     * Hanya untuk record yang salah input atau belum pernah aktif.
     */
    public function destroyTugasTambahan(Pegawai $pegawai, TugasTambahanPegawai $tugasTambahan)
    {
        if ($tugasTambahan->pegawai_id !== $pegawai->id) {
            abort(404);
        }

        $tugasTambahan->delete();

        return redirect()->route('admin.pegawai.edit', $pegawai)
            ->with('success', 'Tugas Tambahan berhasil dihapus.');
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
     * Build breadcrumb path untuk UNOR — berhenti sebelum root.
     * Contoh: "Dinas Kesehatan » Bidang Pelayanan Kesehatan"
     */
    private function buildBreadcrumb(Unor $unor, $allUnor): string
    {
        $parts = [$unor->nama_unor];
        $cursor = $unor;
        while ($cursor->parent_id) {
            $parent = $allUnor->get($cursor->parent_id);
            if (!$parent || !$parent->parent_id) break; // stop di root
            array_unshift($parts, $parent->nama_unor);
            $cursor = $parent;
        }
        return implode(' » ', $parts);
    }
}
