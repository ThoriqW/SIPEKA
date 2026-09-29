<?php

namespace Tests\Feature;

use App\Models\Unor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KebutuhanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function admin_can_access_kebutuhan_index()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $response->assertStatus(200);
        $response->assertSee('Kebutuhan');
        $response->assertSee('Pemerintah Kota Palu');
    }

    #[Test]
    public function admin_can_export_kebutuhan_excel()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function unauthenticated_user_is_redirected()
    {
        $response = $this->get(route('admin.kebutuhan.index'));

        $response->assertRedirect('/login');
    }

    #[Test]
    public function kebutuhan_shows_projection_columns()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $t = date('Y');
        $response->assertSee($t);
        $response->assertSee((string) ($t + 4));
    }

    #[Test]
    public function kebutuhan_shows_pensiun_and_kebutuhan_projections()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $response->assertSee('Proyeksi Pensiun');
        $response->assertSee('Proyeksi Kebutuhan');
    }

    #[Test]
    public function kebutuhan_shows_perangkat_daerah_filter()
    {
        $user = User::where('role', 'admin')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $response->assertSee('Perangkat Daerah');
        $response->assertSee('Semua Perangkat Daerah');
    }

    #[Test]
    public function filtered_kebutuhan_replaces_the_root_with_the_selected_perangkat_daerah()
    {
        $user = User::where('role', 'admin')->first();
        $pd = Unor::where('nama_unor', 'Dinas Kesehatan')->firstOrFail();

        // Tanpa filter: akar pohon adalah Pemkot.
        $this->actingAs($user)->get(route('admin.kebutuhan.index'))
            ->assertSee('Pemerintah Kota Palu');

        // Difilter: akar pohon menjadi Perangkat Daerah yang dipilih, dan
        // Pemkot tidak lagi ikut. Nama PD lain tetap ada di dropdown, jadi
        // yang diperiksa adalah akar pohonnya, bukan daftar pilihan.
        $this->actingAs($user)->get(route('admin.kebutuhan.index', ['unor_id' => $pd->id]))
            ->assertOk()
            ->assertSee('Dinas Kesehatan')
            ->assertDontSee('Pemerintah Kota Palu');
    }

    #[Test]
    public function filtered_kebutuhan_carries_the_filter_into_the_children_url()
    {
        $user = User::where('role', 'admin')->first();
        $pd = Unor::where('nama_unor', 'Dinas Kesehatan')->firstOrFail();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index', ['unor_id' => $pd->id]));

        // Tanpa ini, saat PD dibuka level barisnya dihitung dari root alami
        // sehingga indentasinya salah.
        $response->assertSee('root_unor_id=' . $pd->id, escape: false);
    }

    #[Test]
    public function kebutuhan_export_link_carries_the_active_filter()
    {
        $user = User::where('role', 'admin')->first();
        $pd = Unor::where('nama_unor', 'Dinas Kesehatan')->firstOrFail();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index', ['unor_id' => $pd->id]));

        $response->assertSee(
            route('admin.kebutuhan.export', ['unor_id' => $pd->id]),
            escape: false
        );
    }

    #[Test]
    public function kebutuhan_export_only_contains_the_filtered_perangkat_daerah()
    {
        Excel::fake();

        $user = User::where('role', 'admin')->first();
        $pd = Unor::where('nama_unor', 'Dinas Kesehatan')->firstOrFail();

        $this->actingAs($user)->get(route('admin.kebutuhan.export', ['unor_id' => $pd->id]));

        Excel::assertDownloaded('kebutuhan-' . date('Y-m-d') . '.xlsx', function ($export) {
            $names = array_map('trim', array_column($export->array(), 0));

            $this->assertContains('Dinas Kesehatan', $names);
            $this->assertNotContains('Dinas Pendidikan', $names);

            return true;
        });
    }

    #[Test]
    public function kebutuhan_export_without_filter_contains_every_perangkat_daerah()
    {
        Excel::fake();

        $user = User::where('role', 'admin')->first();

        $this->actingAs($user)->get(route('admin.kebutuhan.export'));

        Excel::assertDownloaded('kebutuhan-' . date('Y-m-d') . '.xlsx', function ($export) {
            $names = array_map('trim', array_column($export->array(), 0));

            $this->assertContains('Dinas Kesehatan', $names);
            $this->assertContains('Dinas Pendidikan', $names);

            return true;
        });
    }
}
