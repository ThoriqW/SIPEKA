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
        $unorList = Unor::whereNotNull('parent_id')->get();

        foreach ($unorList as $unor) {
            $jabatanList = Jabatan::where('opd_id', $unor->id)->get();

            foreach ($jabatanList as $jabatan) {
                $jumlah = match (true) {
                    // Struktural — Pimpinan Tinggi Pratama: selalu 1
                    $jabatan->jenis_jabatan === 'Struktural' && $jabatan->jenjang === 'Pimpinan Tinggi Pratama' => 1,
                    // Struktural — Administrator / Pengawas: 1-2
                    $jabatan->jenis_jabatan === 'Struktural' => 1,
                    // Guru, Dokter, Perawat, Bidan: butuh lebih banyak
                    str_contains($jabatan->nama_jabatan, 'Guru') => rand(3, 6),
                    str_contains($jabatan->nama_jabatan, 'Dokter') => rand(2, 4),
                    str_contains($jabatan->nama_jabatan, 'Perawat') => rand(2, 5),
                    str_contains($jabatan->nama_jabatan, 'Bidan') => rand(2, 3),
                    // Fungsional lain: 1-3
                    $jabatan->jenis_jabatan === 'Fungsional' => rand(1, 3),
                    // Pelaksana: 2-5
                    $jabatan->jenis_jabatan === 'Pelaksana' => rand(2, 5),
                    default => 1,
                };

                KebutuhanPegawai::create([
                    'unor_id' => $unor->id,
                    'jabatan_id' => $jabatan->id,
                    'tahun' => null,
                    'jumlah' => $jumlah,
                ]);
            }
        }
    }
}
