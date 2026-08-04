<?php

namespace App\Services;

class BupCalculator
{
    /**
     * Hitung Batas Usia Pensiun berdasarkan atribut jabatan.
     *
     * Aturan BUP:
     *   60  jika Jabatan Guru (semua jenjang, PNS & PPPK)
     *   65  jika jenjang = "Ahli Utama"
     *   60  jika jenjang ∈ {"Ahli Madya", "Pimpinan Tinggi Pratama"}
     *   58  selain itu
     *
     * @param string      $jenjang     Jenjang jabatan (dari tabel jabatan)
     * @param string|null $namaJabatan Nama jabatan (format: "Guru - Matematika" atau "Pranata Komputer")
     */
    public function hitungBup(string $jenjang, ?string $namaJabatan = null): int
    {
        // Guru → 60 tahun (mengoverride semua aturan jenjang, termasuk Ahli Utama)
        if ($this->isGuru($jenjang, $namaJabatan)) {
            return 60;
        }

        if ($jenjang === 'Ahli Utama') {
            return 65;
        }

        if (in_array($jenjang, ['Ahli Madya', 'Pimpinan Tinggi Pratama'], true)) {
            return 60;
        }

        return 58;
    }

    /**
     * Hitung tanggal pensiun = tanggal_lahir + BUP tahun.
     *
     * @todo Untuk PPPK, logika dapat diperluas menggunakan $jenisKepegawaian
     *       jika data tanggal_akhir_kontrak sudah tersedia:
     *       if (PPPK) { return min(tanggal_akhir_kontrak, tanggal_lahir + 70 tahun) }
     *
     * @param \DateTimeInterface|string $tanggalLahir
     * @param string                   $jenjang          Jenjang jabatan
     * @param string                   $jenisKepegawaian PNS | PPPK (saat ini belum digunakan)
     * @param string|null              $namaJabatan      Nama jabatan (untuk deteksi Guru)
     */
    public function hitungTanggalPensiun(
        \DateTimeInterface|string $tanggalLahir,
        string $jenjang,
        string $jenisKepegawaian,
        ?string $namaJabatan = null
    ): \DateTimeImmutable {
        $bup = $this->hitungBup($jenjang, $namaJabatan);

        if (is_string($tanggalLahir)) {
            $tanggalLahir = new \DateTimeImmutable($tanggalLahir);
        } elseif (!$tanggalLahir instanceof \DateTimeImmutable) {
            // Konversi Carbon / DateTime ke DateTimeImmutable
            $tanggalLahir = \DateTimeImmutable::createFromInterface($tanggalLahir);
        }

        return $tanggalLahir->modify("+{$bup} years");
    }

    /**
     * Deteksi apakah jabatan adalah Guru berdasarkan jenjang atau nama jabatan.
     *
     * Format nama_jabatan di tabel jabatan:
     *   "Guru - Guru Bahasa Indonesia" → parent = "Guru"
     *   "Guru - Matematika"            → parent = "Guru"
     *   "Pranata Komputer"             → parent = "Pranata Komputer" (bukan Guru)
     *
     * @param string      $jenjang     Jenjang jabatan
     * @param string|null $namaJabatan Nama jabatan lengkap (termasuk sub-jabatan jika ada)
     */
    private function isGuru(string $jenjang, ?string $namaJabatan): bool
    {
        // Deteksi via jenjang (safety net)
        if ($jenjang === 'Guru') {
            return true;
        }

        if ($namaJabatan === null) {
            return false;
        }

        // Format: "Guru - Guru Bahasa Indonesia" → ambil bagian sebelum " - "
        $parentName = explode(' - ', $namaJabatan)[0];

        return $parentName === 'Guru';
    }
}
