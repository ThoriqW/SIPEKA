<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\Opd;
use App\Models\Pegawai;
use Illuminate\Database\Seeder;

class PegawaiSeeder extends Seeder
{
    public function run(): void
    {
        $opdDikbud = Opd::where('kode_opd', 'DIKBUD')->first();
        $opdDinkes = Opd::where('kode_opd', 'DINKES')->first();

        // OPD 1 - Dinas Pendidikan dan Kebudayaan
        // Kepala OPD
        Pegawai::create([
            'nama' => 'Dr. Andi Mahmud, M.Pd.',
            'nip' => '197505152000011001',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1975-05-15',
            'golongan_pangkat' => 'IV/c',
            'pendidikan' => 'S3',
            'jenjang' => 'Pimpinan Tinggi Pratama',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-001')->first()->id,
        ]);

        // Sekretariat
        Pegawai::create([
            'nama' => 'Siti Rahayu, S.E., M.M.',
            'nip' => '198002202005012002',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1980-02-20',
            'golongan_pangkat' => 'III/d',
            'pendidikan' => 'S2',
            'jenjang' => 'Administrator',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-002')->first()->id,
        ]);

        // Bidang SD
        Pegawai::create([
            'nama' => 'Budi Santoso, S.Pd., M.Si.',
            'nip' => '197811102003011003',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1978-11-10',
            'golongan_pangkat' => 'III/c',
            'pendidikan' => 'S2',
            'jenjang' => 'Administrator',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-003')->first()->id,
        ]);

        // Sub Bagian Keuangan
        Pegawai::create([
            'nama' => 'Dewi Anggraini, S.E.',
            'nip' => '198505252010012004',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1985-05-25',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'S1',
            'jenjang' => 'Pengawas',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-004')->first()->id,
        ]);

        // Pengelola Keuangan - 2 pegawai
        Pegawai::create([
            'nama' => 'Ahmad Fauzi',
            'nip' => '199003152015011005',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1990-03-15',
            'golongan_pangkat' => 'II/c',
            'pendidikan' => 'D3',
            'jenjang' => 'Pelaksana',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-005')->first()->id,
        ]);

        Pegawai::create([
            'nama' => 'Ratna Dewi',
            'nip' => '199207202015012006',
            'jenis_kepegawaian' => 'PPPK',
            'tanggal_lahir' => '1992-07-20',
            'golongan_pangkat' => 'II/b',
            'pendidikan' => 'D3',
            'jenjang' => 'Pelaksana',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-005')->first()->id,
        ]);

        // Guru SD - 6 pegawai
        Pegawai::create([
            'nama' => 'Dra. Nurhayati',
            'nip' => '196501011990012007',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1965-01-01',
            'golongan_pangkat' => 'IV/a',
            'pendidikan' => 'S1',
            'jenjang' => 'Ahli Madya',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-006')->first()->id,
        ]);

        Pegawai::create([
            'nama' => 'Suparno, S.Pd.',
            'nip' => '197003121995011008',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1970-03-12',
            'golongan_pangkat' => 'III/d',
            'pendidikan' => 'S1',
            'jenjang' => 'Ahli Muda',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-006')->first()->id,
        ]);

        Pegawai::create([
            'nama' => 'Rina Kusuma, S.Pd.',
            'nip' => '197508082000012009',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1975-08-08',
            'golongan_pangkat' => 'III/c',
            'pendidikan' => 'S1',
            'jenjang' => 'Ahli Muda',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-006')->first()->id,
        ]);

        Pegawai::create([
            'nama' => 'Dedi Irawan, S.Pd.',
            'nip' => '198204302010011010',
            'jenis_kepegawaian' => 'PPPK',
            'tanggal_lahir' => '1982-04-30',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'S1',
            'jenjang' => 'Ahli Pertama',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-006')->first()->id,
        ]);

        Pegawai::create([
            'nama' => 'Fitriani, S.Pd.',
            'nip' => '198807152015022011',
            'jenis_kepegawaian' => 'PPPK',
            'tanggal_lahir' => '1988-07-15',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'S1',
            'jenjang' => 'Ahli Pertama',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-006')->first()->id,
        ]);

        Pegawai::create([
            'nama' => 'Hendra Gunawan, S.Pd.',
            'nip' => '199101012020011012',
            'jenis_kepegawaian' => 'PPPK',
            'tanggal_lahir' => '1991-01-01',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'S1',
            'jenjang' => 'Ahli Pertama',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-006')->first()->id,
        ]);

        // Operator Sekolah - 2 pegawai
        Pegawai::create([
            'nama' => 'Bayu Prasetyo',
            'nip' => '199505102020011013',
            'jenis_kepegawaian' => 'PPPK',
            'tanggal_lahir' => '1995-05-10',
            'golongan_pangkat' => 'II/a',
            'pendidikan' => 'SMA',
            'jenjang' => 'Pelaksana',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-007')->first()->id,
        ]);

        Pegawai::create([
            'nama' => 'Indah Permata',
            'nip' => '199608252020012014',
            'jenis_kepegawaian' => 'PPPK',
            'tanggal_lahir' => '1996-08-25',
            'golongan_pangkat' => 'II/a',
            'pendidikan' => 'SMA',
            'jenjang' => 'Pelaksana',
            'opd_id' => $opdDikbud->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DIKBUD-007')->first()->id,
        ]);

        // OPD 2 - Dinas Kesehatan
        // Kepala Dinkes
        Pegawai::create([
            'nama' => 'dr. Hj. Rahmaniar, M.Kes.',
            'nip' => '197003152005012001',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1970-03-15',
            'golongan_pangkat' => 'IV/c',
            'pendidikan' => 'S2',
            'jenjang' => 'Pimpinan Tinggi Pratama',
            'opd_id' => $opdDinkes->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DINKES-001')->first()->id,
        ]);

        // Bidang Pelayanan
        Pegawai::create([
            'nama' => 'drg. Markus Latuconsina',
            'nip' => '197508102000011002',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1975-08-10',
            'golongan_pangkat' => 'III/d',
            'pendidikan' => 'S1',
            'jenjang' => 'Administrator',
            'opd_id' => $opdDinkes->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DINKES-002')->first()->id,
        ]);

        // Dokter Umum - 3 pegawai
        Pegawai::create([
            'nama' => 'dr. Andini Putri',
            'nip' => '198506152010012003',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1985-06-15',
            'golongan_pangkat' => 'III/b',
            'pendidikan' => 'S1',
            'jenjang' => 'Ahli Pertama',
            'opd_id' => $opdDinkes->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DINKES-003')->first()->id,
        ]);

        Pegawai::create([
            'nama' => 'dr. Rizky Pratama',
            'nip' => '198810252015011004',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1988-10-25',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'S1',
            'jenjang' => 'Ahli Pertama',
            'opd_id' => $opdDinkes->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DINKES-003')->first()->id,
        ]);

        Pegawai::create([
            'nama' => 'dr. Melisa Sari',
            'nip' => '199205102020012005',
            'jenis_kepegawaian' => 'PPPK',
            'tanggal_lahir' => '1992-05-10',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'S1',
            'jenjang' => 'Ahli Pertama',
            'opd_id' => $opdDinkes->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DINKES-003')->first()->id,
        ]);

        // Perawat - 4 pegawai
        Pegawai::create([
            'nama' => 'Nurul Hidayah, A.Md.Kep.',
            'nip' => '198812012010012008',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1988-12-01',
            'golongan_pangkat' => 'II/d',
            'pendidikan' => 'D3',
            'jenjang' => 'Keterampilan - Terampil',
            'opd_id' => $opdDinkes->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DINKES-004')->first()->id,
        ]);

        Pegawai::create([
            'nama' => 'Agus Salim, A.Md.Kep.',
            'nip' => '199006152015011009',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1990-06-15',
            'golongan_pangkat' => 'II/c',
            'pendidikan' => 'D3',
            'jenjang' => 'Keterampilan - Terampil',
            'opd_id' => $opdDinkes->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DINKES-004')->first()->id,
        ]);

        Pegawai::create([
            'nama' => 'Rini Astuti, A.Md.Kep.',
            'nip' => '199302202020012010',
            'jenis_kepegawaian' => 'PPPK',
            'tanggal_lahir' => '1993-02-20',
            'golongan_pangkat' => 'II/b',
            'pendidikan' => 'D3',
            'jenjang' => 'Keterampilan - Terampil',
            'opd_id' => $opdDinkes->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DINKES-004')->first()->id,
        ]);

        Pegawai::create([
            'nama' => 'Dian Permata, A.Md.Kep.',
            'nip' => '199507102020012011',
            'jenis_kepegawaian' => 'PPPK',
            'tanggal_lahir' => '1995-07-10',
            'golongan_pangkat' => 'II/a',
            'pendidikan' => 'D3',
            'jenjang' => 'Keterampilan - Terampil',
            'opd_id' => $opdDinkes->id,
            'jabatan_id' => Jabatan::where('kode_jabatan', 'DINKES-004')->first()->id,
        ]);
    }
}
