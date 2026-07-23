<?php

namespace Database\Seeders;

use App\Models\JabatanAsn;
use App\Models\NodeOrganisasi;
use App\Models\Opd;
use App\Models\Pegawai;
use Illuminate\Database\Seeder;

class PegawaiSeeder extends Seeder
{
    public function run(): void
    {
        $opdDikbud = Opd::where('kode_opd', 'DIKBUD')->first();
        $opdDinkes = Opd::where('kode_opd', 'DINKES')->first();

        $jasnGuruPertama  = JabatanAsn::where('nama_jabatan_asn', 'Guru Ahli Pertama')->first();
        $jasnGuruMuda     = JabatanAsn::where('nama_jabatan_asn', 'Guru Ahli Muda')->first();
        $jasnGuruMadya    = JabatanAsn::where('nama_jabatan_asn', 'Guru Ahli Madya')->first();
        $jasnDokterPertama = JabatanAsn::where('nama_jabatan_asn', 'Dokter Ahli Pertama')->first();
        $jasnPerawatTerampil  = JabatanAsn::where('nama_jabatan_asn', 'Perawat Keterampilan - Terampil')->first();
        $jasnKepalaDinas = JabatanAsn::where('nama_jabatan_asn', 'Kepala Dinas Pimpinan Tinggi Pratama')->first();
        $jasnKepalaBidang = JabatanAsn::where('nama_jabatan_asn', 'Kepala Bidang Administrator')->first();
        $jasnKepalaSubBagian = JabatanAsn::where('nama_jabatan_asn', 'Kepala Sub Bagian Pengawas')->first();
        $jasnPengelolaKeu = JabatanAsn::where('nama_jabatan_asn', 'Pengelola Keuangan Pelaksana')->first();
        $jasnOperator = JabatanAsn::where('nama_jabatan_asn', 'Operator Sekolah Pelaksana')->first();

        // Helper: ambil POSISI pertama yang kosong berdasarkan nama
        $posisi = fn(string $nama) => NodeOrganisasi::posisi()
            ->where('nama', 'LIKE', "{$nama}%")
            ->whereDoesntHave('pegawai')
            ->orderBy('nama')
            ->first();

        // OPD 1: Dinas Pendidikan
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
            'jabatan_asn_id' => $jasnKepalaDinas?->id,
            'posisi_organisasi_id' => $posisi('Kepala Dinas Pendidikan')?->id,
        ]);

        // Sekretaris — menggunakan posisi yang tersedia (tidak ada di seeder, jadi NULL dulu)
        Pegawai::create([
            'nama' => 'Siti Rahayu, S.E., M.M.',
            'nip' => '198002202005012002',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1980-02-20',
            'golongan_pangkat' => 'III/d',
            'pendidikan' => 'S2',
            'jenjang' => 'Administrator',
            'opd_id' => $opdDikbud->id,
            'jabatan_asn_id' => $jasnKepalaBidang?->id,
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
            'jabatan_asn_id' => $jasnKepalaBidang?->id,
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
            'jabatan_asn_id' => $jasnKepalaSubBagian?->id,
        ]);

        // Pengelola Keuangan
        Pegawai::create([
            'nama' => 'Ahmad Fauzi', 'nip' => '199003152015011005',
            'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => '1990-03-15',
            'golongan_pangkat' => 'II/c', 'pendidikan' => 'D3',
            'jenjang' => 'Pelaksana', 'opd_id' => $opdDikbud->id,
            'jabatan_asn_id' => $jasnPengelolaKeu?->id,
            'posisi_organisasi_id' => $posisi('Pengelola Keuangan')?->id,
        ]);
        Pegawai::create([
            'nama' => 'Ratna Dewi', 'nip' => '199207202015012006',
            'jenis_kepegawaian' => 'PPPK', 'tanggal_lahir' => '1992-07-20',
            'golongan_pangkat' => 'II/b', 'pendidikan' => 'D3',
            'jenjang' => 'Pelaksana', 'opd_id' => $opdDikbud->id,
            'jabatan_asn_id' => $jasnPengelolaKeu?->id,
            'posisi_organisasi_id' => $posisi('Pengelola Keuangan')?->id,
        ]);

        // Guru — 6 pegawai
        $guruData = [
            ['nama' => 'Dra. Nurhayati', 'nip' => '196501011990012007', 'tgl' => '1965-01-01', 'gol' => 'IV/a', 'jasn' => $jasnGuruMadya],
            ['nama' => 'Suparno, S.Pd.', 'nip' => '197003121995011008', 'tgl' => '1970-03-12', 'gol' => 'III/d', 'jasn' => $jasnGuruMuda],
            ['nama' => 'Rina Kusuma, S.Pd.', 'nip' => '197508082000012009', 'tgl' => '1975-08-08', 'gol' => 'III/c', 'jasn' => $jasnGuruMuda],
            ['nama' => 'Dedi Irawan, S.Pd.', 'nip' => '198204302010011010', 'tgl' => '1982-04-30', 'gol' => 'III/a', 'jasn' => $jasnGuruPertama, 'pppk' => true],
            ['nama' => 'Fitriani, S.Pd.', 'nip' => '198807152015022011', 'tgl' => '1988-07-15', 'gol' => 'III/a', 'jasn' => $jasnGuruPertama, 'pppk' => true],
            ['nama' => 'Hendra Gunawan, S.Pd.', 'nip' => '199101012020011012', 'tgl' => '1991-01-01', 'gol' => 'III/a', 'jasn' => $jasnGuruPertama, 'pppk' => true],
        ];
        foreach ($guruData as $g) {
            Pegawai::create([
                'nama' => $g['nama'], 'nip' => $g['nip'],
                'jenis_kepegawaian' => ($g['pppk'] ?? false) ? 'PPPK' : 'PNS',
                'tanggal_lahir' => $g['tgl'], 'golongan_pangkat' => $g['gol'],
                'pendidikan' => 'S1', 'jenjang' => $g['jasn']->jenjang,
                'opd_id' => $opdDikbud->id,
                'jabatan_asn_id' => $g['jasn']->id,
                'posisi_organisasi_id' => $posisi('Guru')?->id,
            ]);
        }

        // Operator Sekolah
        Pegawai::create([
            'nama' => 'Bayu Prasetyo', 'nip' => '199505102020011013',
            'jenis_kepegawaian' => 'PPPK', 'tanggal_lahir' => '1995-05-10',
            'golongan_pangkat' => 'II/a', 'pendidikan' => 'SMA',
            'jenjang' => 'Pelaksana', 'opd_id' => $opdDikbud->id,
            'jabatan_asn_id' => $jasnOperator?->id,
            'posisi_organisasi_id' => $posisi('Operator Sekolah')?->id,
        ]);
        Pegawai::create([
            'nama' => 'Indah Permata', 'nip' => '199608252020012014',
            'jenis_kepegawaian' => 'PPPK', 'tanggal_lahir' => '1996-08-25',
            'golongan_pangkat' => 'II/a', 'pendidikan' => 'SMA',
            'jenjang' => 'Pelaksana', 'opd_id' => $opdDikbud->id,
            'jabatan_asn_id' => $jasnOperator?->id,
            'posisi_organisasi_id' => $posisi('Operator Sekolah')?->id,
        ]);

        // OPD 2: Dinas Kesehatan
        Pegawai::create([
            'nama' => 'dr. Hj. Rahmaniar, M.Kes.',
            'nip' => '197003152005012001',
            'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => '1970-03-15',
            'golongan_pangkat' => 'IV/c', 'pendidikan' => 'S2',
            'jenjang' => 'Pimpinan Tinggi Pratama', 'opd_id' => $opdDinkes->id,
            'jabatan_asn_id' => $jasnKepalaDinas?->id,
            'posisi_organisasi_id' => $posisi('Kepala Dinas Kesehatan')?->id,
        ]);

        Pegawai::create([
            'nama' => 'drg. Markus Latuconsina',
            'nip' => '197508102000011002',
            'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => '1975-08-10',
            'golongan_pangkat' => 'III/d', 'pendidikan' => 'S1',
            'jenjang' => 'Administrator', 'opd_id' => $opdDinkes->id,
            'jabatan_asn_id' => $jasnKepalaBidang?->id,
        ]);

        // Dokter
        $dokterData = [
            ['nama' => 'dr. Andini Putri', 'nip' => '198506152010012003', 'tgl' => '1985-06-15', 'gol' => 'III/b'],
            ['nama' => 'dr. Rizky Pratama', 'nip' => '198810252015011004', 'tgl' => '1988-10-25', 'gol' => 'III/a'],
            ['nama' => 'dr. Melisa Sari', 'nip' => '199205102020012005', 'tgl' => '1992-05-10', 'gol' => 'III/a', 'pppk' => true],
        ];
        foreach ($dokterData as $d) {
            Pegawai::create([
                'nama' => $d['nama'], 'nip' => $d['nip'],
                'jenis_kepegawaian' => ($d['pppk'] ?? false) ? 'PPPK' : 'PNS',
                'tanggal_lahir' => $d['tgl'], 'golongan_pangkat' => $d['gol'],
                'pendidikan' => 'S1', 'jenjang' => $jasnDokterPertama->jenjang,
                'opd_id' => $opdDinkes->id,
                'jabatan_asn_id' => $jasnDokterPertama->id,
                'posisi_organisasi_id' => $posisi('Dokter')?->id,
            ]);
        }

        // Perawat
        $perawatData = [
            ['nama' => 'Nurul Hidayah, A.Md.Kep.', 'nip' => '198812012010012008', 'tgl' => '1988-12-01', 'gol' => 'II/d'],
            ['nama' => 'Agus Salim, A.Md.Kep.', 'nip' => '199006152015011009', 'tgl' => '1990-06-15', 'gol' => 'II/c'],
            ['nama' => 'Rini Astuti, A.Md.Kep.', 'nip' => '199302202020012010', 'tgl' => '1993-02-20', 'gol' => 'II/b', 'pppk' => true],
            ['nama' => 'Dian Permata, A.Md.Kep.', 'nip' => '199507102020012011', 'tgl' => '1995-07-10', 'gol' => 'II/a', 'pppk' => true],
        ];
        foreach ($perawatData as $p) {
            Pegawai::create([
                'nama' => $p['nama'], 'nip' => $p['nip'],
                'jenis_kepegawaian' => ($p['pppk'] ?? false) ? 'PPPK' : 'PNS',
                'tanggal_lahir' => $p['tgl'], 'golongan_pangkat' => $p['gol'],
                'pendidikan' => 'D3', 'jenjang' => 'Keterampilan - Terampil',
                'opd_id' => $opdDinkes->id,
                'jabatan_asn_id' => $jasnPerawatTerampil?->id,
                'posisi_organisasi_id' => $posisi('Perawat')?->id,
            ]);
        }
    }
}
