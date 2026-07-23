<?php

namespace App\Http\Controllers;

use App\Models\JabatanAsn;
use App\Models\NodeOrganisasi;
use App\Models\Opd;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $totalPns = Pegawai::where('jenis_kepegawaian', 'PNS')->count();
        $totalPppk = Pegawai::where('jenis_kepegawaian', 'PPPK')->count();
        $totalOpd = Opd::count();
        $totalPegawai = Pegawai::count();

        // Total kebutuhan = jumlah POSISI di struktur organisasi
        $totalKebutuhan = NodeOrganisasi::posisi()->count();

        // Pegawai per jenis_jabatan (dari jabatan_asn)
        $pegawaiPerJenisJenjang = Pegawai::join('jabatan_asn', 'pegawai.jabatan_asn_id', '=', 'jabatan_asn.id')
            ->selectRaw("jabatan_asn.jenis_jabatan, jabatan_asn.jenjang, COUNT(*) as total")
            ->groupBy('jabatan_asn.jenis_jabatan', 'jabatan_asn.jenjang')
            ->orderBy('jabatan_asn.jenis_jabatan')
            ->orderByRaw("FIELD(jabatan_asn.jenjang, 'Pimpinan Tinggi Pratama', 'Administrator', 'Pengawas', 'Ahli Utama', 'Ahli Madya', 'Ahli Muda', 'Ahli Pertama', 'Keterampilan - Penyelia', 'Keterampilan - Mahir', 'Keterampilan - Terampil', 'Keterampilan - Pemula', 'Pelaksana')")
            ->get()
            ->groupBy('jenis_jabatan');

        // Kategori Fungsional: grouping dari jabatan_asn
        $pegawaiFungsionalPerGroup = Pegawai::join('jabatan_asn', 'pegawai.jabatan_asn_id', '=', 'jabatan_asn.id')
            ->where('jabatan_asn.jenis_jabatan', 'Fungsional')
            ->selectRaw("
                CASE
                    WHEN jabatan_asn.nama_jabatan_asn LIKE '%Guru%' THEN 'Guru'
                    WHEN jabatan_asn.nama_jabatan_asn REGEXP 'Dokter|Perawat|Bidan|Apoteker|Nutrisionis|Psikolog|Radiografer|Fisioterapis|Tenaga|Perekam|Pranata|Penata|Teknisi|Epidemiolog|Entomolog|Fisikawan|Pembimbing|Terapis|Okupasi|Asisten' THEN 'Kesehatan'
                    ELSE 'Non Guru & Non Kesehatan'
                END as kategori,
                COUNT(*) as total
            ")
            ->groupBy('kategori')
            ->orderBy('kategori')
            ->pluck('total', 'kategori');

        return view('dashboard', compact(
            'totalPns', 'totalPppk', 'totalOpd', 'totalPegawai',
            'totalKebutuhan', 'pegawaiPerJenisJenjang',
            'pegawaiFungsionalPerGroup'
        ));
    }
}
