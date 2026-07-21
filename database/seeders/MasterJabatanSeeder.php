<?php

namespace Database\Seeders;

use App\Models\MasterJabatan;
use Illuminate\Database\Seeder;

class MasterJabatanSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // STRUKTURAL
        // ============================================================
        $struktural = [
            'Kepala Dinas',
            'Sekretaris Dinas',
            'Kepala Bidang',
            'Kepala Sub Bagian',
            'Kepala Sub Bidang',
            'Kepala Badan',
            'Sekretaris Badan',
            'Kepala UPTD',
            'Kepala Bagian',
            'Kepala Seksi',
            'Camat',
            'Sekretaris Camat',
            'Lurah',
            'Sekretaris Lurah',
            'Sekretaris Daerah',
            'Asisten Pemerintahan',
            'Asisten Perekonomian',
            'Asisten Administrasi Umum',
        ];

        foreach ($struktural as $nama) {
            MasterJabatan::create([
                'nama_jabatan' => $nama,
                'jenis_jabatan' => 'Struktural',
                'parent_id' => null,
            ]);
        }

        // ============================================================
        // PELAKSANA
        // ============================================================
        $pelaksana = [
            'Pengadministrasi Umum',
            'Pengelola Keuangan',
            'Pengelola Barang',
            'Pengelola Kepegawaian',
            'Operator Komputer',
            'Bendahara',
            'Sekretaris Pimpinan',
            'Petugas Keamanan',
            'Pramu Kantor',
            'Pengemudi',
        ];

        foreach ($pelaksana as $nama) {
            MasterJabatan::create([
                'nama_jabatan' => $nama,
                'jenis_jabatan' => 'Pelaksana',
                'parent_id' => null,
            ]);
        }

        // ============================================================
        // FUNGSIONAL — TENAGA KESEHATAN (26 dari sheet NAKES)
        // Semua flat (parent_id = null), hanya Dokter punya sub-jabatan
        // ============================================================
        $nakes = [
            'Administrator Kesehatan',
            'Apoteker',
            'Asisten Apoteker',
            'Asisten Penata Anestesi',
            'Bidan',
            'Dokter',               // <-- ini yang punya sub-jabatan
            'Dokter Gigi',
            'Entomolog Kesehatan',
            'Epidemiolog Kesehatan',
            'Fisikawan Medis',
            'Fisioterapis',
            'Nutrisionis',
            'Pembimbing Kesehatan Kerja',
            'Penata Anestesi',
            'Perawat',
            'Perekam Medis',
            'Pranata Laboratorium Kesehatan',
            'Psikolog Klinis',
            'Radiografer',
            'Teknisi Elektromedis',
            'Teknisi Transfusi Darah',
            'Tenaga Promosi Kesehatan dan Ilmu Perilaku',
            'Tenaga Sanitasi Lingkungan',
            'Terapis Gigi dan Mulut',
            'Okupasi Terapis',
            'Terapis Wicara',
        ];

        $dokterId = null;
        foreach ($nakes as $nama) {
            $entry = MasterJabatan::create([
                'nama_jabatan' => $nama,
                'jenis_jabatan' => 'Fungsional',
                'parent_id' => null,
            ]);
            if ($nama === 'Dokter') {
                $dokterId = $entry->id;
            }
        }

        // SUB-JABATAN DOKTER SPESIALIS (40 dari sheet DOKTER SPESIALIS)
        $dokterSpesialis = [
            'Dokter Umum',
            'Dokter Gigi Umum',
            'Dokter Pendidik Klinis',
            'Dokter Spesialis Anak',
            'Dokter Spesialis Anastesi',
            'Dokter Spesialis Bedah',
            'Dokter Spesialis Bedah Anak',
            'Dokter Spesialis Bedah Plastik',
            'Dokter Spesialis Bedah Saraf',
            'Dokter Spesialis Bedah Toraks Kardiovaskuler',
            'Dokter Spesialis Dermatologi dan Venereologi',
            'Dokter Spesialis Fisik dan Rehabilitasi',
            'Dokter Spesialis Forensik dan Medikolegal',
            'Dokter Spesialis Gizi Klinik',
            'Dokter Spesialis Jantung dan Pembuluh Darah',
            'Dokter Spesialis Kesehatan Gigi Anak',
            'Dokter Spesialis Kesehatan Jiwa',
            'Dokter Spesialis Konservasi Gigi',
            'Dokter Spesialis Kulit dan Kelamin',
            'Dokter Spesialis Mata',
            'Dokter Spesialis Mikrobiologi Klinik',
            'Dokter Spesialis Obgyn',
            'Dokter Spesialis Orthopedi',
            'Dokter Spesialis Patologi Klinik',
            'Dokter Spesialis Paru/Pulmonologi dan Kedokteran Respirasi',
            'Dokter Spesialis Patologi Anatomi',
            'Dokter Spesialis Penyakit Dalam',
            'Dokter Spesialis Radiologi',
            'Dokter Spesialis Saraf/Neurologi',
            'Dokter Spesialis THT-KL',
            'Dokter Spesialis Urologi',
            'Dokter Sub Spesialis Gastroentero Hepatologi',
            'Dokter Sub Spesialis Neurologi-Konsultan Neurointervensi',
            'Dokter Sub Spesialis Obgyn-Konsultan Obstetri Ginekologi Sosial',
            'Dokter Sub Spesialis Penyakit Dalam-Gastroentero Hemapatologi',
            'Dokter Sub Spesialis Penyakit Dalam-Ginjal-Hipertensi',
            'Dokter Sub Spesialis Penyakit Dalam-Hermatologi-Onkologi Medik',
            'Dokter Sub Spesialis Penyakit Dalam-Kardiovaskular',
            'Dokter Sub Spesialis Penyakit Dalam-Psikosomatik',
            'Dokter Sub Spesialis Radiologi-Radiologi Intervensional',
        ];

        foreach ($dokterSpesialis as $nama) {
            MasterJabatan::create([
                'nama_jabatan' => $nama,
                'jenis_jabatan' => 'Fungsional',
                'parent_id' => $dokterId,
            ]);
        }

        // ============================================================
        // FUNGSIONAL — TENAGA GURU
        // Semua Guru memiliki sub-jabatan mapel (17 dari sheet MAPEL_GURU)
        // ============================================================
        $guru = MasterJabatan::create([
            'nama_jabatan' => 'Guru',
            'jenis_jabatan' => 'Fungsional',
            'parent_id' => null,
        ]);

        $mapelGuru = [
            'Guru Kelas',
            'Guru Bahasa Indonesia',
            'Guru Bahasa Inggris',
            'Guru Bimbingan dan Konseling',
            'Guru IPA',
            'Guru IPS',
            'Guru Matematika',
            'Guru Pendidikan Agama Budha',
            'Guru Pendidikan Agama Hindu',
            'Guru Pendidikan Agama Islam',
            'Guru Pendidikan Agama Khatolik',
            'Guru Pendidikan Agama Kristen',
            'Guru PENJASORKES',
            'Guru PPKN',
            'Guru Prakarya dan Kewirausahaan',
            'Guru Seni Budaya',
            'Guru TIK',
        ];

        foreach ($mapelGuru as $nama) {
            MasterJabatan::create([
                'nama_jabatan' => $nama,
                'jenis_jabatan' => 'Fungsional',
                'parent_id' => $guru->id,
            ]);
        }

        // ============================================================
        // FUNGSIONAL — TENAGA TEKNIS (flat, tidak ada sub-jabatan)
        // ============================================================
        $teknis = [
            'Analis Kebijakan',
            'Analis Kepegawaian',
            'Arsiparis',
            'Pustakawan',
            'Pranata Komputer',
            'Statistisi',
            'Perencana',
            'Auditor',
            'Peneliti',
            'Widyaiswara',
            'Penyuluh Pertanian',
            'Penyuluh Perikanan',
            'Penyuluh Keluarga Berencana',
            'Pengawas Ketenagakerjaan',
            'Pengawas Lingkungan Hidup',
            'Mediator Hubungan Industrial',
            'Penggerak Swadaya Masyarakat',
            'Pelatih Olahraga',
            'Pamong Budaya',
            'Pranata Hubungan Masyarakat',
        ];

        foreach ($teknis as $nama) {
            MasterJabatan::create([
                'nama_jabatan' => $nama,
                'jenis_jabatan' => 'Fungsional',
                'parent_id' => null,
            ]);
        }
    }
}
