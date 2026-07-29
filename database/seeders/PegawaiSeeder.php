<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\Unor;
use App\Models\Pegawai;
use Illuminate\Database\Seeder;

class PegawaiSeeder extends Seeder
{
    public function run(): void
    {
        $dikbud  = Unor::where('kode_unor', 'DIKBUD')->first();
        $dinkes  = Unor::where('kode_unor', 'DINKES')->first();
        $pupr    = Unor::where('kode_unor', 'PUPR')->first();
        $bkpsdm  = Unor::where('kode_unor', 'BKPSDM')->first();
        $setda   = Unor::where('kode_unor', 'SETDA')->first();

        $jDikbud = fn(string $kode) => Jabatan::where('kode_jabatan', $kode)->first()->id;
        $jDinkes = fn(string $kode) => Jabatan::where('kode_jabatan', $kode)->first()->id;
        $jPupr   = fn(string $kode) => Jabatan::where('kode_jabatan', $kode)->first()->id;
        $jBkpsdm = fn(string $kode) => Jabatan::where('kode_jabatan', $kode)->first()->id;
        $jSetda  = fn(string $kode) => Jabatan::where('kode_jabatan', $kode)->first()->id;

        // ============================================================
        // DIKBUD — 15 pegawai
        // ============================================================
        $dikbudPegawai = [
            // Struktural
            ['nama' => 'Dr. Andi Mahmud, M.Pd.',         'nip' => '197505152000011001', 'jenis' => 'PNS',  'tgl' => '1975-05-15', 'gol' => 'IV/c', 'pend' => 'S3', 'jenjang' => 'Pimpinan Tinggi Pratama', 'jabatan_id' => $jDikbud('DIKBUD-001')],
            ['nama' => 'Siti Rahayu, S.E., M.M.',         'nip' => '198002202005012002', 'jenis' => 'PNS',  'tgl' => '1980-02-20', 'gol' => 'III/d','pend' => 'S2', 'jenjang' => 'Administrator',            'jabatan_id' => $jDikbud('DIKBUD-002')],
            ['nama' => 'Budi Santoso, S.Pd., M.Si.',      'nip' => '197811102003011003', 'jenis' => 'PNS',  'tgl' => '1978-11-10', 'gol' => 'III/c','pend' => 'S2', 'jenjang' => 'Administrator',            'jabatan_id' => $jDikbud('DIKBUD-003')],
            ['nama' => 'Dra. Megawati H.M., M.Pd.',       'nip' => '196606082000012004', 'jenis' => 'PNS',  'tgl' => '1966-06-08', 'gol' => 'IV/b','pend' => 'S2', 'jenjang' => 'Administrator',            'jabatan_id' => $jDikbud('DIKBUD-004')],
            ['nama' => 'Dewi Anggraini, S.E.',             'nip' => '198505252010012005', 'jenis' => 'PNS',  'tgl' => '1985-05-25', 'gol' => 'III/a','pend' => 'S1', 'jenjang' => 'Pengawas',                 'jabatan_id' => $jDikbud('DIKBUD-005')],
            ['nama' => 'Rahmat Hidayat, S.E.',             'nip' => '198710102015011006', 'jenis' => 'PNS',  'tgl' => '1987-10-10', 'gol' => 'III/b','pend' => 'S1', 'jenjang' => 'Pengawas',                 'jabatan_id' => $jDikbud('DIKBUD-006')],
            // Kasi (4)
            ['nama' => 'Nurul Aini, S.Sos.',               'nip' => '198803152015012007', 'jenis' => 'PNS',  'tgl' => '1988-03-15', 'gol' => 'III/a','pend' => 'S1', 'jenjang' => 'Pengawas',                 'jabatan_id' => $jDikbud('DIKBUD-007')],
            ['nama' => 'Hendra Setiawan, S.Pd.',           'nip' => '198911202015011008', 'jenis' => 'PPPK','tgl' => '1989-11-20', 'gol' => 'III/a','pend' => 'S1', 'jenjang' => 'Pengawas',                 'jabatan_id' => $jDikbud('DIKBUD-008')],
            // Guru (5)
            ['nama' => 'Dra. Nurhayati',                   'nip' => '196701011990012007', 'jenis' => 'PNS',  'tgl' => '1967-01-01', 'gol' => 'IV/a','pend' => 'S1', 'jenjang' => 'Ahli Pertama',             'jabatan_id' => $jDikbud('DIKBUD-011')],
            ['nama' => 'Suparno, S.Pd.',                   'nip' => '197003121995011008', 'jenis' => 'PNS',  'tgl' => '1970-03-12', 'gol' => 'III/d','pend' => 'S1', 'jenjang' => 'Ahli Muda',                'jabatan_id' => $jDikbud('DIKBUD-012')],
            ['nama' => 'Rina Kusuma, S.Pd.',               'nip' => '197508082000012009', 'jenis' => 'PNS',  'tgl' => '1975-08-08', 'gol' => 'III/c','pend' => 'S1', 'jenjang' => 'Ahli Pertama',             'jabatan_id' => $jDikbud('DIKBUD-013')],
            ['nama' => 'Dedi Irawan, S.Pd.',               'nip' => '196804302010011010', 'jenis' => 'PNS',  'tgl' => '1968-04-30', 'gol' => 'IV/a','pend' => 'S1', 'jenjang' => 'Ahli Muda',                'jabatan_id' => $jDikbud('DIKBUD-014')],
            ['nama' => 'Fitriani, S.Pd.',                  'nip' => '198807152015022011', 'jenis' => 'PPPK','tgl' => '1988-07-15', 'gol' => 'III/a','pend' => 'S1', 'jenjang' => 'Ahli Pertama',             'jabatan_id' => $jDikbud('DIKBUD-015')],
            // Pelaksana (2)
            ['nama' => 'Ahmad Fauzi',                      'nip' => '199003152015011013', 'jenis' => 'PNS',  'tgl' => '1990-03-15', 'gol' => 'II/c', 'pend' => 'D3', 'jenjang' => 'Pelaksana',                'jabatan_id' => $jDikbud('DIKBUD-021')],
            ['nama' => 'Bayu Prasetyo',                    'nip' => '199505102020011014', 'jenis' => 'PPPK','tgl' => '1995-05-10', 'gol' => 'II/a', 'pend' => 'SMA','jenjang' => 'Pelaksana',                'jabatan_id' => $jDikbud('DIKBUD-024')],
        ];

        foreach ($dikbudPegawai as $p) {
            Pegawai::create([
                'nama' => $p['nama'], 'nip' => $p['nip'], 'jenis_kepegawaian' => $p['jenis'],
                'tanggal_lahir' => $p['tgl'], 'golongan_pangkat' => $p['gol'],
                'pendidikan' => $p['pend'], 'jenjang' => $p['jenjang'],
                'opd_id' => $dikbud->id, 'jabatan_id' => $p['jabatan_id'],
            ]);
        }

        // ============================================================
        // DINKES — 10 pegawai
        // ============================================================
        $dinkesPegawai = [
            ['nama' => 'dr. Hj. Rahmaniar, M.Kes.',        'nip' => '197003152005012001', 'jenis' => 'PNS',  'tgl' => '1970-03-15', 'gol' => 'IV/c', 'pend' => 'S2', 'jenjang' => 'Pimpinan Tinggi Pratama', 'jabatan_id' => $jDinkes('DINKES-001')],
            ['nama' => 'drg. Markus Latuconsina',          'nip' => '197508102000011002', 'jenis' => 'PNS',  'tgl' => '1975-08-10', 'gol' => 'III/d','pend' => 'S1', 'jenjang' => 'Administrator',            'jabatan_id' => $jDinkes('DINKES-002')],
            ['nama' => 'dr. Nurul Hidayah, M.K.M.',        'nip' => '196807152000012003', 'jenis' => 'PNS',  'tgl' => '1968-07-15', 'gol' => 'IV/a', 'pend' => 'S2', 'jenjang' => 'Administrator',            'jabatan_id' => $jDinkes('DINKES-003')],
            ['nama' => 'dr. H. Syamsul Bahri, M.Kes.',     'nip' => '196512102000011004', 'jenis' => 'PNS',  'tgl' => '1965-12-10', 'gol' => 'IV/b', 'pend' => 'S2', 'jenjang' => 'Administrator',            'jabatan_id' => $jDinkes('DINKES-004')],
            ['nama' => 'Rosmawati, S.K.M.',                'nip' => '198502152010012005', 'jenis' => 'PNS',  'tgl' => '1985-02-15', 'gol' => 'III/b','pend' => 'S1', 'jenjang' => 'Pengawas',                 'jabatan_id' => $jDinkes('DINKES-005')],
            // Dokter (2)
            ['nama' => 'dr. Andini Putri',                 'nip' => '198506152010012006', 'jenis' => 'PNS',  'tgl' => '1985-06-15', 'gol' => 'III/b','pend' => 'S1', 'jenjang' => 'Ahli Pertama',             'jabatan_id' => $jDinkes('DINKES-009')],
            ['nama' => 'dr. Rizky Pratama',                'nip' => '198810252015011007', 'jenis' => 'PNS',  'tgl' => '1988-10-25', 'gol' => 'III/a','pend' => 'S1', 'jenjang' => 'Ahli Muda',                'jabatan_id' => $jDinkes('DINKES-010')],
            // Perawat + Bidan (2)
            ['nama' => 'Nurul Hidayah, A.Md.Kep.',         'nip' => '198812012010012008', 'jenis' => 'PNS',  'tgl' => '1988-12-01', 'gol' => 'II/d', 'pend' => 'D3', 'jenjang' => 'Keterampilan - Terampil',   'jabatan_id' => $jDinkes('DINKES-014')],
            ['nama' => 'Rini Astuti, A.Md.Keb.',           'nip' => '199302202020012009', 'jenis' => 'PPPK','tgl' => '1993-02-20', 'gol' => 'II/b', 'pend' => 'D3', 'jenjang' => 'Keterampilan - Terampil',   'jabatan_id' => $jDinkes('DINKES-015')],
            // Pelaksana
            ['nama' => 'Agus Salim',                       'nip' => '199006152015011010', 'jenis' => 'PNS',  'tgl' => '1990-06-15', 'gol' => 'II/c', 'pend' => 'D3', 'jenjang' => 'Pelaksana',                'jabatan_id' => $jDinkes('DINKES-016')],
        ];

        foreach ($dinkesPegawai as $p) {
            Pegawai::create([
                'nama' => $p['nama'], 'nip' => $p['nip'], 'jenis_kepegawaian' => $p['jenis'],
                'tanggal_lahir' => $p['tgl'], 'golongan_pangkat' => $p['gol'],
                'pendidikan' => $p['pend'], 'jenjang' => $p['jenjang'],
                'opd_id' => $dinkes->id, 'jabatan_id' => $p['jabatan_id'],
            ]);
        }

        // ============================================================
        // PUPR — 6 pegawai
        // ============================================================
        $puprPegawai = [
            ['nama' => 'Ir. La Ode Muhidin, M.T.',         'nip' => '197304102000011001', 'jenis' => 'PNS',  'tgl' => '1973-04-10', 'gol' => 'IV/c', 'pend' => 'S2', 'jenjang' => 'Pimpinan Tinggi Pratama', 'jabatan_id' => $jPupr('PUPR-001')],
            ['nama' => 'Wa Ode Nurhayati, S.T., M.T.',     'nip' => '197808152005012002', 'jenis' => 'PNS',  'tgl' => '1978-08-15', 'gol' => 'III/d','pend' => 'S2', 'jenjang' => 'Administrator',            'jabatan_id' => $jPupr('PUPR-002')],
            ['nama' => 'Ahmad Yani, S.T.',                  'nip' => '198212102010011003', 'jenis' => 'PNS',  'tgl' => '1982-12-10', 'gol' => 'III/c','pend' => 'S1', 'jenjang' => 'Administrator',            'jabatan_id' => $jPupr('PUPR-003')],
            ['nama' => 'Sri Wahyuni, S.T.',                 'nip' => '198703252015012004', 'jenis' => 'PNS',  'tgl' => '1987-03-25', 'gol' => 'III/a','pend' => 'S1', 'jenjang' => 'Pengawas',                 'jabatan_id' => $jPupr('PUPR-004')],
            ['nama' => 'Muh. Yusuf',                       'nip' => '199107102015011005', 'jenis' => 'PNS',  'tgl' => '1991-07-10', 'gol' => 'II/c', 'pend' => 'D3', 'jenjang' => 'Pelaksana',                'jabatan_id' => $jPupr('PUPR-007')],
            ['nama' => 'Linda Marlina',                     'nip' => '199403152020012006', 'jenis' => 'PPPK','tgl' => '1994-03-15', 'gol' => 'II/a', 'pend' => 'D3', 'jenjang' => 'Pelaksana',                'jabatan_id' => $jPupr('PUPR-008')],
        ];

        foreach ($puprPegawai as $p) {
            Pegawai::create([
                'nama' => $p['nama'], 'nip' => $p['nip'], 'jenis_kepegawaian' => $p['jenis'],
                'tanggal_lahir' => $p['tgl'], 'golongan_pangkat' => $p['gol'],
                'pendidikan' => $p['pend'], 'jenjang' => $p['jenjang'],
                'opd_id' => $pupr->id, 'jabatan_id' => $p['jabatan_id'],
            ]);
        }

        // ============================================================
        // BKPSDM — 7 pegawai
        // ============================================================
        $bkpsdmPegawai = [
            ['nama' => 'Drs. Abdul Muis, M.Si.',           'nip' => '197205202000011001', 'jenis' => 'PNS',  'tgl' => '1972-05-20', 'gol' => 'IV/c', 'pend' => 'S2', 'jenjang' => 'Pimpinan Tinggi Pratama', 'jabatan_id' => $jBkpsdm('BKPSDM-001')],
            ['nama' => 'Hj. Nurjannah, S.Sos., M.M.',      'nip' => '196903102005012002', 'jenis' => 'PNS',  'tgl' => '1969-03-10', 'gol' => 'IV/b', 'pend' => 'S2', 'jenjang' => 'Administrator',            'jabatan_id' => $jBkpsdm('BKPSDM-002')],
            ['nama' => 'I Made Suardika, S.H.',             'nip' => '197911152010011003', 'jenis' => 'PNS',  'tgl' => '1979-11-15', 'gol' => 'III/d','pend' => 'S1', 'jenjang' => 'Administrator',            'jabatan_id' => $jBkpsdm('BKPSDM-003')],
            ['nama' => 'Indah Permatasari, S.E.',          'nip' => '198412202015012004', 'jenis' => 'PNS',  'tgl' => '1984-12-20', 'gol' => 'III/b','pend' => 'S1', 'jenjang' => 'Pengawas',                 'jabatan_id' => $jBkpsdm('BKPSDM-004')],
            ['nama' => 'Moh. Thoriq, S.Kom.',               'nip' => '199005152015011005', 'jenis' => 'PNS',  'tgl' => '1990-05-15', 'gol' => 'III/a','pend' => 'S1', 'jenjang' => 'Ahli Pertama',             'jabatan_id' => $jBkpsdm('BKPSDM-007')],
            ['nama' => 'Zulkifli, A.Md.',                   'nip' => '198806102010011006', 'jenis' => 'PNS',  'tgl' => '1988-06-10', 'gol' => 'II/c', 'pend' => 'D3', 'jenjang' => 'Pelaksana',                'jabatan_id' => $jBkpsdm('BKPSDM-008')],
            ['nama' => 'Kartika Dewi',                      'nip' => '199307202020012007', 'jenis' => 'PPPK','tgl' => '1993-07-20', 'gol' => 'II/a', 'pend' => 'D3', 'jenjang' => 'Pelaksana',                'jabatan_id' => $jBkpsdm('BKPSDM-009')],
        ];

        foreach ($bkpsdmPegawai as $p) {
            Pegawai::create([
                'nama' => $p['nama'], 'nip' => $p['nip'], 'jenis_kepegawaian' => $p['jenis'],
                'tanggal_lahir' => $p['tgl'], 'golongan_pangkat' => $p['gol'],
                'pendidikan' => $p['pend'], 'jenjang' => $p['jenjang'],
                'opd_id' => $bkpsdm->id, 'jabatan_id' => $p['jabatan_id'],
            ]);
        }

        // ============================================================
        // SETDA — 7 pegawai
        // ============================================================
        $setdaPegawai = [
            ['nama' => 'Dr. H. Asri, M.Si.',                'nip' => '196804052000011001', 'jenis' => 'PNS',  'tgl' => '1968-04-05', 'gol' => 'IV/d', 'pend' => 'S3', 'jenjang' => 'Pimpinan Tinggi Pratama', 'jabatan_id' => $jSetda('SETDA-001')],
            ['nama' => 'Hj. Syarifah, S.H., M.Hum.',       'nip' => '197305202005012002', 'jenis' => 'PNS',  'tgl' => '1973-05-20', 'gol' => 'IV/b', 'pend' => 'S2', 'jenjang' => 'Administrator',            'jabatan_id' => $jSetda('SETDA-002')],
            ['nama' => 'Drs. Sutrisno, M.M.',               'nip' => '197007152000011003', 'jenis' => 'PNS',  'tgl' => '1970-07-15', 'gol' => 'IV/a', 'pend' => 'S2', 'jenjang' => 'Administrator',            'jabatan_id' => $jSetda('SETDA-003')],
            ['nama' => 'Ruslan Abdul Gani, S.STP.',          'nip' => '198108102010011004', 'jenis' => 'PNS',  'tgl' => '1981-08-10', 'gol' => 'III/c','pend' => 'S1', 'jenjang' => 'Administrator',            'jabatan_id' => $jSetda('SETDA-004')],
            ['nama' => 'Amiruddin, S.E.',                   'nip' => '198511152015011005', 'jenis' => 'PNS',  'tgl' => '1985-11-15', 'gol' => 'III/b','pend' => 'S1', 'jenjang' => 'Pengawas',                 'jabatan_id' => $jSetda('SETDA-006')],
            ['nama' => 'Faisal Rahman',                     'nip' => '199202152015011006', 'jenis' => 'PNS',  'tgl' => '1992-02-15', 'gol' => 'II/c', 'pend' => 'D3', 'jenjang' => 'Pelaksana',                'jabatan_id' => $jSetda('SETDA-008')],
            ['nama' => 'Nurul Aulia',                       'nip' => '199508102020012007', 'jenis' => 'PPPK','tgl' => '1995-08-10', 'gol' => 'II/a', 'pend' => 'D3', 'jenjang' => 'Pelaksana',                'jabatan_id' => $jSetda('SETDA-010')],
        ];

        foreach ($setdaPegawai as $p) {
            Pegawai::create([
                'nama' => $p['nama'], 'nip' => $p['nip'], 'jenis_kepegawaian' => $p['jenis'],
                'tanggal_lahir' => $p['tgl'], 'golongan_pangkat' => $p['gol'],
                'pendidikan' => $p['pend'], 'jenjang' => $p['jenjang'],
                'opd_id' => $setda->id, 'jabatan_id' => $p['jabatan_id'],
            ]);
        }
    }
}
