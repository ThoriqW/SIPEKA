<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\ReferensiJabatan;
use App\Models\Unor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Teks dari database tidak boleh masuk ke ekspresi JavaScript Alpine.
 *
 * Blade meng-escape ' menjadi &#039; sehingga HTML-nya terlihat aman. Tapi
 * browser men-decode entity di dalam nilai atribut sebelum Alpine membacanya,
 * jadi kutip tunggal itu kembali muncul dan memutus string JS — sisanya lalu
 * dikompilasi sebagai kode.
 *
 * Karena itu nilai dari database/pengguna hanya boleh sampai ke Alpine lewat
 * atribut data-*, dibaca dengan $el.dataset. Test ini menjaga invarian itu:
 * tidak satu pun ekspresi Alpine pada halaman-halaman berikut boleh memuat
 * teks yang berasal dari data.
 */
class AlpineBindingSafetyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kutip tunggal (memutus string → SyntaxError → seluruh komponen mati)
     * sekaligus payload yang benar-benar jalan kalau ia sampai ke JS.
     */
    private const NAMA_BERBAHAYA = "Ma'ruf + (window.__xss = 1) + 'Palu";

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // ───────────────────────────── unor/create ─────────────────────────────

    #[Test]
    public function unor_create_tidak_menyisipkan_nama_unor_ke_ekspresi_alpine()
    {
        $induk = $this->unorBerbahaya();

        // old('parent_id') membuat induk itu terpilih, sehingga jalur namanya
        // ikut dirakit menjadi nilai awal dropdown.
        $html = $this->withSession(['_old_input' => ['parent_id' => $induk->id]])
            ->actingAs($this->admin())
            ->get(route('admin.unor.create'))
            ->assertStatus(200)
            ->getContent();

        $this->assertNamaBerbahayaSampaiKeView($html, 'unor/create');
        $this->assertTidakAdaTeksDataDiEkspresiAlpine($html, 'unor/create');
    }

    // ────────────────────────────── unor/edit ──────────────────────────────

    #[Test]
    public function unor_edit_tidak_menyisipkan_jalur_induk_ke_ekspresi_alpine()
    {
        $induk = $this->unorBerbahaya();

        $anak = Unor::create([
            'nama_unor' => 'Bidang Anak',
            'kode_unor' => 'ANAK',
            'parent_id' => $induk->id,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.unor.edit', $anak))
            ->assertStatus(200)
            ->getContent();

        $this->assertNamaBerbahayaSampaiKeView($html, 'unor/edit');
        $this->assertTidakAdaTeksDataDiEkspresiAlpine($html, 'unor/edit');
    }

    // ───────────────────────────── user/create ─────────────────────────────

    #[Test]
    public function user_create_tidak_menyisipkan_nama_pegawai_ke_ekspresi_alpine()
    {
        $this->pegawaiBerbahaya();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.user.create'))
            ->assertStatus(200)
            ->getContent();

        $this->assertNamaBerbahayaSampaiKeView($html, 'user/create');
        $this->assertTidakAdaTeksDataDiEkspresiAlpine($html, 'user/create');
    }

    #[Test]
    public function user_create_tidak_menyisipkan_nama_pegawai_saat_form_diisi_ulang()
    {
        // Jalur kedua: setelah POST gagal, old('nip') terisi dan nama pegawai
        // ikut dirakit menjadi nilai awal dropdown.
        $pegawai = $this->pegawaiBerbahaya();

        $html = $this->withSession(['_old_input' => ['nip' => $pegawai->nip]])
            ->actingAs($this->admin())
            ->get(route('admin.user.create'))
            ->assertStatus(200)
            ->getContent();

        $this->assertNamaBerbahayaSampaiKeView($html, 'user/create (isi ulang)');
        $this->assertTidakAdaTeksDataDiEkspresiAlpine($html, 'user/create (isi ulang)');

        $this->assertStringContainsString(
            'data-peg-selected="' . e($pegawai->nip . ' — ' . $pegawai->namaLengkap()) . '"',
            $html,
            'Nilai awal dropdown pegawai harus dikirim lewat data-peg-selected.'
        );
    }

    // ────────────────────────── referensi-jabatan ──────────────────────────

    #[Test]
    public function referensi_jabatan_membaca_jenis_dari_atribut_data()
    {
        // jenis_jabatan divalidasi in:Struktural,Fungsional,Pelaksana, jadi
        // nilainya tidak pernah datang dari teks bebas. Yang dijaga di sini
        // adalah polanya: tidak ada string JS yang dirakit dari Blade.
        $master = ReferensiJabatan::where('jenis_jabatan', 'Fungsional')->first()
            ?? ReferensiJabatan::create([
                'nama_jabatan' => 'Pranata Komputer',
                'jenis_jabatan' => 'Fungsional',
            ]);

        $halaman = [
            'create' => route('admin.referensi-jabatan.create'),
            'edit' => route('admin.referensi-jabatan.edit', $master),
        ];

        foreach ($halaman as $nama => $url) {
            $html = $this->actingAs($this->admin())->get($url)->assertStatus(200)->getContent();

            $this->assertStringContainsString('data-jenis="', $html,
                "Form referensi-jabatan/{$nama} tidak mengirim jenis lewat data-*.");

            foreach ($this->ekspresiAlpine($html) as $ekspresi) {
                $this->assertStringNotContainsString("jenis: '", $ekspresi,
                    "Nilai jenis masih dirakit menjadi string JS di referensi-jabatan/{$nama}: {$ekspresi}");
            }
        }
    }

    // ────────────────────────────── bantuan ──────────────────────────────

    private function admin(): User
    {
        return User::where('role', 'admin')->firstOrFail();
    }

    private function unorBerbahaya(): Unor
    {
        $root = Unor::whereNull('parent_id')->firstOrFail();

        return Unor::create([
            'nama_unor' => self::NAMA_BERBAHAYA,
            'kode_unor' => 'BAHAYA',
            'parent_id' => $root->id,
        ]);
    }

    private function pegawaiBerbahaya(): Pegawai
    {
        return Pegawai::create([
            'nama' => self::NAMA_BERBAHAYA,
            'nip' => '200001012025011099',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '2000-01-01',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'D4/S1',
        ]);
    }

    /**
     * Seluruh nilai atribut direktif Alpine pada sebuah halaman.
     *
     * @return string[]
     */
    private function ekspresiAlpine(string $html): array
    {
        preg_match_all(
            '/\s(?:x-[a-z:-]+|@[a-z:.-]+|:[a-z-]+)="([^"]*)"/i',
            $html,
            $matches,
        );

        return $matches[1];
    }

    /**
     * Nilai berbahaya harus benar-benar sampai ke view — kalau tidak, test ini
     * hampa dan akan lulus walau binding-nya kembali tidak aman.
     */
    private function assertNamaBerbahayaSampaiKeView(string $html, string $konteks): void
    {
        $this->assertStringContainsString(
            'Ma&#039;ruf',
            $html,
            "Nama berbahaya tidak muncul di {$konteks}; fixture tidak sampai ke view sehingga test ini tidak menguji apa pun.",
        );
    }

    private function assertTidakAdaTeksDataDiEkspresiAlpine(string $html, string $konteks): void
    {
        $ekspresi = $this->ekspresiAlpine($html);

        $this->assertNotEmpty(
            $ekspresi,
            "Tidak ada ekspresi Alpine yang terbaca di {$konteks}; pola pencarinya perlu diperiksa.",
        );

        foreach ($ekspresi as $satu) {
            $this->assertStringNotContainsString(
                "Ma'ruf",
                $satu,
                "Nama dari database bocor ke ekspresi Alpine di {$konteks}: {$satu}",
            );

            $this->assertStringNotContainsString(
                '__xss',
                $satu,
                "Payload dari database bocor ke ekspresi Alpine di {$konteks}: {$satu}",
            );
        }
    }
}
