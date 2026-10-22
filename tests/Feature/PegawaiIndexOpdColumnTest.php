<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Unor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Kolom OPD dan Unit Organisasi pada daftar pegawai.
 *
 * OPD dihitung dari penempatan (anak langsung Pemerintah Kota), bukan dari
 * jabatan — jadi berlaku sama untuk semua jabatan, termasuk pimpinan OPD.
 * Kolom Unit memakai jalur relatif terhadap OPD supaya unit bernama sama di
 * cabang berbeda tetap bisa dibedakan.
 */
class PegawaiIndexOpdColumnTest extends TestCase
{
    use RefreshDatabase;

    private Unor $opd;
    private Unor $sekretariatOpd;
    private Unor $sekretariatAntara;
    private Jabatan $pelaksana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $root = Unor::whereNull('parent_id')->firstOrFail();

        $this->opd = Unor::create([
            'nama_unor' => 'Dinas Uji', 'kode_unor' => 'UJI', 'parent_id' => $root->id,
        ]);

        // Dua unit bernama sama persis di cabang berbeda — kasus "Sekretariat"
        // di kecamatan vs kelurahan.
        $this->sekretariatOpd = Unor::create([
            'nama_unor' => 'Sekretariat', 'kode_unor' => 'UJI-SEK', 'parent_id' => $this->opd->id,
        ]);
        $antara = Unor::create([
            'nama_unor' => 'Unit Antara', 'kode_unor' => 'UJI-ANT', 'parent_id' => $this->opd->id,
        ]);
        $this->sekretariatAntara = Unor::create([
            'nama_unor' => 'Sekretariat', 'kode_unor' => 'UJI-ANT-SEK', 'parent_id' => $antara->id,
        ]);

        $this->pelaksana = Jabatan::create([
            'nama_jabatan' => 'Pengelola Uji', 'kode_jabatan' => 'UJI-001',
            'jenis_jabatan' => 'Pelaksana', 'kelas_jabatan' => 6,
        ]);
    }

    #[Test]
    public function kolom_opd_menggantikan_unor_induk()
    {
        $html = $this->halaman();

        $this->assertStringContainsString('>OPD</th>', $html);
        $this->assertStringNotContainsString('Unor Induk', $html);
    }

    #[Test]
    public function dua_unit_bernama_sama_tampil_berbeda_dengan_jalur_relatif()
    {
        $this->buatPegawai('ZZUji A', '200001012025011101', $this->sekretariatOpd);
        $this->buatPegawai('ZZUji B', '200001012025011102', $this->sekretariatAntara);

        $html = $this->halaman();

        $selA = $this->selBaris($html, '200001012025011101');
        $selB = $this->selBaris($html, '200001012025011102');

        // Keduanya berada di OPD yang sama...
        $this->assertSame('Dinas Uji', $this->teks($selA[3]));
        $this->assertSame('Dinas Uji', $this->teks($selB[3]));

        // ...tapi unitnya tetap bisa dibedakan.
        $this->assertSame('Sekretariat', $this->teks($selA[5]));
        $this->assertSame('Unit Antara » Sekretariat', $this->teks($selB[5]));
        $this->assertNotSame($this->teks($selA[5]), $this->teks($selB[5]));
    }

    #[Test]
    public function pimpinan_opd_menampilkan_opd_yang_dipimpin()
    {
        $jptp = Jabatan::create([
            'nama_jabatan' => 'Kepala Dinas Uji', 'kode_jabatan' => 'UJI-002',
            'jenis_jabatan' => 'Struktural', 'jenjang' => 'Pimpinan Tinggi Pratama',
            'kelas_jabatan' => 15,
        ]);

        $this->buatPegawai('ZZUji Pimpinan', '200001012025011103', $this->opd, $jptp);

        $sel = $this->selBaris($this->halaman(), '200001012025011103');

        $this->assertSame('Dinas Uji', $this->teks($sel[3]),
            'Kepala OPD harus menampilkan OPD yang dipimpin, bukan Pemerintah Kota Palu.');
        $this->assertStringNotContainsString('Pemerintah Kota Palu', $this->teks($sel[3]));

        // Jabatan pimpinan diperlakukan sama seperti jabatan lain: unitnya
        // adalah OPD itu sendiri.
        $this->assertSame('Dinas Uji', $this->teks($sel[5]));
    }

    #[Test]
    public function jalur_penuh_tersedia_di_tooltip()
    {
        $this->buatPegawai('ZZUji A', '200001012025011101', $this->sekretariatOpd);
        $this->buatPegawai('ZZUji B', '200001012025011102', $this->sekretariatAntara);

        $html = $this->halaman();

        $selA = $this->selBaris($html, '200001012025011101');
        $selB = $this->selBaris($html, '200001012025011102');

        $this->assertStringContainsString(
            'title="Dinas Uji » Sekretariat"', $selA[5],
            'Tooltip harus memuat jalur penuh di atas OPD.'
        );
        $this->assertStringContainsString(
            'title="Dinas Uji » Unit Antara » Sekretariat"', $selB[5],
            'Tooltip harus memuat jalur penuh di atas OPD.'
        );
    }

    // ────────────────────────────── bantuan ──────────────────────────────

    private function halaman(): string
    {
        return $this->actingAs(User::where('role', 'admin')->firstOrFail())
            ->get(route('admin.pegawai.index', ['search' => 'ZZUji']))
            ->assertStatus(200)
            ->getContent();
    }

    private function buatPegawai(string $nama, string $nip, Unor $unor, ?Jabatan $jabatan = null): Pegawai
    {
        $jabatan ??= $this->pelaksana;

        $pegawai = Pegawai::create([
            'nama' => $nama,
            'nip' => $nip,
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1990-01-01',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'D4/S1',
            'jabatan_id' => $jabatan->id,
        ]);

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unor_id' => $unor->id,
            'jabatan_id' => $jabatan->id,
            'tanggal_mulai' => '2020-01-01',
            'is_active' => true,
        ]);

        return $pegawai;
    }

    /**
     * Potongan <td> mentah milik baris dengan NIP tertentu.
     *
     * @return string[]
     */
    private function selBaris(string $html, string $nip): array
    {
        preg_match_all('/<tr\b.*?<\/tr>/s', $html, $baris);

        foreach ($baris[0] as $tr) {
            if (!str_contains($tr, $nip)) {
                continue;
            }

            preg_match_all('/<td\b[^>]*>.*?<\/td>/s', $tr, $sel);

            return $sel[0];
        }

        $this->fail("Baris untuk NIP {$nip} tidak ditemukan pada halaman.");
    }

    private function teks(string $td): string
    {
        return trim(html_entity_decode(strip_tags($td)));
    }
}
