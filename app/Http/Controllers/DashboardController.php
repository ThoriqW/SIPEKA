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
}
