<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KebutuhanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function bkd_can_access_kebutuhan_index()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $response->assertStatus(200);
        $response->assertSee('Kebutuhan');
        $response->assertSee('Instansi Pemerintah Kota Palu');
    }

    /** @test */
    public function admin_opd_can_access_kebutuhan_index()
    {
        $user = User::where('role', 'admin_opd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $response->assertStatus(200);
        $response->assertSee('Kebutuhan');
    }

    /** @test */
    public function admin_opd_does_not_see_other_opd_data()
    {
        // Admin Dikbud (opd_id=1) should NOT see Dinkes (opd_id=2) jabatan
        $user = User::where('email', 'admin@dikbud.palu.go.id')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $response->assertStatus(200);
        $response->assertSee('Kepala Dinas Pendidikan');
        $response->assertDontSee('Kepala Dinas Kesehatan');
    }

    /** @test */
    public function bkd_can_filter_by_opd()
    {
        $user = User::where('role', 'bkd')->first();

        // Filter to OPD 2 (Dinkes)
        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index', ['opd_id' => 2]));

        $response->assertStatus(200);
        $response->assertSee('Kepala Dinas Kesehatan');
        $response->assertDontSee('Kepala Dinas Pendidikan');
    }

    /** @test */
    public function bkd_can_export_kebutuhan_excel()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function admin_opd_can_export_kebutuhan_excel()
    {
        $user = User::where('role', 'admin_opd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function unauthenticated_user_is_redirected()
    {
        $response = $this->get(route('admin.kebutuhan.index'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function kebutuhan_shows_projection_columns()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $response->assertSee('Keb. Thn 1');
        $response->assertSee('Thn 5');
    }

    /** @test */
    public function kebutuhan_shows_opd_filter_for_bkd()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $response->assertSee('Filter OPD');
        $response->assertSee('Semua OPD');
    }

    /** @test */
    public function kebutuhan_does_not_show_opd_filter_for_admin_opd()
    {
        $user = User::where('role', 'admin_opd')->first();

        $response = $this->actingAs($user)->get(route('admin.kebutuhan.index'));

        $response->assertDontSee('Filter OPD');
    }
}
