<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BezettingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function bkd_can_access_bezetting_index()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        $response->assertStatus(200);
        $response->assertSee('Bezetting');
        $response->assertSee('Instansi Pemerintah Kota Palu');
    }

    /** @test */
    public function admin_opd_can_access_bezetting_index()
    {
        $user = User::where('role', 'admin_opd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        $response->assertStatus(200);
        $response->assertSee('Bezetting');
    }

    /** @test */
    public function bkd_sees_all_opd_in_bezetting()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        $response->assertSee('Kepala Dinas Pendidikan');
        $response->assertSee('Kepala Dinas Kesehatan');
    }

    /** @test */
    public function admin_opd_sees_only_own_opd_in_bezetting()
    {
        $user = User::where('email', 'admin@dikbud.palu.go.id')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        $response->assertStatus(200);
        $response->assertSee('Kepala Dinas Pendidikan');
        $response->assertDontSee('Kepala Dinas Kesehatan');
    }

    /** @test */
    public function bkd_can_export_bezetting_excel()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function admin_opd_can_export_bezetting_excel()
    {
        $user = User::where('role', 'admin_opd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function unauthenticated_user_is_redirected_from_bezetting()
    {
        $response = $this->get(route('admin.bezetting.index'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function bezetting_shows_pensiun_and_kebutuhan_projections()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        $response->assertSee('Proyeksi Pensiun');
        $response->assertSee('Proyeksi Kebutuhan');
    }

    /** @test */
    public function bezetting_has_expand_collapse_functionality()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        $response->assertSee('treeData');
        $response->assertSee('expandedItems');
        $response->assertSee('cursor-pointer');
    }
}
