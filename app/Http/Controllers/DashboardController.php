<?php

namespace App\Http\Controllers;

use App\Models\KebutuhanPegawai;
use App\Models\Pegawai;
use App\Models\ReferensiJabatan;
use App\Models\Unor;

class DashboardController extends Controller
{
    public function index()
    {
        $totalPns = Pegawai::where('jenis_kepegawaian', 'PNS')->count();
        $totalPppk = Pegawai::where('jenis_kepegawaian', 'PPPK')->count();
        $totalPegawai = Pegawai::count();

        // Total Perangkat Daerah = UNOR level 1 (anak langsung Pemkot)
        $pemkot = Unor::whereNull('parent_id')->first();
        $totalOpd = $pemkot
            ? Unor::where('parent_id', $pemkot->id)->count()
            : Unor::count();

        // Total kebutuhan seluruh jabatan dari tabel kebutuhan_pegawai
        $totalKebutuhan = KebutuhanPegawai::whereNull('tahun')->sum('jumlah');

        // Pegawai per jenis_jabatan dirinci per jenjang
        $pegawaiPerJenisJenjang = Pegawai::join('jabatan', 'pegawai.jabatan_id', '=', 'jabatan.id')
            ->selectRaw("jabatan.jenis_jabatan, jabatan.jenjang, COUNT(*) as total")
            ->groupBy('jabatan.jenis_jabatan', 'jabatan.jenjang')
            ->orderBy('jabatan.jenis_jabatan')
            ->orderByRaw("FIELD(jabatan.jenjang, 'Pimpinan Tinggi Pratama', 'Administrator', 'Pengawas', 'Ahli Utama', 'Ahli Madya', 'Ahli Muda', 'Ahli Pertama', 'Keterampilan - Penyelia', 'Keterampilan - Mahir', 'Keterampilan - Terampil', 'Keterampilan - Pemula', 'Pelaksana')")
            ->get()
            ->groupBy('jenis_jabatan');

        // Nama-nama jabatan per kelompok — dari master_jabatan (source of truth)
        $namaPerKelompok = ReferensiJabatan::whereNotNull('kelompok')
            ->selectRaw('kelompok, GROUP_CONCAT(DISTINCT nama_jabatan) as names')
            ->groupBy('kelompok')
            ->pluck('names', 'kelompok')
            ->map(fn($names) => explode(',', $names))
            ->all();

        // Pegawai Fungsional per kategori — proses di PHP
        $pegawaiFungsional = Pegawai::with('jabatan')
            ->whereHas('jabatan', fn($q) => $q->where('jenis_jabatan', 'Fungsional'))
            ->get();

        $counts = [
            'Tenaga Guru'               => 0,
            'Tenaga Kesehatan'           => 0,
            'Non Guru & Non Kesehatan'   => 0,
        ];

        foreach ($pegawaiFungsional as $p) {
            $parentName = explode(' - ', $p->jabatan->nama_jabatan)[0];

            $matched = false;
            foreach ($namaPerKelompok as $kelompok => $names) {
                if (in_array($parentName, $names, true)) {
                    $label = match ($kelompok) {
                        'Tenaga Guru'       => 'Tenaga Guru',
                        'Tenaga Kesehatan'   => 'Tenaga Kesehatan',
                        'Tenaga Teknis'     => 'Non Guru & Non Kesehatan',
                        default             => 'Non Guru & Non Kesehatan',
                    };
                    $counts[$label]++;
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $counts['Non Guru & Non Kesehatan']++;
            }
        }

        return view('dashboard', compact(
            'totalPns', 'totalPppk', 'totalOpd', 'totalPegawai',
            'totalKebutuhan', 'pegawaiPerJenisJenjang',
            'counts'
        ));
    }
}
