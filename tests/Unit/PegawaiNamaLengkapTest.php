<?php

namespace Tests\Unit;

use App\Models\Pegawai;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Perakitan nama lengkap pegawai.
 *
 * Ini satu-satunya tempat nama pegawai dirakit, jadi aturan komanya diuji di
 * sini alih-alih di setiap halaman yang menampilkannya.
 */
class PegawaiNamaLengkapTest extends TestCase
{
    private function pegawai(?string $gelarDepan, ?string $gelarBelakang, string $nama = 'Abdul Muis'): Pegawai
    {
        return new Pegawai([
            'nama' => $nama,
            'gelar_depan' => $gelarDepan,
            'gelar_belakang' => $gelarBelakang,
        ]);
    }

    #[Test]
    public function tanpa_gelar_menghasilkan_nama_apa_adanya()
    {
        $this->assertSame('Abdul Muis', $this->pegawai(null, null)->namaLengkap());
        $this->assertSame('Abdul Muis', $this->pegawai('', '')->namaLengkap());
    }

    #[Test]
    public function gelar_depan_saja()
    {
        $this->assertSame('Drs. Abdul Muis', $this->pegawai('Drs.', null)->namaLengkap());
    }

    #[Test]
    public function gelar_belakang_saja()
    {
        $this->assertSame('Abdul Muis, S.E.', $this->pegawai(null, 'S.E.')->namaLengkap());
    }

    #[Test]
    public function gelar_depan_dan_belakang()
    {
        $this->assertSame('Drs. Abdul Muis, S.E., M.M.', $this->pegawai('Drs.', 'S.E., M.M.')->namaLengkap());
    }

    #[Test]
    public function spasi_berlebih_dirapikan()
    {
        $this->assertSame('Drs. Abdul Muis, S.E.', $this->pegawai('  Drs.  ', '  S.E.  ')->namaLengkap());
    }

    #[Test]
    public function data_lama_yang_gelarnya_menyatu_di_nama_tidak_tergandakan()
    {
        // Data lama: kolom gelar kosong, gelar sudah ada di dalam nama.
        $lama = $this->pegawai(null, null, 'Drs. Abdul Muis, M.Si.');

        $this->assertSame('Drs. Abdul Muis, M.Si.', $lama->namaLengkap());
    }
}
