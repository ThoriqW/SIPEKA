<?php

namespace Tests\Feature;

use App\Models\MasterTugasTambahan;
use App\Models\Pegawai;
use App\Models\TugasTambahanPegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TugasTambahanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function admin_can_access_index()
    {
        $user = User::where('role', 'admin')->first();
        $response = $this->actingAs($user)->get(route('admin.tugas-tambahan.index'));
        $response->assertStatus(200);
        $response->assertSee('Tugas Tambahan');
    }

    #[Test]
    public function admin_can_create_tugas_tambahan()
    {
        $user = User::where('role', 'admin')->first();
        $response = $this->actingAs($user)->post(route('admin.tugas-tambahan.store'), [
            'nama_tugas' => 'Plt. Kepala Sekolah',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('master_tugas_tambahan', ['nama_tugas' => 'Plt. Kepala Sekolah']);
    }

    #[Test]
    public function admin_can_update_tugas_tambahan()
    {
        $user = User::where('role', 'admin')->first();
        $tugas = MasterTugasTambahan::first();
        $this->actingAs($user)->put(route('admin.tugas-tambahan.update', $tugas), [
            'nama_tugas' => 'Kepala Sekolah Updated',
        ]);
        $this->assertDatabaseHas('master_tugas_tambahan', ['id' => $tugas->id, 'nama_tugas' => 'Kepala Sekolah Updated']);
    }

    #[Test]
    public function admin_can_delete_unused_tugas()
    {
        $user = User::where('role', 'admin')->first();
        $tugas = MasterTugasTambahan::create(['nama_tugas' => 'Temp']);
        $this->actingAs($user)->delete(route('admin.tugas-tambahan.destroy', $tugas));
        $this->assertDatabaseMissing('master_tugas_tambahan', ['id' => $tugas->id]);
    }

    #[Test]
    public function tugas_tambahan_does_not_change_jabatan_utama()
    {
        // Seeder sudah assign Kepala Sekolah ke Guru Kelas
        $guru = Pegawai::whereHas('jabatan', function ($q) {
            $q->where('kode_jabatan', 'DIKBUD-006');
        })->first();

        $this->assertNotNull($guru);
        $this->assertNotNull($guru->jabatan_id, 'Pegawai harus memiliki jabatan utama');

        $jabatanUtama = $guru->jabatan_id;
        $penempatanUtama = $guru->penempatanAktif->unor_id;

        // Assign tugas tambahan
        $kepsek = MasterTugasTambahan::where('nama_tugas', 'Kepala Sekolah')->first();
        TugasTambahanPegawai::create([
            'pegawai_id' => $guru->id,
            'tugas_tambahan_id' => $kepsek->id,
            'unor_id' => $penempatanUtama,
            'tanggal_mulai' => '2024-01-01',
            'is_active' => true,
        ]);

        // Refresh dan cek jabatan utama TIDAK BERUBAH
        $guru->refresh();
        $this->assertEquals($jabatanUtama, $guru->jabatan_id,
            'Tugas tambahan TIDAK boleh mengubah jabatan utama');
        $this->assertEquals($penempatanUtama, $guru->penempatanAktif->unor_id,
            'Tugas tambahan TIDAK boleh mengubah penempatan utama');
    }

    #[Test]
    public function duplicate_nama_tugas_is_rejected()
    {
        $user = User::where('role', 'admin')->first();
        $response = $this->actingAs($user)->post(route('admin.tugas-tambahan.store'), [
            'nama_tugas' => 'Kepala Sekolah', // already seeded
        ]);
        $response->assertSessionHasErrors('nama_tugas');
    }
}
