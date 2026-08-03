<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\KebutuhanPegawai;
use App\Models\Sotk;
use Illuminate\Database\Seeder;

class KebutuhanSeeder extends Seeder
{
    public function run(): void
    {
        $sotkEntries = Sotk::with('jabatan', 'unor')->get();

        foreach ($sotkEntries as $sotk) {
            if (!$sotk->jabatan) continue;

            $jumlah = $this->resolveKebutuhan($sotk->unor->kode_unor, $sotk->jabatan->nama_jabatan, $sotk->jabatan->jenjang);

            KebutuhanPegawai::create([
                'unor_id'    => $sotk->unor_id,
                'jabatan_id' => $sotk->jabatan_id,
                'tahun'      => null,
                'jumlah'     => $jumlah,
            ]);
        }
    }

    /**
     * Tentukan jumlah kebutuhan berdasarkan formasi di Organisasi Dummy.md.
     */
    private function resolveKebutuhan(string $kodeUnor, string $namaJabatan, ?string $jenjang): int
    {
        // BKPSDM — Bidang PPI: Pranata Komputer Ahli Pertama = 3 formasi
        if ($kodeUnor === 'BKPSDM-BID-PPI' && $namaJabatan === 'Pranata Komputer' && $jenjang === 'Ahli Pertama') {
            return 3;
        }

        // Dinkes — Bidang Yankes: Dokter Ahli Pertama = 2 formasi
        if ($kodeUnor === 'DINKES-BID-YANKES' && $namaJabatan === 'Dokter' && $jenjang === 'Ahli Pertama') {
            return 2;
        }

        // Dinkes — Bidang Yankes: Perawat Ahli Pertama = 3 formasi
        if ($kodeUnor === 'DINKES-BID-YANKES' && $namaJabatan === 'Perawat' && $jenjang === 'Ahli Pertama') {
            return 3;
        }

        // Default: 1
        return 1;
    }
}
