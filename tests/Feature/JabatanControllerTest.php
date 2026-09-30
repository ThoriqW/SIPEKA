<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Sotk;
use App\Models\Unor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Form Jabatan — pemilihan Unit Organisasi.
 *
 * Kecamatan dan kelurahan sama-sama dapat memiliki "Sekretariat". Tanpa jalur
 * pada labelnya, kedua pilihan itu tampil kembar dan jabatan bisa tersimpan di
 * UNOR yang salah tanpa disadari.
 */
class JabatanControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Unor $pemkot;
    private Unor $dinas;
    private Unor $kecamatan;
    private Unor $kelurahan;
    private Unor $sekretariatKelurahan;
    private Unor $sekretariatKecamatan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::where('role', 'admin')->first();
        $this->pemkot = Unor::whereNull('parent_id')->firstOrFail();
        $this->dinas = Unor::where('kode_unor', 'DINKES')->firstOrFail();

        $this->kecamatan = Unor::create(['nama_unor' => 'Kecamatan Palu Barat', 'kode_unor' => 'KEC-PB', 'parent_id' => $this->pemkot->id]);
        $this->kelurahan = Unor::create(['nama_unor' => 'Kelurahan Birobuli', 'kode_unor' => 'KEL-BIR', 'parent_id' => $this->kecamatan->id]);
        $this->sekretariatKelurahan = Unor::create(['nama_unor' => 'Sekretariat', 'kode_unor' => 'SEK-BIR', 'parent_id' => $this->kelurahan->id]);
        $this->sekretariatKecamatan = Unor::create(['nama_unor' => 'Sekretariat', 'kode_unor' => 'SEK-PB', 'parent_id' => $this->kecamatan->id]);
    }

    /**
     * Isi dropdown Unit Organisasi per Unor Induk, dibaca dari JSON yang
     * dikirim ke Alpine — bukan dari HTML-nya, supaya pemeriksaan tidak
     * bergantung pada bentuk escape JSON.
     */
    private function dropdownPerInduk(string $html): array
    {
        $this->assertMatchesRegularExpression('/^\s*var unorData = (\{.*\});$/m', $html);
        preg_match('/^\s*var unorData = (\{.*\});$/m', $html, $m);

        return json_decode($m[1], true);
    }

    private function buatJabatanDi(Unor $unor, string $nama): Jabatan
    {
        $jabatan = Jabatan::create([
            'nama_jabatan' => $nama, 'kode_jabatan' => 'UJI-' . $unor->id,
            'jenis_jabatan' => 'Struktural', 'kelas_jabatan' => 11, 'jenjang' => 'Ahli Madya',
        ]);
        Sotk::create(['unor_id' => $unor->id, 'jabatan_id' => $jabatan->id]);

        return $jabatan;
    }

    private function payload(Unor $induk, Unor $unor): array
    {
        return [
            'nama_jabatan' => 'Statistisi',
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang' => 'Ahli Muda',
            'kebutuhan' => 1,
            'induk_id' => $induk->id,
            'unor_id' => $unor->id,
        ];
    }

    // ─────────────────── Label dropdown ───────────────────

    #[Test]
    public function unit_dropdown_labels_separate_homonymous_unor()
    {
        $response = $this->actingAs($this->user)->get(route('admin.jabatan.create'));
        $response->assertOk();

        $label = array_column($this->dropdownPerInduk($response->getContent())[$this->kecamatan->id], 'nama');

        // Dua "Sekretariat" di bawah kecamatan yang sama kini berlabel berbeda.
        $this->assertContains('Kelurahan Birobuli » Sekretariat', $label);
        $this->assertContains('Sekretariat', $label);
        $this->assertContains('Kecamatan Palu Barat', $label);
    }

    #[Test]
    public function dropdown_only_offers_units_within_the_selected_induk()
    {
        $response = $this->actingAs($this->user)->get(route('admin.jabatan.create'));

        $perInduk = $this->dropdownPerInduk($response->getContent());
        $idsKecamatan = array_column($perInduk[$this->kecamatan->id], 'id');

        $this->assertNotContains($this->dinas->id, $idsKecamatan, 'OPD lain tidak boleh muncul di bawah kecamatan.');
        $this->assertContains($this->sekretariatKelurahan->id, $idsKecamatan);
        $this->assertContains($this->sekretariatKecamatan->id, $idsKecamatan);
    }

    #[Test]
    public function edit_form_uses_the_same_path_labels()
    {
        $jabatan = $this->buatJabatanDi($this->sekretariatKelurahan, 'Sekretaris Kelurahan');

        $response = $this->actingAs($this->user)->get(route('admin.jabatan.edit', $jabatan));
        $response->assertOk();

        $label = array_column($this->dropdownPerInduk($response->getContent())[$this->kecamatan->id], 'nama');

        $this->assertContains('Kelurahan Birobuli » Sekretariat', $label);
    }

    // ─────────── Endpoint jabatan per OPD (dipakai form Pegawai) ───────────

    #[Test]
    public function by_opd_labels_units_with_path_relative_to_the_selected_opd()
    {
        $jabatan = $this->buatJabatanDi($this->sekretariatKelurahan, 'Sekretaris');

        $response = $this->actingAs($this->user)
            ->get(route('admin.jabatan.by-opd', ['unor_id' => $this->kecamatan->id]));

        $response->assertOk();
        $data = collect($response->json('data'))->firstWhere('id', $jabatan->id);

        $this->assertNotNull($data);
        $this->assertSame('Kelurahan Birobuli » Sekretariat', $data['unor_jalur']);
    }

    #[Test]
    public function by_opd_distinguishes_homonymous_units_below_one_opd()
    {
        $kelurahan = $this->buatJabatanDi($this->sekretariatKelurahan, 'Sekretaris');
        $kecamatan = $this->buatJabatanDi($this->sekretariatKecamatan, 'Sekretaris');

        $data = collect(
            $this->actingAs($this->user)
                ->get(route('admin.jabatan.by-opd', ['unor_id' => $this->kecamatan->id]))
                ->json('data')
        );

        // Keduanya bernama "Sekretaris" pada UNOR bernama "Sekretariat" —
        // inilah yang dulu tampil kembar di dropdown form Pegawai.
        $this->assertSame('Kelurahan Birobuli » Sekretariat', $data->firstWhere('id', $kelurahan->id)['unor_jalur']);
        $this->assertSame('Sekretariat', $data->firstWhere('id', $kecamatan->id)['unor_jalur']);
    }

    #[Test]
    public function by_opd_marks_a_filled_structural_seat()
    {
        $terisi = $this->buatJabatanDi($this->sekretariatKelurahan, 'Sekretaris Kelurahan');
        $kosong = $this->buatJabatanDi($this->sekretariatKecamatan, 'Sekretaris Kecamatan');

        $fungsional = Jabatan::create([
            'nama_jabatan' => 'Pranata Komputer', 'kode_jabatan' => 'UJI-F',
            'jenis_jabatan' => 'Fungsional', 'kelas_jabatan' => 8, 'jenjang' => 'Ahli Pertama',
        ]);
        Sotk::create(['unor_id' => $this->sekretariatKecamatan->id, 'jabatan_id' => $fungsional->id]);

        // Kursi struktural pertama diisi, plus satu jabatan fungsional berisi
        // pegawai — fungsional tidak boleh dianggap "terisi".
        foreach ([$terisi, $fungsional] as $jabatan) {
            $pegawai = Pegawai::create([
                'nip' => '2000010120250110' . $jabatan->id,
                'nama' => 'Pegawai ' . $jabatan->id,
                'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => '2000-01-01',
                'golongan_pangkat' => 'III/a', 'pendidikan' => 'D4/S1',
                'jabatan_id' => $jabatan->id,
            ]);
            PenempatanPegawai::create([
                'pegawai_id' => $pegawai->id,
                'unor_id' => $jabatan->sotkEntries()->value('unor_id'),
                'jabatan_id' => $jabatan->id,
                'tanggal_mulai' => now()->toDateString(),
                'is_active' => true,
            ]);
        }

        $data = collect(
            $this->actingAs($this->user)
                ->get(route('admin.jabatan.by-opd', ['unor_id' => $this->kecamatan->id]))
                ->json('data')
        );

        $this->assertTrue($data->firstWhere('id', $terisi->id)['terisi']);
        $this->assertFalse($data->firstWhere('id', $kosong->id)['terisi']);
        $this->assertFalse($data->firstWhere('id', $fungsional->id)['terisi'],
            'Jabatan Fungsional boleh diisi banyak pegawai, jadi tidak pernah ditandai terisi.');
    }

    // ─────────────────── Daftar Jabatan ───────────────────

    #[Test]
    public function index_shows_full_path_so_homonymous_unor_can_be_told_apart()
    {
        $this->buatJabatanDi($this->sekretariatKelurahan, 'Sekretaris Kelurahan');
        $this->buatJabatanDi($this->sekretariatKecamatan, 'Sekretaris Kecamatan');

        $response = $this->actingAs($this->user)
            ->get(route('admin.jabatan.index', ['unor_id' => $this->kecamatan->id]));

        $response->assertOk();

        // Penutup </td> dipakai agar jalur pendek tidak ikut cocok sebagai
        // potongan dari jalur yang lebih panjang.
        $response->assertSee('Kecamatan Palu Barat » Sekretariat</td>', escape: false);
        $response->assertSee('Kecamatan Palu Barat » Kelurahan Birobuli » Sekretariat</td>', escape: false);
    }

    // ─────────────────── Validasi simpan ───────────────────

    #[Test]
    public function store_rejects_unit_outside_the_selected_induk()
    {
        // Sekretariat Kelurahan Birobuli tidak berada di bawah Dinas Kesehatan.
        $response = $this->actingAs($this->user)
            ->post(route('admin.jabatan.store'), $this->payload($this->dinas, $this->sekretariatKelurahan));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('jabatan', ['nama_jabatan' => 'Statistisi']);
    }

    #[Test]
    public function store_accepts_unit_within_the_selected_induk()
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.jabatan.store'), $this->payload($this->kecamatan, $this->sekretariatKelurahan));

        $response->assertRedirect(route('admin.jabatan.index'));

        $jabatan = Jabatan::where('nama_jabatan', 'Statistisi')->first();
        $this->assertNotNull($jabatan);
        $this->assertSame(
            $this->sekretariatKelurahan->id,
            $jabatan->sotkEntries()->value('unor_id'),
            'Jabatan harus menempel pada UNOR yang benar-benar dipilih.'
        );
    }

    #[Test]
    public function store_accepts_perangkat_daerah_under_pemkot()
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.jabatan.store'), $this->payload($this->pemkot, $this->dinas));

        $response->assertRedirect(route('admin.jabatan.index'));
        $this->assertNotNull(Jabatan::where('nama_jabatan', 'Statistisi')->first());
    }

    #[Test]
    public function update_rejects_unit_outside_the_selected_induk()
    {
        $jabatan = $this->buatJabatanDi($this->sekretariatKelurahan, 'Sekretaris Kelurahan');

        $response = $this->actingAs($this->user)
            ->put(route('admin.jabatan.update', $jabatan), $this->payload($this->dinas, $this->sekretariatKelurahan));

        $response->assertSessionHas('error');
        $this->assertSame(
            $this->sekretariatKelurahan->id,
            $jabatan->sotkEntries()->value('unor_id'),
            'Penempatan lama tidak boleh berubah saat validasi gagal.'
        );
    }
}
