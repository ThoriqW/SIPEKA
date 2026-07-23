<?php

namespace Database\Seeders;

use App\Models\JabatanAsn;
use App\Services\KodeNodeGenerator;
use Illuminate\Database\Seeder;

/**
 * Seeder mengisi tabel jabatan_asn — katalog jabatan kepegawaian ASN.
 *
 * Data: nama jabatan + jenis_jabatan + jenjang.
 * Setiap kombinasi menghasilkan satu record jabatan_asn
 * yang dapat dipilih saat mengisi data pegawai.
 */
class JabatanAsnSeeder extends Seeder
{
    public function run(): void
    {
        if (JabatanAsn::exists()) {
            return;
        }

        $generator = app(KodeNodeGenerator::class);

        // Data: [nama_jabatan, jenis_jabatan, [daftar_jenjang]]
        $data = [
            // === STRUKTURAL ===
            ['Kepala Dinas', 'Struktural', ['Pimpinan Tinggi Pratama']],
            ['Kepala Badan', 'Struktural', ['Pimpinan Tinggi Pratama']],
            ['Kepala Bagian', 'Struktural', ['Administrator']],
            ['Kepala Bidang', 'Struktural', ['Administrator']],
            ['Kepala Sub Bagian', 'Struktural', ['Pengawas']],
            ['Kepala Sub Bidang', 'Struktural', ['Pengawas']],
            ['Kepala Seksi', 'Struktural', ['Pengawas']],

            // === FUNGSIONAL (Ahli) ===
            ['Guru', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Dokter', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Dokter Gigi', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Perawat', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Bidan', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Apoteker', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Pranata Komputer', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Analis SDM Aparatur', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Analis Kebijakan', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Auditor', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Penyuluh', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Arsiparis', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Pustakawan', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Statistisi', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Perencana', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Pengawas Sekolah', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],
            ['Widyaprada', 'Fungsional', ['Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama']],

            // === FUNGSIONAL (Keterampilan) ===
            ['Perawat', 'Fungsional', ['Keterampilan - Pemula', 'Keterampilan - Terampil', 'Keterampilan - Mahir', 'Keterampilan - Penyelia']],
            ['Bidan', 'Fungsional', ['Keterampilan - Pemula', 'Keterampilan - Terampil', 'Keterampilan - Mahir', 'Keterampilan - Penyelia']],
            ['Pranata Komputer', 'Fungsional', ['Keterampilan - Pemula', 'Keterampilan - Terampil', 'Keterampilan - Mahir', 'Keterampilan - Penyelia']],

            // === PELAKSANA ===
            ['Pengelola Keuangan', 'Pelaksana', ['Pelaksana']],
            ['Pengelola Barang', 'Pelaksana', ['Pelaksana']],
            ['Pengelola Kepegawaian', 'Pelaksana', ['Pelaksana']],
            ['Pengelola Data', 'Pelaksana', ['Pelaksana']],
            ['Pengadministrasi', 'Pelaksana', ['Pelaksana']],
            ['Operator Sekolah', 'Pelaksana', ['Pelaksana']],
            ['Petugas Keamanan', 'Pelaksana', ['Pelaksana']],
            ['Petugas Kebersihan', 'Pelaksana', ['Pelaksana']],
            ['Pramu Bakti', 'Pelaksana', ['Pelaksana']],
        ];

        foreach ($data as [$nama, $jenis, $jenjangList]) {
            foreach ($jenjangList as $jenjang) {
                // Format nama_jabatan_asn
                if ($jenis === 'Pelaksana') {
                    $namaJasn = $nama;
                } elseif (str_contains($jenjang, 'Keterampilan')) {
                    $namaJasn = $nama . ' ' . $jenjang;
                } else {
                    $namaJasn = $nama . ' ' . $jenjang;
                }

                JabatanAsn::create([
                    'nama_jabatan_asn' => $namaJasn,
                    'jenis_jabatan' => $jenis,
                    'jenjang' => $jenjang,
                    'parent_id' => null,
                    'kode_jabatan_asn' => $generator->generateKodeJabatanAsn(),
                ]);
            }
        }
    }
}
