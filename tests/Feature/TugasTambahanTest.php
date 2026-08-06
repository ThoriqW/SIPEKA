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
        // Ambil pegawai pertama yang punya penempatan aktif
        $guru = Pegawai::whereHas('penempatanAktif')->first();

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

    #[Test]
    public function pegawai_tidak_bisa_punya_tugas_tambahan_yang_sama_di_unor_berbeda()
    {
        $user = User::where('role', 'admin')->first();
        $pegawai = Pegawai::whereHas('penempatanAktif')->first();
        $kepsek = MasterTugasTambahan::where('nama_tugas', 'Kepala Sekolah')->first();

        // Ambil dua UNOR berbeda
        $unor1 = \App\Models\Unor::where('kode_unor', 'BKPSDM')->first();
        $unor2 = \App\Models\Unor::where('kode_unor', 'DINKES')->first();

        // Assign tugas di UNOR pertama
        TugasTambahanPegawai::create([
            'pegawai_id'        => $pegawai->id,
            'tugas_tambahan_id' => $kepsek->id,
            'unor_id'           => $unor1->id,
            'tanggal_mulai'     => '2025-01-01',
            'is_active'         => true,
        ]);

        // Coba assign tugas yang sama di UNOR kedua — harus ditolak
        $response = $this->actingAs($user)->post(
            route('admin.pegawai.tugas-tambahan.store', $pegawai),
            [
                'tugas_tambahan_id' => $kepsek->id,
                'unor_id'           => $unor2->id,
                'tanggal_mulai'     => '2025-06-01',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Pastikan tidak ada record aktif di UNOR kedua
        $this->assertDatabaseMissing('tugas_tambahan_pegawai', [
            'pegawai_id'        => $pegawai->id,
            'tugas_tambahan_id' => $kepsek->id,
            'unor_id'           => $unor2->id,
            'is_active'         => true,
        ]);
    }

    #[Test]
    public function unor_tidak_bisa_punya_tugas_tambahan_yang_sama_oleh_pegawai_berbeda()
    {
        $user = User::where('role', 'admin')->first();
        $kepsek = MasterTugasTambahan::where('nama_tugas', 'Kepala Sekolah')->first();

        // Ambil dua pegawai berbeda dengan penempatan aktif
        $pegawai1 = Pegawai::whereHas('penempatanAktif')->first();
        $pegawai2 = Pegawai::whereHas('penempatanAktif')
            ->where('id', '!=', $pegawai1->id)
            ->first();
        $this->assertNotNull($pegawai2, 'Butuh minimal 2 pegawai di seeder');

        $unor = \App\Models\Unor::where('kode_unor', 'BKPSDM')->first();

        // Pegawai 1 dapat tugas di UNOR
        TugasTambahanPegawai::create([
            'pegawai_id'        => $pegawai1->id,
            'tugas_tambahan_id' => $kepsek->id,
            'unor_id'           => $unor->id,
            'tanggal_mulai'     => '2025-01-01',
            'is_active'         => true,
        ]);

        // Pegawai 2 coba dapat tugas yang sama di UNOR yang sama — harus ditolak
        $response = $this->actingAs($user)->post(
            route('admin.pegawai.tugas-tambahan.store', $pegawai2),
            [
                'tugas_tambahan_id' => $kepsek->id,
                'unor_id'           => $unor->id,
                'tanggal_mulai'     => '2025-06-01',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Pastikan tidak ada record aktif untuk pegawai 2
        $this->assertDatabaseMissing('tugas_tambahan_pegawai', [
            'pegawai_id'        => $pegawai2->id,
            'tugas_tambahan_id' => $kepsek->id,
            'unor_id'           => $unor->id,
            'is_active'         => true,
        ]);
    }

    #[Test]
    public function pegawai_bisa_punya_tugas_tambahan_berbeda_di_unor_sama()
    {
        $user = User::where('role', 'admin')->first();
        $pegawai = Pegawai::whereHas('penempatanAktif')->first();
        $kepsek = MasterTugasTambahan::where('nama_tugas', 'Kepala Sekolah')->first();
        $plt = MasterTugasTambahan::where('nama_tugas', 'Plt. Kepala Bidang')->first();
        $unor = \App\Models\Unor::where('kode_unor', 'BKPSDM')->first();

        // Assign tugas pertama
        TugasTambahanPegawai::create([
            'pegawai_id'        => $pegawai->id,
            'tugas_tambahan_id' => $kepsek->id,
            'unor_id'           => $unor->id,
            'tanggal_mulai'     => '2025-01-01',
            'is_active'         => true,
        ]);

        // Assign tugas berbeda di UNOR sama — harus berhasil
        $response = $this->actingAs($user)->post(
            route('admin.pegawai.tugas-tambahan.store', $pegawai),
            [
                'tugas_tambahan_id' => $plt->id,
                'unor_id'           => $unor->id,
                'tanggal_mulai'     => '2025-06-01',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan kedua record aktif
        $this->assertDatabaseHas('tugas_tambahan_pegawai', [
            'pegawai_id'        => $pegawai->id,
            'tugas_tambahan_id' => $kepsek->id,
            'unor_id'           => $unor->id,
            'is_active'         => true,
        ]);
        $this->assertDatabaseHas('tugas_tambahan_pegawai', [
            'pegawai_id'        => $pegawai->id,
            'tugas_tambahan_id' => $plt->id,
            'unor_id'           => $unor->id,
            'is_active'         => true,
        ]);
    }

    #[Test]
    public function unor_bisa_punya_tugas_tambahan_berbeda_oleh_pegawai_sama()
    {
        $user = User::where('role', 'admin')->first();
        $pegawai = Pegawai::whereHas('penempatanAktif')->first();
        $kepsek = MasterTugasTambahan::where('nama_tugas', 'Kepala Sekolah')->first();
        $plt = MasterTugasTambahan::where('nama_tugas', 'Plt. Kepala Bidang')->first();
        $unor = \App\Models\Unor::where('kode_unor', 'BKPSDM')->first();

        // Assign tugas pertama
        TugasTambahanPegawai::create([
            'pegawai_id'        => $pegawai->id,
            'tugas_tambahan_id' => $kepsek->id,
            'unor_id'           => $unor->id,
            'tanggal_mulai'     => '2025-01-01',
            'is_active'         => true,
        ]);

        // Assign tugas berbeda oleh pegawai sama di UNOR sama — harus berhasil
        $response = $this->actingAs($user)->post(
            route('admin.pegawai.tugas-tambahan.store', $pegawai),
            [
                'tugas_tambahan_id' => $plt->id,
                'unor_id'           => $unor->id,
                'tanggal_mulai'     => '2025-06-01',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan kedua record aktif
        $this->assertDatabaseHas('tugas_tambahan_pegawai', [
            'pegawai_id'        => $pegawai->id,
            'tugas_tambahan_id' => $kepsek->id,
            'unor_id'           => $unor->id,
            'is_active'         => true,
        ]);
        $this->assertDatabaseHas('tugas_tambahan_pegawai', [
            'pegawai_id'        => $pegawai->id,
            'tugas_tambahan_id' => $plt->id,
            'unor_id'           => $unor->id,
            'is_active'         => true,
        ]);
    }
}
