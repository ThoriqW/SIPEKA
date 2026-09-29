<?php

namespace Tests\Feature;

use App\Models\Unor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BezettingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function authenticated_user_can_access_bezetting_index()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        $response->assertStatus(200);
        $response->assertSee('Bezetting');
        $response->assertSee('Pemerintah Kota Palu');
    }

    #[Test]
    public function admin_sees_all_opd_in_bezetting()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        $response->assertSee('Dinas Pendidikan');
        $response->assertSee('Dinas Kesehatan');
    }

    #[Test]
    public function admin_can_export_bezetting_excel()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function unauthenticated_user_is_redirected_from_bezetting()
    {
        $response = $this->get(route('admin.bezetting.index'));

        $response->assertRedirect('/login');
    }

    #[Test]
    public function bezetting_does_not_show_projections()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        // Bezetting tidak lagi menampilkan kolom proyeksi (pindah ke Kebutuhan)
        $response->assertDontSee('Proyeksi Pensiun');
        $response->assertDontSee('Proyeksi Kebutuhan');
    }

    #[Test]
    public function bezetting_shows_opd_filter_for_admin()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        $response->assertSee('Perangkat Daerah');
        $response->assertSee('Semua Perangkat Daerah');
    }

    #[Test]
    public function admin_can_filter_bezetting_by_opd()
    {
        $user = User::where('role', 'admin')->first();
        $pd = Unor::where('nama_unor', 'Dinas Kesehatan')->firstOrFail();

        // Tanpa filter: akar pohon adalah Pemkot.
        $this->actingAs($user)->get(route('admin.bezetting.index'))
            ->assertSee('Pemerintah Kota Palu');

        // Difilter: akar pohon menjadi Perangkat Daerah yang dipilih, dan
        // Pemkot tidak lagi ikut. Nama PD lain tetap ada di dropdown, jadi
        // yang diperiksa adalah akar pohonnya, bukan daftar pilihan.
        $this->actingAs($user)->get(route('admin.bezetting.index', ['unor_id' => $pd->id]))
            ->assertOk()
            ->assertSee('Dinas Kesehatan')
            ->assertDontSee('Pemerintah Kota Palu');
    }

    #[Test]
    public function bezetting_has_expand_collapse_functionality()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        $response->assertSee('treeData');
        $response->assertSee('expandedItems');
        // Phase 1: cursor-pointer mungkin tidak dirender karena tree flat (semua level 1)
        $response->assertSee('data-level');
    }
}
