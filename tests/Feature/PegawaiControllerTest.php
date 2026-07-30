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
        $jabatan = Jabatan::with('sotkEntries')->first();
        $unorId = $jabatan->sotkEntries->first()?->unor_id;

        $response = $this->actingAs($user)->post(route('admin.pegawai.store'), [
            'nip' => '200001012025011001',
            'nama' => 'Test Pegawai Baru',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '2000-01-01',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'S1',
            'jabatan_id' => $jabatan->id,
        ]);

        $response->assertRedirect(route('admin.pegawai.index'));

        $pegawai = Pegawai::where('nip', '200001012025011001')->first();
        $this->assertNotNull($pegawai);
        $this->assertEquals($jabatan->jenjang, $pegawai->jenjang);

        // Harus ada penempatan aktif
        $this->assertNotNull($pegawai->penempatanAktif);
        $this->assertEquals($unorId, $pegawai->penempatanAktif->unor_id);
        $this->assertEquals($jabatan->id, $pegawai->penempatanAktif->jabatan_id);
    }

    #[Test]
    public function multiple_pegawai_can_share_same_struktural_jabatan()
    {
        $user = User::where('role', 'admin')->first();
        $jabatan = Jabatan::where('kode_jabatan', 'DIKBUD-001')->first(); // Kepala Dinas (struktural)

        // Tambah pegawai pertama
        Pegawai::create([
            'nip' => '200001012025011001', 'nama' => 'Pegawai 1',
            'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => '2000-01-01',
            'golongan_pangkat' => 'III/a', 'pendidikan' => 'S1',
            'jenjang' => $jabatan->jenjang, 'jabatan_id' => $jabatan->id,
        ]);

        // Tambah pegawai kedua pada jabatan struktural yang SAMA — HARUS DITERIMA
        $response = $this->actingAs($user)->post(route('admin.pegawai.store'), [
            'nip' => '200002012025011002',
            'nama' => 'Pegawai 2',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '2000-02-01',
            'golongan_pangkat' => 'III/b',
            'pendidikan' => 'S1',
            'jabatan_id' => $jabatan->id,
        ]);

        $response->assertRedirect(route('admin.pegawai.index'));
        // Seeder already has 1 pegawai on this jabatan + 2 we just created = 3
        $this->assertEquals(3, Pegawai::where('jabatan_id', $jabatan->id)->count());
    }

    #[Test]
    public function updating_jabatan_creates_new_penempatan_and_deactivates_old()
    {
        $user = User::where('role', 'admin')->first();
        $pegawai = Pegawai::with('penempatanAktif')->first();
        $newJabatan = Jabatan::where('id', '!=', $pegawai->jabatan_id)->first();

        $oldPenempatanId = $pegawai->penempatanAktif->id ?? null;
        $this->assertNotNull($oldPenempatanId, 'Pegawai harus punya penempatan awal');

        $response = $this->actingAs($user)->put(route('admin.pegawai.update', $pegawai), [
            'nip' => $pegawai->nip,
            'nama' => $pegawai->nama,
            'jenis_kepegawaian' => $pegawai->jenis_kepegawaian,
            'tanggal_lahir' => $pegawai->tanggal_lahir->format('Y-m-d'),
            'golongan_pangkat' => $pegawai->golongan_pangkat,
            'pendidikan' => $pegawai->pendidikan,
            'jabatan_id' => $newJabatan->id,
        ]);

        $response->assertRedirect(route('admin.pegawai.index'));

        $pegawai->refresh();

        // Penempatan lama harus nonaktif
        if ($oldPenempatanId) {
            $oldPenempatan = PenempatanPegawai::find($oldPenempatanId);
            $this->assertFalse((bool) $oldPenempatan->is_active, 'Penempatan lama harus nonaktif');
        }

        // Penempatan baru harus aktif
        $this->assertNotNull($pegawai->penempatanAktif);
        $this->assertEquals($newJabatan->id, $pegawai->penempatanAktif->jabatan_id);
    }
}
