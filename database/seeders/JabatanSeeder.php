<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\Unor;
use Illuminate\Database\Seeder;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        $dikbud = Unor::where('kode_unor', 'DIKBUD')->first();
        $dinkes = Unor::where('kode_unor', 'DINKES')->first();
        $pupr   = Unor::where('kode_unor', 'PUPR')->first();
        $bkpsdm = Unor::where('kode_unor', 'BKPSDM')->first();
        $setda  = Unor::where('kode_unor', 'SETDA')->first();

        // ============================================================
        // DIKBUD — Dinas Pendidikan dan Kebudayaan
        // ============================================================
        $dikbudJabatan = [
            // Struktural
            ['nama' => 'Kepala Dinas',                                'jenis' => 'Struktural', 'kelas' => 15, 'jenjang' => 'Pimpinan Tinggi Pratama'],
            ['nama' => 'Sekretaris Dinas',                            'jenis' => 'Struktural', 'kelas' => 13, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Bidang',                               'jenis' => 'Struktural', 'kelas' => 12, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Bidang',                               'jenis' => 'Struktural', 'kelas' => 12, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Sub Bagian',                           'jenis' => 'Struktural', 'kelas' => 10, 'jenjang' => 'Pengawas'],
            ['nama' => 'Kepala Sub Bagian',                           'jenis' => 'Struktural', 'kelas' => 10, 'jenjang' => 'Pengawas'],
            ['nama' => 'Kepala Seksi',                                'jenis' => 'Struktural', 'kelas' => 9,  'jenjang' => 'Pengawas'],
            ['nama' => 'Kepala Seksi',                                'jenis' => 'Struktural', 'kelas' => 9,  'jenjang' => 'Pengawas'],
            ['nama' => 'Kepala Seksi',                                'jenis' => 'Struktural', 'kelas' => 9,  'jenjang' => 'Pengawas'],
            ['nama' => 'Kepala Seksi',                                'jenis' => 'Struktural', 'kelas' => 9,  'jenjang' => 'Pengawas'],
            // Fungsional Guru (dengan sub-jabatan)
            ['nama' => 'Guru - Guru Kelas',                           'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Pertama'],
            ['nama' => 'Guru - Guru Kelas',                           'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Muda'],
            ['nama' => 'Guru - Guru Matematika',                      'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Pertama'],
            ['nama' => 'Guru - Guru Matematika',                      'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Muda'],
            ['nama' => 'Guru - Guru Bahasa Indonesia',                'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Pertama'],
            ['nama' => 'Guru - Guru Bahasa Inggris',                  'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Pertama'],
            ['nama' => 'Guru - Guru IPA',                             'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Pertama'],
            ['nama' => 'Guru - Guru PPKN',                            'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Muda'],
            ['nama' => 'Guru - Guru PENJASORKES',                     'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Pertama'],
            ['nama' => 'Guru - Guru Bimbingan dan Konseling',         'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Muda'],
            // Pelaksana
            ['nama' => 'Pengelola Keuangan',                          'jenis' => 'Pelaksana',  'kelas' => 6, 'jenjang' => 'Pelaksana'],
            ['nama' => 'Pengadministrasi Umum',                       'jenis' => 'Pelaksana',  'kelas' => 5, 'jenjang' => 'Pelaksana'],
            ['nama' => 'Pengadministrasi Umum',                       'jenis' => 'Pelaksana',  'kelas' => 5, 'jenjang' => 'Pelaksana'],
            ['nama' => 'Operator Komputer',                           'jenis' => 'Pelaksana',  'kelas' => 5, 'jenjang' => 'Pelaksana'],
            ['nama' => 'Bendahara',                                   'jenis' => 'Pelaksana',  'kelas' => 6, 'jenjang' => 'Pelaksana'],
        ];

        foreach ($dikbudJabatan as $i => $j) {
            Jabatan::create([
                'nama_jabatan'  => $j['nama'],
                'kode_jabatan'  => sprintf('DIKBUD-%03d', $i + 1),
                'jenis_jabatan' => $j['jenis'],
                'kelas_jabatan' => $j['kelas'],
                'jenjang'       => $j['jenjang'],
                'opd_id'        => $dikbud->id,
            ]);
        }

        // ============================================================
        // DINKES — Dinas Kesehatan
        // ============================================================
        $dinkesJabatan = [
            // Struktural
            ['nama' => 'Kepala Dinas',                                'jenis' => 'Struktural', 'kelas' => 15, 'jenjang' => 'Pimpinan Tinggi Pratama'],
            ['nama' => 'Sekretaris Dinas',                            'jenis' => 'Struktural', 'kelas' => 13, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Bidang',                               'jenis' => 'Struktural', 'kelas' => 12, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Bidang',                               'jenis' => 'Struktural', 'kelas' => 12, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Sub Bagian',                           'jenis' => 'Struktural', 'kelas' => 10, 'jenjang' => 'Pengawas'],
            ['nama' => 'Kepala Seksi',                                'jenis' => 'Struktural', 'kelas' => 9,  'jenjang' => 'Pengawas'],
            ['nama' => 'Kepala Seksi',                                'jenis' => 'Struktural', 'kelas' => 9,  'jenjang' => 'Pengawas'],
            ['nama' => 'Kepala Seksi',                                'jenis' => 'Struktural', 'kelas' => 9,  'jenjang' => 'Pengawas'],
            // Fungsional Nakes
            ['nama' => 'Dokter - Dokter Umum',                        'jenis' => 'Fungsional', 'kelas' => 9, 'jenjang' => 'Ahli Pertama'],
            ['nama' => 'Dokter - Dokter Umum',                        'jenis' => 'Fungsional', 'kelas' => 9, 'jenjang' => 'Ahli Muda'],
            ['nama' => 'Dokter - Dokter Spesialis Anak',              'jenis' => 'Fungsional', 'kelas' => 10,'jenjang' => 'Ahli Muda'],
            ['nama' => 'Dokter - Dokter Spesialis Obgyn',             'jenis' => 'Fungsional', 'kelas' => 10,'jenjang' => 'Ahli Madya'],
            ['nama' => 'Dokter Gigi',                                 'jenis' => 'Fungsional', 'kelas' => 9, 'jenjang' => 'Ahli Pertama'],
            ['nama' => 'Perawat',                                     'jenis' => 'Fungsional', 'kelas' => 7, 'jenjang' => 'Keterampilan - Terampil'],
            ['nama' => 'Bidan',                                       'jenis' => 'Fungsional', 'kelas' => 7, 'jenjang' => 'Keterampilan - Terampil'],
            // Pelaksana
            ['nama' => 'Pengelola Keuangan',                          'jenis' => 'Pelaksana',  'kelas' => 6, 'jenjang' => 'Pelaksana'],
            ['nama' => 'Pengadministrasi Umum',                       'jenis' => 'Pelaksana',  'kelas' => 5, 'jenjang' => 'Pelaksana'],
        ];

        foreach ($dinkesJabatan as $i => $j) {
            Jabatan::create([
                'nama_jabatan'  => $j['nama'],
                'kode_jabatan'  => sprintf('DINKES-%03d', $i + 1),
                'jenis_jabatan' => $j['jenis'],
                'kelas_jabatan' => $j['kelas'],
                'jenjang'       => $j['jenjang'],
                'opd_id'        => $dinkes->id,
            ]);
        }

        // ============================================================
        // PUPR — Dinas Pekerjaan Umum dan Penataan Ruang
        // ============================================================
        $puprJabatan = [
            ['nama' => 'Kepala Dinas',                                'jenis' => 'Struktural', 'kelas' => 15, 'jenjang' => 'Pimpinan Tinggi Pratama'],
            ['nama' => 'Sekretaris Dinas',                            'jenis' => 'Struktural', 'kelas' => 13, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Bidang',                               'jenis' => 'Struktural', 'kelas' => 12, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Seksi',                                'jenis' => 'Struktural', 'kelas' => 9,  'jenjang' => 'Pengawas'],
            ['nama' => 'Kepala Seksi',                                'jenis' => 'Struktural', 'kelas' => 9,  'jenjang' => 'Pengawas'],
            ['nama' => 'Analis Kebijakan',                            'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Muda'],
            ['nama' => 'Operator Komputer',                           'jenis' => 'Pelaksana',  'kelas' => 5, 'jenjang' => 'Pelaksana'],
            ['nama' => 'Pengadministrasi Umum',                       'jenis' => 'Pelaksana',  'kelas' => 5, 'jenjang' => 'Pelaksana'],
        ];

        foreach ($puprJabatan as $i => $j) {
            Jabatan::create([
                'nama_jabatan'  => $j['nama'],
                'kode_jabatan'  => sprintf('PUPR-%03d', $i + 1),
                'jenis_jabatan' => $j['jenis'],
                'kelas_jabatan' => $j['kelas'],
                'jenjang'       => $j['jenjang'],
                'opd_id'        => $pupr->id,
            ]);
        }

        // ============================================================
        // BKPSDM — Badan Kepegawaian dan Pengembangan SDM
        // ============================================================
        $bkpsdmJabatan = [
            ['nama' => 'Kepala Badan',                                'jenis' => 'Struktural', 'kelas' => 15, 'jenjang' => 'Pimpinan Tinggi Pratama'],
            ['nama' => 'Sekretaris Badan',                            'jenis' => 'Struktural', 'kelas' => 13, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Bidang',                               'jenis' => 'Struktural', 'kelas' => 12, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Sub Bidang',                           'jenis' => 'Struktural', 'kelas' => 10, 'jenjang' => 'Pengawas'],
            ['nama' => 'Kepala Sub Bidang',                           'jenis' => 'Struktural', 'kelas' => 10, 'jenjang' => 'Pengawas'],
            ['nama' => 'Analis Kepegawaian',                          'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Muda'],
            ['nama' => 'Pranata Komputer',                            'jenis' => 'Fungsional', 'kelas' => 8, 'jenjang' => 'Ahli Pertama'],
            ['nama' => 'Pengelola Kepegawaian',                       'jenis' => 'Pelaksana',  'kelas' => 6, 'jenjang' => 'Pelaksana'],
            ['nama' => 'Pengadministrasi Umum',                       'jenis' => 'Pelaksana',  'kelas' => 5, 'jenjang' => 'Pelaksana'],
        ];

        foreach ($bkpsdmJabatan as $i => $j) {
            Jabatan::create([
                'nama_jabatan'  => $j['nama'],
                'kode_jabatan'  => sprintf('BKPSDM-%03d', $i + 1),
                'jenis_jabatan' => $j['jenis'],
                'kelas_jabatan' => $j['kelas'],
                'jenjang'       => $j['jenjang'],
                'opd_id'        => $bkpsdm->id,
            ]);
        }

        // ============================================================
        // SETDA — Sekretariat Daerah
        // ============================================================
        $setdaJabatan = [
            ['nama' => 'Sekretaris Daerah',                           'jenis' => 'Struktural', 'kelas' => 15, 'jenjang' => 'Pimpinan Tinggi Pratama'],
            ['nama' => 'Asisten Pemerintahan',                        'jenis' => 'Struktural', 'kelas' => 13, 'jenjang' => 'Administrator'],
            ['nama' => 'Asisten Administrasi Umum',                   'jenis' => 'Struktural', 'kelas' => 13, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Bagian',                               'jenis' => 'Struktural', 'kelas' => 12, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Bagian',                               'jenis' => 'Struktural', 'kelas' => 12, 'jenjang' => 'Administrator'],
            ['nama' => 'Kepala Sub Bagian',                           'jenis' => 'Struktural', 'kelas' => 10, 'jenjang' => 'Pengawas'],
            ['nama' => 'Kepala Sub Bagian',                           'jenis' => 'Struktural', 'kelas' => 10, 'jenjang' => 'Pengawas'],
            ['nama' => 'Operator Komputer',                           'jenis' => 'Pelaksana',  'kelas' => 5, 'jenjang' => 'Pelaksana'],
            ['nama' => 'Bendahara',                                   'jenis' => 'Pelaksana',  'kelas' => 6, 'jenjang' => 'Pelaksana'],
            ['nama' => 'Pengadministrasi Umum',                       'jenis' => 'Pelaksana',  'kelas' => 5, 'jenjang' => 'Pelaksana'],
        ];

        foreach ($setdaJabatan as $i => $j) {
            Jabatan::create([
                'nama_jabatan'  => $j['nama'],
                'kode_jabatan'  => sprintf('SETDA-%03d', $i + 1),
                'jenis_jabatan' => $j['jenis'],
                'kelas_jabatan' => $j['kelas'],
                'jenjang'       => $j['jenjang'],
                'opd_id'        => $setda->id,
            ]);
        }
    }
}
