<?php

namespace App\Services;

class BupCalculator
{
    /**
     * Hitung Batas Usia Pensiun berdasarkan jenjang dan jenis kepegawaian.
     *
     * Aturan:
     *   65  jika jenjang = "Ahli Utama"
     *   60  jika jenjang ∈ {"Ahli Madya", "Pimpinan Tinggi"}
     *        ATAU (jenis_kepegawaian = "PPPK" DAN jenjang = "Guru")
     *   58  selain itu
     */
    public function hitungBup(string $jenjang, string $jenisKepegawaian): int
    {
        if ($jenjang === 'Ahli Utama') {
            return 65;
        }

        if (in_array($jenjang, ['Ahli Madya', 'Pimpinan Tinggi'], true)) {
            return 60;
        }

        if ($jenisKepegawaian === 'PPPK' && $jenjang === 'Guru') {
            return 60;
        }

        return 58;
    }

    /**
     * Hitung tanggal pensiun = tanggal_lahir + BUP tahun.
     */
    public function hitungTanggalPensiun(\DateTimeInterface|string $tanggalLahir, string $jenjang, string $jenisKepegawaian): \DateTimeImmutable
    {
        $bup = $this->hitungBup($jenjang, $jenisKepegawaian);

        if (is_string($tanggalLahir)) {
            $tanggalLahir = new \DateTimeImmutable($tanggalLahir);
        }

        return $tanggalLahir->modify("+{$bup} years");
    }
}
