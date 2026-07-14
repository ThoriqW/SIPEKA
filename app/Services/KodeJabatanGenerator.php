<?php

namespace App\Services;

use App\Enums\JenisJabatan;
use App\Models\Jabatan;
use App\Models\Opd;
use Illuminate\Support\Facades\DB;

class KodeJabatanGenerator
{
    /**
     * Generate kode jabatan otomatis dengan format:
     * {KODE_OPD}-{SINGKATAN_JENIS}-{NOMOR_3_DIGIT}
     *
     * Contoh: DIKBUD-STR-001, DINKES-FNG-001, PUPR-PLK-001
     *
     * Nomor urut dihitung per OPD per jenis jabatan.
     * Menggunakan DB transaction + lockForUpdate untuk mencegah
     * race condition pada concurrent request.
     */
    public function generate(string $kodeOpd, string $jenisJabatan): string
    {
        $singkatan = JenisJabatan::from($jenisJabatan)->singkatan();
        $prefix = "{$kodeOpd}-{$singkatan}-";

        return DB::transaction(function () use ($prefix) {
            // Cari nomor urut tertinggi untuk prefix ini
            $maxKode = Jabatan::where('kode_jabatan', 'LIKE', "{$prefix}%")
                ->lockForUpdate()
                ->orderByRaw('LENGTH(kode_jabatan) DESC')
                ->orderBy('kode_jabatan', 'DESC')
                ->value('kode_jabatan');

            $next = 1;
            if ($maxKode && preg_match('/-(\d+)$/', $maxKode, $m)) {
                $next = (int) $m[1] + 1;
            }

            return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        });
    }
}
