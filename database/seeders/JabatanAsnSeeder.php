<?php

namespace Database\Seeders;

use App\Models\JabatanAsn;
use App\Models\MasterJabatan;
use App\Services\KodeNodeGenerator;
use Illuminate\Database\Seeder;

/**
 * Seeder mengisi tabel jabatan_asn dari data master_jabatan.
 *
 * Untuk setiap master_jabatan root-level (parent_id = null),
 * buat beberapa varian jenjang yang relevan.
 *
 * Contoh:
 *   - "Guru" → Guru Ahli Pertama, Guru Ahli Muda, Guru Ahli Madya
 *   - "Dokter" → Dokter Ahli Pertama, Dokter Ahli Madya
 *   - "Perawat" → Perawat - Terampil, Perawat - Mahir, Perawat - Penyelia
 */
class JabatanAsnSeeder extends Seeder
{
    public function run(): void
    {
        // Cek apakah sudah ada data — jangan duplikasi
        if (JabatanAsn::exists()) {
            return;
        }

        $generator = app(KodeNodeGenerator::class);

        // Mapping jenis jabatan → jenjang yang tersedia
        $jenjangByJenis = [
            'Struktural' => [
                'Pengawas',
                'Administrator',
                'Pimpinan Tinggi Pratama',
            ],
            'Fungsional' => [
                'Ahli Pertama',
                'Ahli Muda',
                'Ahli Madya',
                'Ahli Utama',
                'Keterampilan - Pemula',
                'Keterampilan - Terampil',
                'Keterampilan - Mahir',
                'Keterampilan - Penyelia',
            ],
            'Pelaksana' => [
                'Pelaksana',
            ],
        ];

        // Ambil semua master_jabatan root-level
        $roots = MasterJabatan::whereNull('parent_id')
            ->orderBy('jenis_jabatan')
            ->orderBy('nama_jabatan')
            ->get();

        foreach ($roots as $root) {
            $jenjangList = $jenjangByJenis[$root->jenis_jabatan] ?? [$root->jenis_jabatan];

            // Untuk setiap kombinasi nama jabatan + jenjang, buat satu record
            foreach ($jenjangList as $jenjang) {
                // Tentukan nama_jabatan_asn
                if ($root->jenis_jabatan === 'Pelaksana') {
                    // Pelaksana: cukup nama jabatan saja
                    $namaJasn = $root->nama_jabatan;
                } elseif (str_contains($jenjang, 'Keterampilan')) {
                    // Keterampilan: "Perawat - Terampil"
                    $namaJasn = $root->nama_jabatan . ' ' . $jenjang;
                } else {
                    // Fungsional/Struktural: "Guru Ahli Pertama", "Dokter Ahli Madya"
                    $namaJasn = $root->nama_jabatan . ' ' . $jenjang;
                }

                JabatanAsn::create([
                    'nama_jabatan_asn' => $namaJasn,
                    'jenis_jabatan' => $root->jenis_jabatan,
                    'jenjang' => $jenjang,
                    'parent_id' => null,
                    'kode_jabatan_asn' => $generator->generateKodeJabatanAsn(),
                ]);

                // Proses anak-anak master_jabatan (sub-spesialisasi)
                $children = MasterJabatan::where('parent_id', $root->id)->get();
                foreach ($children as $child) {
                    if ($root->jenis_jabatan === 'Pelaksana') {
                        $childNama = $child->nama_jabatan;
                    } elseif (str_contains($jenjang, 'Keterampilan')) {
                        $childNama = $child->nama_jabatan . ' ' . $jenjang;
                    } else {
                        $childNama = $child->nama_jabatan . ' ' . $jenjang;
                    }

                    $parent = JabatanAsn::where('nama_jabatan_asn', $namaJasn)
                        ->where('jenis_jabatan', $root->jenis_jabatan)
                        ->first();

                    JabatanAsn::create([
                        'nama_jabatan_asn' => $childNama,
                        'jenis_jabatan' => $root->jenis_jabatan,
                        'jenjang' => $jenjang,
                        'parent_id' => $parent?->id,
                        'kode_jabatan_asn' => $generator->generateKodeJabatanAsn(),
                    ]);
                }
            }
        }
    }
}
