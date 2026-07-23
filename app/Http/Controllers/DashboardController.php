<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\JabatanAsn;
use App\Models\MasterJabatan;
use App\Models\NodeOrganisasi;
use App\Models\Opd;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $totalPns = Pegawai::where('jenis_kepegawaian', 'PNS')->count();
        $totalPppk = Pegawai::where('jenis_kepegawaian', 'PPPK')->count();
        $totalOpd = Opd::count();
        $totalPegawai = Pegawai::count();

        // Total kebutuhan: dari node_organisasi (model baru) atau jabatan (model lama)
        $useNewModel = NodeOrganisasi::exists();

        if ($useNewModel) {
            // Model baru: kebutuhan = jumlah POSISI
            $totalKebutuhan = NodeOrganisasi::posisi()->count();

            // Pegawai per jenis_jabatan (dari jabatan_asn)
            $pegawaiPerJenisJenjang = Pegawai::join('jabatan_asn', 'pegawai.jabatan_asn_id', '=', 'jabatan_asn.id')
                ->selectRaw("jabatan_asn.jenis_jabatan, jabatan_asn.jenjang, COUNT(*) as total")
                ->groupBy('jabatan_asn.jenis_jabatan', 'jabatan_asn.jenjang')
                ->orderBy('jabatan_asn.jenis_jabatan')
                ->orderByRaw("FIELD(jabatan_asn.jenjang, 'Pimpinan Tinggi Pratama', 'Administrator', 'Pengawas', 'Ahli Utama', 'Ahli Madya', 'Ahli Muda', 'Ahli Pertama', 'Keterampilan - Penyelia', 'Keterampilan - Mahir', 'Keterampilan - Terampil', 'Keterampilan - Pemula', 'Pelaksana')")
                ->get()
                ->groupBy('jenis_jabatan');
        } else {
            // Model lama: fallback
            $totalKebutuhan = Jabatan::sum('kebutuhan');

            $pegawaiPerJenisJenjang = Pegawai::join('jabatan', 'pegawai.jabatan_id', '=', 'jabatan.id')
                ->selectRaw("jabatan.jenis_jabatan, jabatan.jenjang, COUNT(*) as total")
                ->groupBy('jabatan.jenis_jabatan', 'jabatan.jenjang')
                ->orderBy('jabatan.jenis_jabatan')
                ->orderByRaw("FIELD(jabatan.jenjang, 'Pimpinan Tinggi Pratama', 'Administrator', 'Pengawas', 'Ahli Utama', 'Ahli Madya', 'Ahli Muda', 'Ahli Pertama', 'Keterampilan - Penyelia', 'Keterampilan - Mahir', 'Keterampilan - Terampil', 'Keterampilan - Pemula', 'Pelaksana')")
                ->get()
                ->groupBy('jenis_jabatan');
        }

        // Kategori Fungsional: gunakan data dari jabatan_asn (model baru) atau master_jabatan (model lama)
        if ($useNewModel) {
            // Model baru: grouping dari jabatan_asn.nama_jabatan_asn
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
        } else {
            // Legacy logic with master_jabatan
            $guru = MasterJabatan::where('nama_jabatan', 'Guru')
                ->where('jenis_jabatan', 'Fungsional')->whereNull('parent_id')->first();
            $dokter = MasterJabatan::where('nama_jabatan', 'Dokter')
                ->where('jenis_jabatan', 'Fungsional')->whereNull('parent_id')->first();

            $namaGuru = ['Guru'];
            if ($guru) {
                $namaGuru = array_merge($namaGuru, MasterJabatan::where('parent_id', $guru->id)->pluck('nama_jabatan')->toArray());
            }

            $nakesNames = ['Administrator Kesehatan', 'Apoteker', 'Asisten Apoteker', 'Asisten Penata Anestesi',
                'Bidan', 'Dokter', 'Dokter Gigi', 'Entomolog Kesehatan', 'Epidemiolog Kesehatan',
                'Fisikawan Medis', 'Fisioterapis', 'Nutrisionis', 'Pembimbing Kesehatan Kerja',
                'Penata Anestesi', 'Perawat', 'Perekam Medis', 'Pranata Laboratorium Kesehatan',
                'Psikolog Klinis', 'Radiografer', 'Teknisi Elektromedis', 'Teknisi Transfusi Darah',
                'Tenaga Promosi Kesehatan dan Ilmu Perilaku', 'Tenaga Sanitasi Lingkungan',
                'Terapis Gigi dan Mulut', 'Okupasi Terapis', 'Terapis Wicara'];
            if ($dokter) {
                $nakesNames = array_merge($nakesNames, MasterJabatan::where('parent_id', $dokter->id)->pluck('nama_jabatan')->toArray());
            }

            $guruPlaceholders = implode(',', array_fill(0, count($namaGuru), '?'));
            $nakesPlaceholders = implode(',', array_fill(0, count($nakesNames), '?'));
            $allBindings = array_merge($namaGuru, $nakesNames);

            $pegawaiFungsionalPerGroup = Pegawai::join('jabatan', 'pegawai.jabatan_id', '=', 'jabatan.id')
                ->where('jabatan.jenis_jabatan', 'Fungsional')
                ->selectRaw("
                    CASE
                        WHEN SUBSTRING_INDEX(jabatan.nama_jabatan, ' - ', 1) IN ({$guruPlaceholders}) THEN 'Guru'
                        WHEN SUBSTRING_INDEX(jabatan.nama_jabatan, ' - ', 1) IN ({$nakesPlaceholders}) THEN 'Kesehatan'
                        ELSE 'Non Guru & Non Kesehatan'
                    END as kategori,
                    COUNT(*) as total
                ", $allBindings)
                ->groupBy('kategori')
                ->orderBy('kategori')
                ->pluck('total', 'kategori');
        }

        return view('dashboard', compact(
            'totalPns', 'totalPppk', 'totalOpd', 'totalPegawai',
            'totalKebutuhan', 'pegawaiPerJenisJenjang',
            'pegawaiFungsionalPerGroup'
        ));
    }
}
