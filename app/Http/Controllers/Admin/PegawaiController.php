<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
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
        $query = Pegawai::query()->with(['jabatan', 'penempatanAktif.unor.parent']);
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

        // Jenjang otomatis dari jabatan yang dipilih
        $jabatan = null;
        if (!empty($validated['jabatan_id'])) {
            $jabatan = Jabatan::with('sotkEntries')->find($validated['jabatan_id']);
            if ($jabatan) {
                $validated['jenjang'] = $jabatan->jenjang;
            }
        } else {
            $validated['jenjang'] = null;
        }

        // Resolve UNOR dari SOTK jabatan
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
        $pegawai->load('penempatanAktif.unor');
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

        return view('admin.pegawai.edit', [
            'pegawai' => $pegawai,
            'opdList' => $opdList,
            'currentIndukId' => $currentIndukId,
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
            'induk_id' => 'nullable|required_with:jabatan_id|exists:unor,id',
            'jabatan_id' => 'nullable|exists:jabatan,id',
        ]);

        // Jenjang otomatis dari jabatan yang dipilih
        $jabatan = null;
        if (!empty($validated['jabatan_id'])) {
            $jabatan = Jabatan::with('sotkEntries')->find($validated['jabatan_id']);
            if ($jabatan) {
                $validated['jenjang'] = $jabatan->jenjang;
            }
        } else {
            $validated['jenjang'] = null;
        }

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
}
