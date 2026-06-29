<?php

namespace App\Http\Controllers;

use App\Models\Opd;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isBkd()) {
            return $this->bkdDashboard();
        }

        return $this->adminOpdDashboard($user);
    }

    private function bkdDashboard()
    {
        $totalPns = Pegawai::where('jenis_kepegawaian', 'PNS')->count();
        $totalPppk = Pegawai::where('jenis_kepegawaian', 'PPPK')->count();
        $totalOpd = Opd::count();
        $totalPegawai = Pegawai::count();

        $komposisi = [
            'PNS' => $totalPns,
            'PPPK' => $totalPppk,
        ];

        $opdList = Opd::withCount('pegawai')->orderBy('nama_opd')->get();

        return view('dashboard', compact(
            'totalPns', 'totalPppk', 'totalOpd', 'totalPegawai', 'komposisi', 'opdList'
        ));
    }

    private function adminOpdDashboard($user)
    {
        $opd = $user->opd;

        if (!$opd) {
            return view('dashboard', [
                'pegawaiCount' => 0, 'pnsCount' => 0, 'pppkCount' => 0,
                'jabatanCount' => 0, 'bezetting' => [], 'opd' => null,
                'totalPns' => 0, 'totalPppk' => 0, 'totalOpd' => 0,
                'totalPegawai' => 0, 'komposisi' => [], 'opdList' => [],
            ]);
        }

        $pegawaiCount = Pegawai::where('opd_id', $opd->id)->count();
        $pnsCount = Pegawai::where('opd_id', $opd->id)->where('jenis_kepegawaian', 'PNS')->count();
        $pppkCount = Pegawai::where('opd_id', $opd->id)->where('jenis_kepegawaian', 'PPPK')->count();
        $jabatanCount = $opd->jabatan()->count();

        $bezetting = $opd->jabatan()->withCount('pegawai')->get()->map(function ($jabatan) {
            return [
                'nama_jabatan' => $jabatan->nama_jabatan,
                'kebutuhan' => $jabatan->kebutuhan,
                'terisi' => $jabatan->pegawai_count,
                'kekurangan' => $jabatan->kebutuhan !== null ? max(0, $jabatan->kebutuhan - $jabatan->pegawai_count) : null,
            ];
        });

        return view('dashboard', compact(
            'pegawaiCount', 'pnsCount', 'pppkCount', 'jabatanCount', 'bezetting', 'opd'
        ) + [
            'totalPns' => $pnsCount, 'totalPppk' => $pppkCount, 'totalOpd' => 1,
            'totalPegawai' => $pegawaiCount, 'komposisi' => ['PNS' => $pnsCount, 'PPPK' => $pppkCount],
            'opdList' => $opd ? collect([$opd])->map(function($o) use ($pegawaiCount) { $o->pegawai_count = $pegawaiCount; return $o; }) : collect(),
        ]);
    }
}
