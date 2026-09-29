<?php

namespace Tests\Feature;

use App\Models\Unor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Endpoint pemuat baris anak untuk pohon Kebutuhan & Bezetting, plus
 * pemeriksaan bahwa halaman awal tidak lagi mengirim seluruh isi pohon.
 */
class TreeChildrenEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Unor $bkpsdm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::where('role', 'admin')->first();
        $this->bkpsdm = Unor::where('nama_unor', 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia')->firstOrFail();
    }

    // ─────────────────── Muatan awal halaman ───────────────────

    #[Test]
    public function kebutuhan_index_only_renders_root_and_direct_children()
    {
        $response = $this->actingAs($this->user)->get(route('admin.kebutuhan.index'));

        $response->assertOk();
        $response->assertSee('Pemerintah Kota Palu');   // root, level 0
        $response->assertSee('Dinas Pendidikan');       // anak langsung, level 1
        // Bidang berada di level 2 — belum boleh ikut ter-render.
        $response->assertDontSee('Bidang Mutasi dan Promosi');
    }

    #[Test]
    public function bezetting_index_only_renders_root_and_direct_children()
    {
        $response = $this->actingAs($this->user)->get(route('admin.bezetting.index'));

        $response->assertOk();
        $response->assertSee('Pemerintah Kota Palu');
        $response->assertSee('Dinas Kesehatan');
        $response->assertDontSee('Bidang Mutasi dan Promosi');
    }

    #[Test]
    public function index_marks_expandable_rows_with_their_children_url()
    {
        $response = $this->actingAs($this->user)->get(route('admin.bezetting.index'));

        $response->assertSee('data-children-url', escape: false);
        $response->assertSee(route('admin.bezetting.children', ['unor' => $this->bkpsdm->id]), escape: false);
    }

    #[Test]
    public function tree_container_carries_the_column_count_for_status_rows()
    {
        // Kolom dipakai baris "Memuat…"/error. Kebutuhan punya 16 kolom
        // (2 baris header), Bezetting 7.
        $this->actingAs($this->user)->get(route('admin.kebutuhan.index'))
            ->assertSee('data-colspan="16"', escape: false);

        $this->actingAs($this->user)->get(route('admin.bezetting.index'))
            ->assertSee('data-colspan="7"', escape: false);
    }

    #[Test]
    public function filtered_bezetting_index_carries_the_filter_into_the_children_url()
    {
        $response = $this->actingAs($this->user)->get(
            route('admin.bezetting.index', ['unor_id' => $this->bkpsdm->id])
        );

        $response->assertOk();
        $response->assertSee('root_unor_id=' . $this->bkpsdm->id, escape: false);
    }

    // ─────────────────── Endpoint baris anak ───────────────────

    #[Test]
    public function unauthenticated_user_is_redirected_from_children_endpoint()
    {
        $this->get(route('admin.bezetting.children', ['unor' => $this->bkpsdm->id]))
            ->assertRedirect('/login');

        $this->get(route('admin.kebutuhan.children', ['unor' => $this->bkpsdm->id]))
            ->assertRedirect('/login');
    }

    #[Test]
    public function bezetting_children_returns_direct_children_only()
    {
        $response = $this->actingAs($this->user)->get(
            route('admin.bezetting.children', ['unor' => $this->bkpsdm->id])
        );

        $response->assertOk();
        $response->assertJsonStructure(['html', 'count']);

        $html = $response->json('html');
        $this->assertStringContainsString('Bidang Mutasi dan Promosi', $html);      // level 2
        $this->assertStringNotContainsString('Sub Bagian Umum dan Kepegawaian', $html); // level 3
        $this->assertGreaterThan(0, $response->json('count'));
    }

    #[Test]
    public function kebutuhan_children_returns_direct_children_only()
    {
        $response = $this->actingAs($this->user)->get(
            route('admin.kebutuhan.children', ['unor' => $this->bkpsdm->id])
        );

        $response->assertOk();

        $html = $response->json('html');
        $this->assertStringContainsString('Bidang Mutasi dan Promosi', $html);
        $this->assertStringNotContainsString('Sub Bagian Umum dan Kepegawaian', $html);
    }

    #[Test]
    public function children_rows_are_numbered_from_one()
    {
        $html = $this->actingAs($this->user)
            ->get(route('admin.bezetting.children', ['unor' => $this->bkpsdm->id]))
            ->json('html');

        // Fragmen selalu mulai dari nomor 1, berapa pun posisi node di pohon.
        $this->assertMatchesRegularExpression('/>\s*1\s*</', $html);
    }

    #[Test]
    public function children_endpoint_returns_404_for_unknown_unor()
    {
        $this->actingAs($this->user)
            ->get(route('admin.bezetting.children', ['unor' => 999999]))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->get(route('admin.kebutuhan.children', ['unor' => 999999]))
            ->assertNotFound();
    }

    #[Test]
    public function expanding_a_leaf_unor_returns_an_empty_fragment()
    {
        $leaf = Unor::create([
            'nama_unor' => 'Unit Tanpa Isi', 'kode_unor' => 'KOSONG',
            'parent_id' => $this->bkpsdm->id,
        ]);

        $response = $this->actingAs($this->user)->get(
            route('admin.bezetting.children', ['unor' => $leaf->id])
        );

        $response->assertOk();
        $this->assertSame(0, $response->json('count'));
        $this->assertSame('', trim($response->json('html')));
    }
}
