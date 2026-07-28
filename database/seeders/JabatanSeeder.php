<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\Unor;
use Illuminate\Database\Seeder;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        $opdDikbud = Unor::where('kode_unor', 'DIKBUD')->first();
        $opdDinkes = Unor::where('kode_unor', 'DINKES')->first();

        // ── OPD 1: Dinas Pendidikan dan Kebudayaan ──
        Jabatan::create([
            'nama_jabatan' => 'Kepala Dinas Pendidikan dan Kebudayaan',
            'kode_jabatan' => 'DIKBUD-001',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 15,
            'jenjang' => 'Pimpinan Tinggi Pratama',
            'opd_id' => $opdDikbud->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Sekretariat',
            'kode_jabatan' => 'DIKBUD-002',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 13,
            'jenjang' => 'Administrator',
            'opd_id' => $opdDikbud->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Bidang Sekolah Dasar',
            'kode_jabatan' => 'DIKBUD-003',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang' => 'Administrator',
            'opd_id' => $opdDikbud->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Sub Bagian Keuangan',
            'kode_jabatan' => 'DIKBUD-004',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 10,
            'jenjang' => 'Pengawas',
            'opd_id' => $opdDikbud->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Pengelola Keuangan',
            'kode_jabatan' => 'DIKBUD-005',
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 6,
            'jenjang' => 'Pelaksana',
            'opd_id' => $opdDikbud->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Guru - Guru Kelas',
            'kode_jabatan' => 'DIKBUD-006',
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang' => 'Ahli Pertama',
            'opd_id' => $opdDikbud->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Operator Sekolah',
            'kode_jabatan' => 'DIKBUD-007',
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 5,
            'jenjang' => 'Pelaksana',
            'opd_id' => $opdDikbud->id,
        ]);

        // ── OPD 2: Dinas Kesehatan ──
        Jabatan::create([
            'nama_jabatan' => 'Kepala Dinas Kesehatan',
            'kode_jabatan' => 'DINKES-001',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 15,
            'jenjang' => 'Pimpinan Tinggi Pratama',
            'opd_id' => $opdDinkes->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Bidang Pelayanan Kesehatan',
            'kode_jabatan' => 'DINKES-002',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 12,
            'jenjang' => 'Administrator',
            'opd_id' => $opdDinkes->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Dokter - Dokter Umum',
            'kode_jabatan' => 'DINKES-003',
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 9,
            'jenjang' => 'Ahli Pertama',
            'opd_id' => $opdDinkes->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Perawat',
            'kode_jabatan' => 'DINKES-004',
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 7,
            'jenjang' => 'Keterampilan - Terampil',
            'opd_id' => $opdDinkes->id,
        ]);
    }
}
