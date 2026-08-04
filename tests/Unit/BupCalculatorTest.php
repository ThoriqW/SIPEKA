<?php

namespace Tests\Unit;

use App\Services\BupCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BupCalculatorTest extends TestCase
{
    private BupCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(BupCalculator::class);
    }

    // ─── Guru ────────────────────────────────────────────

    #[Test]
    public function guru_dengan_sub_jabatan_bup_60()
    {
        // Format: "Guru - Guru Bahasa Indonesia"
        $bup = $this->calculator->hitungBup('Ahli Madya', 'Guru - Guru Bahasa Indonesia');
        $this->assertEquals(60, $bup);
    }

    #[Test]
    public function guru_ahli_utama_tetap_60_mengoverride_jenjang()
    {
        // Guru dengan jenjang Ahli Utama (Guru Utama) — tetap 60
        $bup = $this->calculator->hitungBup('Ahli Utama', 'Guru - Matematika');
        $this->assertEquals(60, $bup, 'Guru BUP should be 60 regardless of Ahli Utama jenjang');
    }

    #[Test]
    public function guru_tanpa_jenjang_bup_60()
    {
        $bup = $this->calculator->hitungBup('', 'Guru - Guru Bahasa Indonesia');
        $this->assertEquals(60, $bup);
    }

    // ─── Ahli Utama non-Guru ─────────────────────────────

    #[Test]
    public function ahli_utama_non_guru_bup_65()
    {
        $bup = $this->calculator->hitungBup('Ahli Utama', 'Pranata Komputer');
        $this->assertEquals(65, $bup);
    }

    // ─── Ahli Madya non-Guru ─────────────────────────────

    #[Test]
    public function ahli_madya_non_guru_bup_60()
    {
        $bup = $this->calculator->hitungBup('Ahli Madya', 'Pranata Komputer');
        $this->assertEquals(60, $bup);
    }

    // ─── JPT Pratama ─────────────────────────────────────

    #[Test]
    public function jpt_pratama_bup_60()
    {
        $bup = $this->calculator->hitungBup('Pimpinan Tinggi Pratama', 'Kepala Badan');
        $this->assertEquals(60, $bup);
    }

    // ─── Ahli Muda ───────────────────────────────────────

    #[Test]
    public function ahli_muda_bup_58()
    {
        $bup = $this->calculator->hitungBup('Ahli Muda', 'Widyaiswara');
        $this->assertEquals(58, $bup);
    }

    // ─── Ahli Pertama ────────────────────────────────────

    #[Test]
    public function ahli_pertama_bup_58()
    {
        $bup = $this->calculator->hitungBup('Ahli Pertama', 'Dokter');
        $this->assertEquals(58, $bup);
    }

    // ─── Pelaksana ───────────────────────────────────────

    #[Test]
    public function pelaksana_bup_58()
    {
        $bup = $this->calculator->hitungBup('Pelaksana', 'Pengemudi');
        $this->assertEquals(58, $bup);
    }

    // ─── Nama jabatan null ───────────────────────────────

    #[Test]
    public function nama_jabatan_null_tidak_error()
    {
        // Tanpa nama_jabatan, deteksi Guru hanya via jenjang
        $bup = $this->calculator->hitungBup('Ahli Pertama', null);
        $this->assertEquals(58, $bup);
    }

    // ─── Tanggal pensiun ─────────────────────────────────

    #[Test]
    public function hitung_tanggal_pensiun_menghasilkan_tanggal_yang_benar()
    {
        // Lahir 1990-06-15, BUP 58 → pensiun 2048-06-15
        $tanggal = $this->calculator->hitungTanggalPensiun(
            '1990-06-15',
            'Ahli Pertama',
            'PNS',
            'Pranata Komputer'
        );

        $this->assertInstanceOf(\DateTimeImmutable::class, $tanggal);
        $this->assertEquals('2048-06-15', $tanggal->format('Y-m-d'));
    }

    // ─── Edge cases ──────────────────────────────────────

    #[Test]
    public function nama_jabatan_mengandung_kata_guru_tapi_bukan_guru()
    {
        // String "guru" tidak muncul di nama jabatan non-Guru
        $bup = $this->calculator->hitungBup('Ahli Pertama', 'Pengelola Kepegawaian');
        $this->assertEquals(58, $bup);
    }

    #[Test]
    public function administrator_bup_58()
    {
        $bup = $this->calculator->hitungBup('Administrator', 'Sekretaris');
        $this->assertEquals(58, $bup);
    }
}
