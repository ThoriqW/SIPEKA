<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use Illuminate\Database\Seeder;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        // OPD 1: Dinas Pendidikan dan Kebudayaan
        $kepala_opd1 = Jabatan::create([
            'nama_jabatan' => 'Kepala Dinas Pendidikan dan Kebudayaan',
            'kode_jabatan' => 'DIKBUD-001',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 15,
            'jenjang' => 'Pimpinan Tinggi Pratama',
            'kebutuhan' => null,
            'opd_id' => 1,
            'induk_jabatan_id' => null,
        ]);

        $sekretariat = Jabatan::create([
            'nama_jabatan' => 'Sekretariat',
            'kode_jabatan' => 'DIKBUD-002',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 13,
            'jenjang' => 'Ahli Madya',
            'kebutuhan' => null,
            'opd_id' => 1,
            'induk_jabatan_id' => $kepala_opd1->id,
        ]);

        $bidang_sd = Jabatan::create([
            'nama_jabatan' => 'Bidang Sekolah Dasar',
            'kode_jabatan' => 'DIKBUD-003',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang' => 'Ahli Muda',
            'kebutuhan' => null,
            'opd_id' => 1,
            'induk_jabatan_id' => $kepala_opd1->id,
        ]);

        $sub_keuangan = Jabatan::create([
            'nama_jabatan' => 'Sub Bagian Keuangan',
            'kode_jabatan' => 'DIKBUD-004',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 10,
            'jenjang' => 'Ahli Pertama',
            'kebutuhan' => null,
            'opd_id' => 1,
            'induk_jabatan_id' => $sekretariat->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Pengelola Keuangan',
            'kode_jabatan' => 'DIKBUD-005',
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 6,
            'jenjang' => 'Pelaksana',
            'kebutuhan' => 3,
            'opd_id' => 1,
            'induk_jabatan_id' => $sub_keuangan->id,
        ]);

        $guru_sd = Jabatan::create([
            'nama_jabatan' => 'Guru Sekolah Dasar',
            'kode_jabatan' => 'DIKBUD-006',
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang' => 'Ahli Pertama',
            'kebutuhan' => 10,
            'opd_id' => 1,
            'induk_jabatan_id' => $bidang_sd->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Operator Sekolah',
            'kode_jabatan' => 'DIKBUD-007',
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 5,
            'jenjang' => 'Pelaksana',
            'kebutuhan' => 5,
            'opd_id' => 1,
            'induk_jabatan_id' => $bidang_sd->id, // Struktural
        ]);

        // OPD 2: Dinas Kesehatan
        $kepala_opd2 = Jabatan::create([
            'nama_jabatan' => 'Kepala Dinas Kesehatan',
            'kode_jabatan' => 'DINKES-001',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 15,
            'jenjang' => 'Pimpinan Tinggi Pratama',
            'kebutuhan' => null,
            'opd_id' => 2,
            'induk_jabatan_id' => null,
        ]);

        $bidang_pelayanan = Jabatan::create([
            'nama_jabatan' => 'Bidang Pelayanan Kesehatan',
            'kode_jabatan' => 'DINKES-002',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang' => 'Ahli Muda',
            'kebutuhan' => null,
            'opd_id' => 2,
            'induk_jabatan_id' => $kepala_opd2->id,
        ]);

        $dokter = Jabatan::create([
            'nama_jabatan' => 'Dokter Umum',
            'kode_jabatan' => 'DINKES-003',
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 9,
            'jenjang' => 'Ahli Pertama',
            'kebutuhan' => 5,
            'opd_id' => 2,
            'induk_jabatan_id' => $bidang_pelayanan->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Perawat',
            'kode_jabatan' => 'DINKES-004',
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 7,
            'jenjang' => 'Keterampilan - Terampil',
            'kebutuhan' => 8,
            'opd_id' => 2,
            'induk_jabatan_id' => $bidang_pelayanan->id, // Struktural
        ]);
    }
}
