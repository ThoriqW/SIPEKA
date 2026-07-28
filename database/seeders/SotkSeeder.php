<?php

namespace Database\Seeders;

use App\Models\Sotk;
use App\Models\Jabatan;
use App\Models\Unor;
use Illuminate\Database\Seeder;

class SotkSeeder extends Seeder
{
    public function run(): void
    {
        $dikbud = Unor::where('kode_unor', 'DIKBUD')->first();
        $dinkes = Unor::where('kode_unor', 'DINKES')->first();

        // Ambil semua jabatan DIKBUD
        $jabatanDikbud = Jabatan::where('opd_id', $dikbud->id)->pluck('id', 'kode_jabatan');
        foreach ($jabatanDikbud as $jabatanId) {
            Sotk::create(['unor_id' => $dikbud->id, 'jabatan_id' => $jabatanId]);
        }

        // Ambil semua jabatan DINKES
        $jabatanDinkes = Jabatan::where('opd_id', $dinkes->id)->pluck('id', 'kode_jabatan');
        foreach ($jabatanDinkes as $jabatanId) {
            Sotk::create(['unor_id' => $dinkes->id, 'jabatan_id' => $jabatanId]);
        }

        // Juga assign jabatan ke Pemkot (level 0) untuk JPTP
        $pemkot = Unor::where('kode_unor', 'PEMKOT')->first();
        $kepalaDikbud = Jabatan::where('kode_jabatan', 'DIKBUD-001')->first();
        $kepalaDinkes = Jabatan::where('kode_jabatan', 'DINKES-001')->first();

        // JPTP juga bagian dari UNOR induk (Pemkot)
        Sotk::create(['unor_id' => $pemkot->id, 'jabatan_id' => $kepalaDikbud->id]);
        Sotk::create(['unor_id' => $pemkot->id, 'jabatan_id' => $kepalaDinkes->id]);
    }
}
