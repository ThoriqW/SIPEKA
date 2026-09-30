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

    private function payload(
        Unor $induk,
        Unor $unor,
        string $nama = 'Statistisi',
        string $jenis = 'Fungsional',
        string $jenjang = 'Ahli Muda',
    ): array {
        return [
            'nama_jabatan' => $nama,
            'jenis_jabatan' => $jenis,
            'kelas_jabatan' => 8,
            'jenjang' => $jenjang,
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

    // ───── Aturan khusus Pimpinan Tinggi Pratama ─────

    private static int $pdSeq = 0;

    /** Perangkat Daerah baru — seeder sudah menghabiskan jatah JPTP di ketiga OPD-nya. */
    private function buatPerangkatDaerah(string $nama): Unor
    {
        return Unor::create([
            'nama_unor' => $nama,
            'kode_unor' => 'PD-UJI-' . (++self::$pdSeq),
            'parent_id' => $this->pemkot->id,
        ]);
    }

    #[Test]
    public function store_accepts_the_first_jptp_in_a_perangkat_daerah()
    {
        $pd = $this->buatPerangkatDaerah('Dinas Uji');

        $this->actingAs($this->user)
            ->post(route('admin.jabatan.store'), $this->payload($this->pemkot, $pd, 'Kepala Dinas', 'Struktural', 'Pimpinan Tinggi Pratama'))
            ->assertRedirect(route('admin.jabatan.index'));

        $this->assertSame(1, Sotk::where('unor_id', $pd->id)->count());
        $this->assertDatabaseHas('jabatan', ['nama_jabatan' => 'Kepala Dinas', 'jenjang' => 'Pimpinan Tinggi Pratama']);
    }

    #[Test]
    public function store_rejects_a_second_jptp_in_the_same_perangkat_daerah()
    {
        $pd = $this->buatPerangkatDaerah('Dinas Uji');

        $this->actingAs($this->user)
            ->post(route('admin.jabatan.store'), $this->payload($this->pemkot, $pd, 'Kepala Dinas', 'Struktural', 'Pimpinan Tinggi Pratama'))
            ->assertRedirect(route('admin.jabatan.index'));

        // Nama berbeda, jadi pemeriksaan duplikasi nama+jenjang tidak menangkapnya.
        $response = $this->actingAs($this->user)
            ->post(route('admin.jabatan.store'), $this->payload($this->pemkot, $pd, 'Kepala Badan', 'Struktural', 'Pimpinan Tinggi Pratama'));

        $response->assertSessionHas('error');
        // Hanya JPTP pertama yang menempel di Perangkat Daerah ini.
        $this->assertSame(1, Sotk::where('unor_id', $pd->id)->count());
    }

    #[Test]
    public function store_rejects_jptp_below_perangkat_daerah_level()
    {
        $pd = $this->buatPerangkatDaerah('Dinas Uji');
        $bidang = Unor::create(['nama_unor' => 'Bidang Uji', 'kode_unor' => 'BID-UJI', 'parent_id' => $pd->id]);

        $response = $this->actingAs($this->user)
            ->post(route('admin.jabatan.store'), $this->payload($pd, $bidang, 'Kepala Bidang', 'Struktural', 'Pimpinan Tinggi Pratama'));

        $response->assertSessionHas('error');
        $this->assertSame(0, Sotk::where('unor_id', $bidang->id)->count());
    }

    #[Test]
    public function store_still_allows_non_jptp_structural_at_a_sub_unit()
    {
        $pd = $this->buatPerangkatDaerah('Dinas Uji');
        $bidang = Unor::create(['nama_unor' => 'Bidang Uji', 'kode_unor' => 'BID-UJI', 'parent_id' => $pd->id]);

        // Aturan JPTP tidak boleh ikut membatasi jenjang struktural lain.
        $this->actingAs($this->user)
            ->post(route('admin.jabatan.store'), $this->payload($pd, $bidang, 'Kepala Bidang', 'Struktural', 'Administrator'))
            ->assertRedirect(route('admin.jabatan.index'));

        $this->assertSame(1, Sotk::where('unor_id', $bidang->id)->count());
    }

    #[Test]
    public function update_allows_keeping_the_same_jptp()
    {
        $pd = $this->buatPerangkatDaerah('Dinas Uji');

        $this->actingAs($this->user)
            ->post(route('admin.jabatan.store'), $this->payload($this->pemkot, $pd, 'Kepala Dinas', 'Struktural', 'Pimpinan Tinggi Pratama'));

        $jabatan = Jabatan::where('jenjang', 'Pimpinan Tinggi Pratama')
            ->whereHas('sotkEntries', fn($q) => $q->where('unor_id', $pd->id))
            ->firstOrFail();

        // JPTP tidak boleh memblokir dirinya sendiri saat disimpan ulang.
        $this->actingAs($this->user)
            ->put(route('admin.jabatan.update', $jabatan), $this->payload($this->pemkot, $pd, 'Kepala Dinas', 'Struktural', 'Pimpinan Tinggi Pratama'))
            ->assertRedirect(route('admin.jabatan.index'));

        $this->assertSame($pd->id, $jabatan->sotkEntries()->value('unor_id'));
    }

    #[Test]
    public function update_rejects_moving_a_jptp_into_a_perangkat_daerah_that_already_has_one()
    {
        $dinasA = $this->buatPerangkatDaerah('Dinas A');
        $dinasB = $this->buatPerangkatDaerah('Dinas B');

        $this->actingAs($this->user)
            ->post(route('admin.jabatan.store'), $this->payload($this->pemkot, $dinasA, 'Kepala Dinas', 'Struktural', 'Pimpinan Tinggi Pratama'))
            ->assertRedirect(route('admin.jabatan.index'));
        $this->actingAs($this->user)
            ->post(route('admin.jabatan.store'), $this->payload($this->pemkot, $dinasB, 'Kepala Badan', 'Struktural', 'Pimpinan Tinggi Pratama'))
            ->assertRedirect(route('admin.jabatan.index'));

        // "Kepala Badan" juga dibuat seeder di OPD lain — batasi ke Dinas B.
        $jabatanB = Jabatan::where('nama_jabatan', 'Kepala Badan')
            ->whereHas('sotkEntries', fn($q) => $q->where('unor_id', $dinasB->id))
            ->firstOrFail();

        $this->actingAs($this->user)
            ->put(route('admin.jabatan.update', $jabatanB), $this->payload($this->pemkot, $dinasA, 'Kepala Badan', 'Struktural', 'Pimpinan Tinggi Pratama'))
            ->assertSessionHas('error');

        $this->assertSame($dinasB->id, $jabatanB->sotkEntries()->value('unor_id'),
            'Penempatan lama tidak boleh berubah saat validasi gagal.');
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
