<?php

namespace Tests\Feature;

use App\Models\Unor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function admin_can_access_unor_index()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.unor.index'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Unit Organisasi');
        $response->assertSee('Pemerintah Kota Palu');
    }

    #[Test]
    public function unor_index_shows_root_level()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.unor.index'));

        // Root "Pemerintah Kota Palu" harus muncul di halaman
        $response->assertSee('Pemerintah Kota Palu');
        // OPD level juga harus muncul
        $response->assertSee('Badan Kepegawaian dan Pengembangan Sumber Daya Manusia');
    }

    #[Test]
    public function create_unor_rejected_when_parent_id_null_and_root_exists()
    {
        $user = User::where('role', 'admin')->first();

        // Pastikan root sudah ada dari seeder
        $this->assertDatabaseHas('unor', ['parent_id' => null]);

        $response = $this->actingAs($user)->post(route('admin.unor.store'), [
            'nama_unor' => 'Dinas Tanpa Induk',
            'kode_unor' => 'DTI',
            'parent_id' => '',  // kosong = tanpa parent
        ]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertDatabaseMissing('unor', ['nama_unor' => 'Dinas Tanpa Induk']);
    }

    #[Test]
    public function create_unor_allowed_when_no_root_exists()
    {
        // Hapus semua data dependen lalu UNOR agar tidak ada root
        \Illuminate\Support\Facades\DB::table('tugas_tambahan_pegawai')->delete();
        \Illuminate\Support\Facades\DB::table('penempatan_pegawai')->delete();
        \Illuminate\Support\Facades\DB::table('kebutuhan_pegawai')->delete();
        \Illuminate\Support\Facades\DB::table('sotk')->delete();
        \Illuminate\Support\Facades\DB::table('pegawai')->delete();
        \Illuminate\Support\Facades\DB::table('jabatan')->delete();

        // Null-kan parent_id dulu (FK self-reference), lalu hapus semua UNOR
        Unor::query()->update(['parent_id' => null]);
        Unor::query()->delete();

        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->post(route('admin.unor.store'), [
            'nama_unor' => 'Root Baru',
            'kode_unor' => 'ROOT-1',
            'parent_id' => '',
        ]);

        $response->assertRedirect(route('admin.unor.index'));
        $this->assertDatabaseHas('unor', [
            'nama_unor' => 'Root Baru',
            'parent_id' => null,
        ]);
    }

    #[Test]
    public function create_unor_with_parent_succeeds_when_root_exists()
    {
        $user = User::where('role', 'admin')->first();
        $root = Unor::whereNull('parent_id')->first();

        $response = $this->actingAs($user)->post(route('admin.unor.store'), [
            'nama_unor' => 'Dinas Baru',
            'kode_unor' => 'DINAS-BARU',
            'parent_id' => $root->id,
        ]);

        $response->assertRedirect(route('admin.unor.index'));
        $this->assertDatabaseHas('unor', [
            'nama_unor' => 'Dinas Baru',
            'parent_id' => $root->id,
        ]);
    }

    #[Test]
    public function update_non_root_unor_rejected_when_set_parent_to_null()
    {
        $user = User::where('role', 'admin')->first();

        // Ambil UNOR yang bukan root (level OPD, parent_id tidak null)
        $unor = Unor::whereNotNull('parent_id')->first();
        $this->assertNotNull($unor, 'Harus ada UNOR non-root dari seeder');

        $response = $this->actingAs($user)->put(route('admin.unor.update', $unor), [
            'nama_unor' => $unor->nama_unor,
            'kode_unor' => $unor->kode_unor,
            'parent_id' => '',  // mencoba menghapus parent
        ]);

        $response->assertSessionHasErrors('parent_id');
        // Pastikan parent_id tidak berubah
        $this->assertDatabaseHas('unor', [
            'id' => $unor->id,
            'parent_id' => $unor->parent_id,
        ]);
    }

    #[Test]
    public function update_root_unor_can_keep_parent_null()
    {
        $user = User::where('role', 'admin')->first();

        $root = Unor::whereNull('parent_id')->first();
        $this->assertNotNull($root, 'Harus ada root dari seeder');

        $response = $this->actingAs($user)->put(route('admin.unor.update', $root), [
            'nama_unor' => 'Pemerintah Kota Palu Updated',
            'kode_unor' => $root->kode_unor,
            'parent_id' => '',  // root tetap tanpa parent
        ]);

        $response->assertRedirect(route('admin.unor.index'));
        $this->assertDatabaseHas('unor', [
            'id' => $root->id,
            'nama_unor' => 'Pemerintah Kota Palu Updated',
            'parent_id' => null,
        ]);
    }

    #[Test]
    public function unor_index_shows_all_roots_in_multi_root_scenario()
    {
        $user = User::where('role', 'admin')->first();

        // Simulasikan multi-root: buat root kedua langsung via DB
        $root2 = Unor::create([
            'nama_unor' => 'Root Kedua',
            'kode_unor' => 'ROOT-2',
            'parent_id' => null,
        ]);

        // Buat anak di bawah root kedua
        Unor::create([
            'nama_unor' => 'Anak Root Kedua',
            'kode_unor' => 'ANAK-ROOT-2',
            'parent_id' => $root2->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.unor.index'));

        $response->assertStatus(200);
        // Kedua root harus muncul
        $response->assertSee('Pemerintah Kota Palu');
        $response->assertSee('Root Kedua');
        $response->assertSee('Anak Root Kedua');
    }

    #[Test]
    public function cannot_delete_unor_with_children()
    {
        $user = User::where('role', 'admin')->first();

        // BKPSDM punya anak (Sekretariat, bidang-bidang)
        $bkpsdm = Unor::where('nama_unor', 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia')->first();
        $this->assertNotNull($bkpsdm);

        $response = $this->actingAs($user)->delete(route('admin.unor.destroy', $bkpsdm));

        // destroy() menggunakan back() — periksa session error, bukan exact URL
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('unor', ['id' => $bkpsdm->id]);
    }

    #[Test]
    public function unauthenticated_user_is_redirected()
    {
        $response = $this->get(route('admin.unor.index'));
        $response->assertRedirect('/login');
    }
}
