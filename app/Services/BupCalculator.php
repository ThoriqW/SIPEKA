<?php

namespace App\Services;

class BupCalculator
{
    /**
     * Hitung Batas Usia Pensiun berdasarkan jenjang, jenis kepegawaian, dan nama jabatan.
     *
     * Aturan:
     *   65  jika jenjang = "Ahli Utama"
     *   60  jika jenjang ∈ {"Ahli Madya", "Pimpinan Tinggi Pratama"}
     *        ATAU (jenis_kepegawaian = "PPPK" DAN (jenjang = "Guru" ATAU nama jabatan mengandung "Guru"))
     *   58  selain itu
     *
     * @param string      $jenjang           Jenjang pegawai (dari tabel pegawai / jabatan)
     * @param string      $jenisKepegawaian  PNS | PPPK
     * @param string|null $namaJabatan       Nama jabatan (opsional, untuk deteksi Guru via nama)
     */
    public function hitungBup(string $jenjang, string $jenisKepegawaian, ?string $namaJabatan = null): int
    {
        if ($jenjang === 'Ahli Utama') {
            return 65;
        }

        if (in_array($jenjang, ['Ahli Madya', 'Pimpinan Tinggi Pratama'], true)) {
            return 60;
        }

        if ($jenisKepegawaian === 'PPPK' && $this->isGuru($jenjang, $namaJabatan)) {
            return 60;
        }

        return 58;
    }

    /**
     * Deteksi apakah pegawai adalah Guru berdasarkan jenjang atau nama jabatan.
     * Karena enum Jenjang tidak memiliki case 'Guru', deteksi dilakukan via:
     * 1. Jenjang = 'Guru' (jika suatu saat ditambahkan)
     * 2. Nama jabatan mengandung kata "Guru" (case-insensitive)
     */
    private function isGuru(string $jenjang, ?string $namaJabatan): bool
    {
        if ($jenjang === 'Guru') {
            return true;
        }

        if ($namaJabatan !== null && str_contains(mb_strtolower($namaJabatan), 'guru')) {
            return true;
        }

        return false;
    }

    /**
     * Hitung tanggal pensiun = tanggal_lahir + BUP tahun.
     */
    public function hitungTanggalPensiun(\DateTimeInterface|string $tanggalLahir, string $jenjang, string $jenisKepegawaian, ?string $namaJabatan = null): \DateTimeImmutable
    {
        $bup = $this->hitungBup($jenjang, $jenisKepegawaian, $namaJabatan);

        if (is_string($tanggalLahir)) {
            $tanggalLahir = new \DateTimeImmutable($tanggalLahir);
        } elseif (!$tanggalLahir instanceof \DateTimeImmutable) {
            // Konversi Carbon / DateTime ke DateTimeImmutable
            $tanggalLahir = \DateTimeImmutable::createFromInterface($tanggalLahir);
        }

        return $tanggalLahir->modify("+{$bup} years");
    }
}
