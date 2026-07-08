<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    public function bkd_can_access_kebutuhan_index()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $response->assertStatus(200);
        $response->assertSee('Kebutuhan');
        $response->assertSee('Pemerintah Kota Palu');
    }

    #[Test]
    public function admin_opd_can_access_kebutuhan_index()
    {
        $user = User::where('role', 'admin_opd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $response->assertStatus(200);
        $response->assertSee('Kebutuhan');
    }

    #[Test]
    public function admin_opd_does_not_see_other_opd_data()
    {
        // Admin Dikbud (opd_id=1) should NOT see Dinkes (opd_id=2) jabatan
        $user = User::where('email', 'admin@dikbud.palu.go.id')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $response->assertStatus(200);
        $response->assertSee('Kepala Dinas Pendidikan');
        $response->assertDontSee('Kepala Dinas Kesehatan');
    }

    #[Test]
    public function bkd_can_export_kebutuhan_excel()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function admin_opd_can_export_kebutuhan_excel()
    {
        $user = User::where('role', 'admin_opd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('Content-Type'));
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
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $t = date('Y');
        $response->assertSee($t);
        $response->assertSee((string) ($t + 4));
    }

    #[Test]
    public function kebutuhan_shows_pensiun_and_kebutuhan_projections()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        // Kebutuhan menampilkan proyeksi pensiun dan kebutuhan
        $response->assertSee('Proyeksi Pensiun');
        $response->assertSee('Proyeksi Kebutuhan');
    }

    #[Test]
    public function kebutuhan_does_not_show_opd_filter()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        // Kebutuhan tidak menampilkan filter OPD (menampilkan seluruh OPD)
        $response->assertDontSee('Filter OPD');
    }
}
