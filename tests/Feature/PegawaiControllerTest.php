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

class PegawaiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function authenticated_user_can_access_pegawai_index()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.pegawai.index'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Pegawai');
    }

    #[Test]
    public function unauthenticated_user_is_redirected()
    {
        $response = $this->get(route('admin.pegawai.index'));
        $response->assertRedirect('/login');
    }

    #[Test]
    public function can_view_pegawai_create_form()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.pegawai.create'));

        $response->assertStatus(200);
        $response->assertSee('Tambah Pegawai');
    }

    #[Test]
    public function can_create_pegawai_with_penempatan()
    {
        $user = User::where('role', 'admin')->first();
        $unor = $this->unorInduk();
        $jabatan = $this->buatJabatan($unor, 'Fungsional', 'Ahli Pertama');
        $unorId = $jabatan->sotkEntries->first()?->unor_id;

        $response = $this->actingAs($user)->post(
            route('admin.pegawai.store'),
            $this->payloadPegawai($unor, $jabatan, $this->nip(1), 'Test Pegawai Baru')
        );

        $response->assertRedirect(route('admin.pegawai.index'));

        $pegawai = Pegawai::where('nip', $this->nip(1))->first();
        $this->assertNotNull($pegawai);
        $this->assertEquals($jabatan->jenjang, $pegawai->jabatan->jenjang);

        // Harus ada penempatan aktif
        $this->assertNotNull($pegawai->penempatanAktif);
        $this->assertEquals($unorId, $pegawai->penempatanAktif->unor_id);
        $this->assertEquals($jabatan->id, $pegawai->penempatanAktif->jabatan_id);
    }

    // ─────── Kursi jabatan: Struktural satu orang, lainnya banyak ───────

    private function unorInduk(): Unor
    {
        return Unor::where('kode_unor', 'DIKBUD')->firstOrFail();
    }

    private static int $seq = 0;

    private function buatJabatan(Unor $unor, string $jenis, string $jenjang, string $nama = null): Jabatan
    {
        $jabatan = Jabatan::create([
            'nama_jabatan' => $nama ?? ('Jabatan Uji ' . $jenis),
            'kode_jabatan' => 'UJI-' . (++self::$seq),
            'jenis_jabatan' => $jenis,
            'kelas_jabatan' => 10,
            'jenjang' => $jenjang,
        ]);
        Sotk::create(['unor_id' => $unor->id, 'jabatan_id' => $jabatan->id]);

        return $jabatan;
    }

    private function nip(int $n): string
    {
        return '2000010120250110' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    }

    private function payloadPegawai(Unor $unor, Jabatan $jabatan, string $nip, string $nama): array
    {
        return [
            'nip' => $nip,
            'nama' => $nama,
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '2000-01-01',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'D4/S1',
            'induk_id' => $unor->id,
            'jabatan_id' => $jabatan->id,
        ];
    }

    #[Test]
    public function multiple_pegawai_can_share_same_non_structural_jabatan()
    {
        $user = User::where('role', 'admin')->first();
        $unor = $this->unorInduk();

        foreach ([['Fungsional', 'Ahli Pertama', 10], ['Pelaksana', 'Pelaksana', 20]] as [$jenis, $jenjang, $base]) {
            $jabatan = $this->buatJabatan($unor, $jenis, $jenjang);

            foreach ([1, 2] as $i) {
                $this->actingAs($user)->post(route('admin.pegawai.store'), $this->payloadPegawai(
                    $unor, $jabatan, $this->nip($base + $i), 'Pegawai ' . $jenis . ' ' . $i
                ))->assertRedirect(route('admin.pegawai.index'));
            }

            $this->assertEquals(2, Pegawai::where('jabatan_id', $jabatan->id)->count(),
                "Jabatan {$jenis} harus boleh diisi lebih dari satu pegawai.");
        }
    }

    #[Test]
    public function structural_jabatan_only_accepts_one_pegawai()
    {
        $user = User::where('role', 'admin')->first();
        $unor = $this->unorInduk();
        $jabatan = $this->buatJabatan($unor, 'Struktural', 'Administrator');

        $this->actingAs($user)->post(route('admin.pegawai.store'),
            $this->payloadPegawai($unor, $jabatan, $this->nip(30), 'Pegawai Struktural 1'))
            ->assertRedirect(route('admin.pegawai.index'));

        $response = $this->actingAs($user)->post(route('admin.pegawai.store'),
            $this->payloadPegawai($unor, $jabatan, $this->nip(31), 'Pegawai Struktural 2'));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('pegawai', ['nip' => $this->nip(31)]);
        $this->assertEquals(1, Pegawai::where('jabatan_id', $jabatan->id)->count());
    }

    #[Test]
    public function structural_seat_is_freed_when_its_penempatan_is_no_longer_active()
    {
        $user = User::where('role', 'admin')->first();
        $unor = $this->unorInduk();
        $jabatan = $this->buatJabatan($unor, 'Struktural', 'Administrator');

        $this->actingAs($user)->post(route('admin.pegawai.store'),
            $this->payloadPegawai($unor, $jabatan, $this->nip(40), 'Penghuni Lama'));

        // Kursi dihitung dari penempatan aktif, sama seperti Bezetting.
        PenempatanPegawai::where('jabatan_id', $jabatan->id)->update(['is_active' => false]);

        $this->actingAs($user)->post(route('admin.pegawai.store'),
            $this->payloadPegawai($unor, $jabatan, $this->nip(41), 'Penghuni Baru'))
            ->assertRedirect(route('admin.pegawai.index'));

        $this->assertEquals(2, Pegawai::where('jabatan_id', $jabatan->id)->count());
    }

    #[Test]
    public function update_without_changing_jabatan_is_not_blocked()
    {
        $user = User::where('role', 'admin')->first();
        $unor = $this->unorInduk();
        $jabatan = $this->buatJabatan($unor, 'Struktural', 'Administrator');

        $this->actingAs($user)->post(route('admin.pegawai.store'),
            $this->payloadPegawai($unor, $jabatan, $this->nip(50), 'Pegawai Struktural'));
        $pegawai = Pegawai::where('nip', $this->nip(50))->firstOrFail();

        // Pegawai tidak boleh memblokir dirinya sendiri di kursinya sendiri.
        $this->actingAs($user)->put(route('admin.pegawai.update', $pegawai),
            $this->payloadPegawai($unor, $jabatan, $this->nip(50), 'Pegawai Struktural Diubah'))
            ->assertRedirect(route('admin.pegawai.index'));

        $this->assertSame('Pegawai Struktural Diubah', $pegawai->fresh()->nama);
    }

    #[Test]
    public function update_rejects_moving_pegawai_to_a_filled_structural_jabatan()
    {
        $user = User::where('role', 'admin')->first();
        $unor = $this->unorInduk();

        $terisi = $this->buatJabatan($unor, 'Struktural', 'Administrator');
        $this->actingAs($user)->post(route('admin.pegawai.store'),
            $this->payloadPegawai($unor, $terisi, $this->nip(60), 'Penghuni Kursi'));

        $fungsional = $this->buatJabatan($unor, 'Fungsional', 'Ahli Pertama');
        $this->actingAs($user)->post(route('admin.pegawai.store'),
            $this->payloadPegawai($unor, $fungsional, $this->nip(61), 'Pegawai Pindah'));

        $pegawai = Pegawai::where('nip', $this->nip(61))->firstOrFail();

        $this->actingAs($user)->put(route('admin.pegawai.update', $pegawai),
            $this->payloadPegawai($unor, $terisi, $this->nip(61), 'Pegawai Pindah'))
            ->assertSessionHas('error');

        $this->assertSame($fungsional->id, $pegawai->fresh()->jabatan_id,
            'Jabatan tidak boleh berubah saat validasi gagal.');
    }

    #[Test]
    public function updating_jabatan_creates_new_penempatan_and_deactivates_old()
    {
        $user = User::where('role', 'admin')->first();
        $unor = $this->unorInduk();
        $awal = $this->buatJabatan($unor, 'Fungsional', 'Ahli Pertama');
        $tujuan = $this->buatJabatan($unor, 'Fungsional', 'Ahli Muda');

        $this->actingAs($user)->post(
            route('admin.pegawai.store'),
            $this->payloadPegawai($unor, $awal, $this->nip(2), 'Pegawai Uji')
        )->assertRedirect(route('admin.pegawai.index'));

        $pegawai = Pegawai::where('nip', $this->nip(2))->firstOrFail();
        $pegawai->load('penempatanAktif');
        $oldPenempatanId = $pegawai->penempatanAktif->id ?? null;
        $this->assertNotNull($oldPenempatanId, 'Pegawai harus punya penempatan awal');

        $response = $this->actingAs($user)->put(route('admin.pegawai.update', $pegawai), [
            'nip' => $pegawai->nip,
            'nama' => $pegawai->nama,
            'jenis_kepegawaian' => $pegawai->jenis_kepegawaian,
            'tanggal_lahir' => $pegawai->tanggal_lahir->format('Y-m-d'),
            'golongan_pangkat' => $pegawai->golongan_pangkat,
            'pendidikan' => $pegawai->pendidikan,
            'induk_id' => $unor->id,
            'jabatan_id' => $tujuan->id,
        ]);

        $response->assertRedirect(route('admin.pegawai.index'));

        $pegawai->refresh();

        // Penempatan lama harus nonaktif
        $oldPenempatan = PenempatanPegawai::find($oldPenempatanId);
        $this->assertFalse((bool) $oldPenempatan->is_active, 'Penempatan lama harus nonaktif');

        // Penempatan baru harus aktif
        $this->assertNotNull($pegawai->penempatanAktif);
        $this->assertEquals($tujuan->id, $pegawai->penempatanAktif->jabatan_id);
    }
}
