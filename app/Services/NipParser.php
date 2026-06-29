<?php

namespace App\Services;

class NipParser
{
    /**
     * Ekstrak tanggal lahir dari 8 digit pertama NIP (format YYYYMMDD).
     * Format NIP 18-digit standar BKN: YYYYMMDDYYYYMMDDXX.
     */
    public function extractTanggalLahir(string $nip): ?string
    {
        $nip = preg_replace('/\s+/', '', $nip);

        if (strlen($nip) !== 18 || !ctype_digit($nip)) {
            return null;
        }

        $tahun = (int) substr($nip, 0, 4);
        $bulan = (int) substr($nip, 4, 2);
        $hari = (int) substr($nip, 6, 2);

        if (!checkdate($bulan, $hari, $tahun)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $tahun, $bulan, $hari);
    }
}
