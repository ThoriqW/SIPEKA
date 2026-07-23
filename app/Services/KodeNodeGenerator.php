<?php

namespace App\Services;

use App\Models\JabatanAsn;
use App\Models\NodeOrganisasi;
use Illuminate\Support\Facades\DB;

/**
 * Generate kode untuk unit organisasi dan jabatan ASN.
 *
 * Format kode POSISI: {KODE_UNIT_INDUK}-{SINGKATAN_POSISI}-{NOMOR}
 *   Contoh: BKPSDMD-PRANKOM-001, DINKES-DOKTER-002
 *
 * Format kode UNIT: {KODE_UNIT}
 *   Contoh: BKPSDMD, DINKES
 *
 * Format kode Jabatan ASN: JASN-{NOMOR_5_DIGIT}
 *   Contoh: JASN-00001
 */
class KodeNodeGenerator
{
    /**
     * Generate kode untuk node POSISI.
     *
     * Format: {KODE_UNIT_INDUK}-{SINGKATAN_NAMA}-{NOMOR_3_DIGIT}
     * SINGKATAN diambil dari 8 karakter pertama nama posisi (uppercase, tanpa spasi).
     */
    public function generateKodePosisi(NodeOrganisasi $parentUnit, string $namaPosisi): string
    {
        // Cari kode unit induk (ke atas sampai dapat UNIT dengan kode)
        $kodeUnit = $this->resolveKodeUnit($parentUnit);

        $singkatan = $this->singkatanNama($namaPosisi);
        $prefix = "{$kodeUnit}-{$singkatan}-";

        return DB::transaction(function () use ($prefix) {
            $maxKode = NodeOrganisasi::where('kode', 'LIKE', "{$prefix}%")
                ->lockForUpdate()
                ->orderByRaw('LENGTH(kode) DESC')
                ->orderBy('kode', 'DESC')
                ->value('kode');

            $next = 1;
            if ($maxKode && preg_match('/-(\d+)$/', $maxKode, $m)) {
                $next = (int) $m[1] + 1;
            }

            return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Generate kode untuk node UNIT.
     * Format: singkatan dari nama unit (uppercase, maks 12 karakter).
     */
    public function generateKodeUnit(string $namaUnit): string
    {
        return DB::transaction(function () use ($namaUnit) {
            $base = $this->singkatanNama($namaUnit);

            // Cek apa sudah ada kode serupa
            $exists = NodeOrganisasi::where('kode', $base)->exists();
            if (!$exists) {
                return $base;
            }

            // Tambahkan suffix angka jika duplikat
            $i = 2;
            while (NodeOrganisasi::where('kode', "{$base}-{$i}")->exists()) {
                $i++;
            }
            return "{$base}-{$i}";
        });
    }

    /**
     * Generate kode untuk Jabatan ASN.
     * Format: JASN-{NOMOR_5_DIGIT}
     */
    public function generateKodeJabatanAsn(): string
    {
        return DB::transaction(function () {
            $maxKode = JabatanAsn::where('kode_jabatan_asn', 'LIKE', 'JASN-%')
                ->lockForUpdate()
                ->orderByRaw('LENGTH(kode_jabatan_asn) DESC')
                ->orderBy('kode_jabatan_asn', 'DESC')
                ->value('kode_jabatan_asn');

            $next = 1;
            if ($maxKode && preg_match('/JASN-(\d+)$/', $maxKode, $m)) {
                $next = (int) $m[1] + 1;
            }

            return 'JASN-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Resolve kode unit dari parent node (naik sampai ketemu UNIT dengan kode).
     */
    private function resolveKodeUnit(NodeOrganisasi $parent): string
    {
        if ($parent->kode) {
            return $parent->kode;
        }

        if ($parent->parent) {
            return $this->resolveKodeUnit($parent->parent);
        }

        // Fallback: gunakan singkatan nama parent
        return $this->singkatanNama($parent->nama);
    }

    /**
     * Buat singkatan dari nama: uppercase, hapus spasi, maks 8 karakter.
     * Contoh: "Pranata Komputer" → "PRANATAK"
     */
    private function singkatanNama(string $nama): string
    {
        $cleaned = preg_replace('/[^a-zA-Z0-9]/', '', $nama);
        return strtoupper(substr($cleaned, 0, 8));
    }
}
