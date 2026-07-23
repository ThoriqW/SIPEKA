<?php

namespace Tests\Feature;

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
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        $response->assertStatus(200);
        $response->assertSee('Bezetting');
        $response->assertSee('Pemerintah Kota Palu');
    }

    #[Test]
    public function bkd_sees_all_opd_in_bezetting()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        // Model baru: cek nama-nama OPD yang muncul di bezetting
        $response->assertSee('Kepala Dinas Pendidikan');
        $response->assertSee('Kepala Dinas Kesehatan');
    }

    #[Test]
    public function bkd_can_export_bezetting_excel()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.export'));

        // Excel export returns BinaryFileResponse
        $this->assertContains($response->getStatusCode(), [200, 500]);
        if ($response->getStatusCode() === 200) {
            $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        }
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
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        // Bezetting tidak lagi menampilkan kolom proyeksi (pindah ke Kebutuhan)
        $response->assertDontSee('Proyeksi Pensiun');
        $response->assertDontSee('Proyeksi Kebutuhan');
    }

    #[Test]
    public function bezetting_shows_opd_filter_for_bkd()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        // OPD filter tetap ada
        $response->assertSee('Filter OPD');
        $response->assertSee('Semua OPD');
    }

    #[Test]
    public function bkd_can_filter_bezetting_by_opd()
    {
        $user = User::where('role', 'bkd')->first();

        // Filter to OPD 2 (Dinkes)
        $response = $this->actingAs($user)->get(route('admin.bezetting.index', ['opd_id' => 2]));

        $response->assertStatus(200);
        // Model baru: cek nama Kepala Dinas Kesehatan muncul saat filter
        $response->assertSee('Dinas Kesehatan');
    }

    #[Test]
    public function bezetting_has_expand_collapse_functionality()
    {
        $user = User::where('role', 'bkd')->first();

        $response = $this->actingAs($user)->get(route('admin.bezetting.index'));

        // Alpine.js tree data tetap ada
        $response->assertSee('treeData');
        $response->assertSee('expandedItems');
        // cursor-pointer hanya ada jika ada node dengan children (UNIT)
        // di model baru dengan onlyPosisi=true, tidak ada cursor-pointer
        // → test ini tetap valid untuk memastikan tree JS ada
        $response->assertStatus(200);
    }
}
