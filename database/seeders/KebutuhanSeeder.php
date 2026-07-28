<?php

namespace Database\Seeders;

use App\Models\KebutuhanPegawai;
use App\Models\Jabatan;
use App\Models\Unor;
use Illuminate\Database\Seeder;

class KebutuhanSeeder extends Seeder
{
    public function run(): void
    {
        $dikbud = Unor::where('kode_unor', 'DIKBUD')->first();
        $dinkes = Unor::where('kode_unor', 'DINKES')->first();

        // ── DIKBUD ──
        KebutuhanPegawai::create([
            'unor_id' => $dikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-005')->first()->id,
            'tahun' => null,
            'jumlah' => 3,
        ]);
        KebutuhanPegawai::create([
            'unor_id' => $dikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-006')->first()->id,
            'tahun' => null,
            'jumlah' => 10,
        ]);
        KebutuhanPegawai::create([
            'unor_id' => $dikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-007')->first()->id,
            'tahun' => null,
            'jumlah' => 5,
        ]);

        // ── DINKES ──
        KebutuhanPegawai::create([
            'unor_id' => $dinkes->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DINKES-003')->first()->id,
            'tahun' => null,
            'jumlah' => 5,
        ]);
        KebutuhanPegawai::create([
            'unor_id' => $dinkes->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DINKES-004')->first()->id,
            'tahun' => null,
            'jumlah' => 8,
        ]);
    }
}
