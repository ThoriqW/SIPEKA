<?php

namespace Tests\Unit;

use App\Models\Jabatan;
use App\Models\KebutuhanPegawai;
use App\Models\PenempatanPegawai;
use App\Models\Pegawai;
use App\Models\Sotk;
use App\Models\Unor;
use App\Services\FlattenedTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlattenedTreeServiceTest extends TestCase
{
    use RefreshDatabase;

    private FlattenedTreeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // ── Build UNOR hierarchy ──
        $pemkot = Unor::create(['nama_unor' => 'Pemerintah Kota Palu', 'kode_unor' => 'PEMKOT', 'parent_id' => null]);
        $dikbud = Unor::create(['nama_unor' => 'Dinas Test', 'kode_unor' => 'DT', 'parent_id' => $pemkot->id]);
        $smpn = Unor::create(['nama_unor' => 'SMP Negeri 1', 'kode_unor' => 'SMPN1', 'parent_id' => $dikbud->id]);

        // ── Create jabatan ──
        $kepala = Jabatan::create([
            'nama_jabatan' => 'Kepala Dinas Test', 'kode_jabatan' => 'DT-001',
            'jenis_jabatan' => 'Struktural', 'kelas_jabatan' => 15,
           
        ]);
        $pengelola = Jabatan::create([
            'nama_jabatan' => 'Pengelola Keuangan', 'kode_jabatan' => 'DT-002',
            'jenis_jabatan' => 'Pelaksana', 'kelas_jabatan' => 6,
           
        ]);
        $guru = Jabatan::create([
            'nama_jabatan' => 'Guru Matematika', 'kode_jabatan' => 'SMP-001',
            'jenis_jabatan' => 'Fungsional', 'kelas_jabatan' => 8,
           
        ]);

        // ── Assign jabatan ke UNOR via SOTK ──
        Sotk::create(['unor_id' => $dikbud->id, 'jabatan_id' => $kepala->id]);
        Sotk::create(['unor_id' => $dikbud->id, 'jabatan_id' => $pengelola->id]);
        Sotk::create(['unor_id' => $smpn->id, 'jabatan_id' => $guru->id]);

        // ── Kebutuhan ──
        KebutuhanPegawai::create(['unor_id' => $dikbud->id, 'jabatan_id' => $kepala->id, 'tahun' => null, 'jumlah' => 1]);
        KebutuhanPegawai::create(['unor_id' => $dikbud->id, 'jabatan_id' => $pengelola->id, 'tahun' => null, 'jumlah' => 3]);
        KebutuhanPegawai::create(['unor_id' => $smpn->id, 'jabatan_id' => $guru->id, 'tahun' => null, 'jumlah' => 4]);

        // ── Pegawai + Penempatan ──
        $p1 = Pegawai::create([
            'nama' => 'Test Pegawai', 'nip' => '199001012020011001',
            'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => '1990-01-01',
            'golongan_pangkat' => 'III/a', 'pendidikan' => 'S1',
            'jabatan_id' => $pengelola->id,
        ]);
        PenempatanPegawai::create([
            'pegawai_id' => $p1->id, 'unor_id' => $dikbud->id, 'jabatan_id' => $pengelola->id,
            'tanggal_mulai' => '2020-01-01', 'is_active' => true,
        ]);

        $this->service = app(FlattenedTreeService::class);
    }

    #[Test]
    public function it_builds_tree_with_unor_and_jabatan_rows()
    {
        $tree = $this->service->buildFlatTree();

        // Should have: DT (unor) + 2 jabatan + SMPN1 (unor) + 1 jabatan = 5 rows
        $this->assertGreaterThanOrEqual(5, count($tree));
    }

    #[Test]
    public function it_has_root_unor_at_level_zero()
    {
        $tree = $this->service->buildFlatTree();

        $this->assertGreaterThan(0, count($tree));
        $this->assertEquals('unor', $tree[0]['type']);
        $this->assertEquals(0, $tree[0]['level']);
        $this->assertStringContainsString('Pemerintah Kota Palu', $tree[0]['nama_jabatan']);
    }

    #[Test]
    public function it_assigns_unor_and_jabatan_types()
    {
        $tree = $this->service->buildFlatTree();

        $types = array_column($tree, 'type');
        $this->assertContains('unor', $types);
        $this->assertContains('jabatan', $types);
    }

    #[Test]
    public function it_places_jabatan_under_correct_unor()
    {
        $tree = $this->service->buildFlatTree();

        // Find a jabatan row and check its parent_id references a unor
        $jabatanRows = array_filter($tree, fn($r) => $r['type'] === 'jabatan');
        $this->assertNotEmpty($jabatanRows);

        foreach ($jabatanRows as $row) {
            $this->assertStringStartsWith('u-', (string) $row['parent_id']);
        }
    }

    #[Test]
    public function it_includes_child_unor_in_tree()
    {
        $tree = $this->service->buildFlatTree();

        $names = array_column($tree, 'nama_jabatan');
        $this->assertContains('SMP Negeri 1', $names);
    }

    #[Test]
    public function it_computes_kebutuhan_from_kebutuhan_pegawai_table()
    {
        $tree = $this->service->buildFlatTree();

        $pengelola = collect($tree)->first(fn($r) => $r['nama_jabatan'] === 'Pengelola Keuangan');
        $this->assertNotNull($pengelola);
        $this->assertEquals(3, $pengelola['kebutuhan']);
    }

    #[Test]
    public function it_computes_bezetting_from_penempatan()
    {
        $tree = $this->service->buildFlatTree();

        $pengelola = collect($tree)->first(fn($r) => $r['nama_jabatan'] === 'Pengelola Keuangan');
        $this->assertNotNull($pengelola);
        $this->assertEquals(1, $pengelola['bezetting']);
    }

    #[Test]
    public function it_computes_selisih_correctly()
    {
        $tree = $this->service->buildFlatTree();

        $pengelola = collect($tree)->first(fn($r) => $r['nama_jabatan'] === 'Pengelola Keuangan');
        // kebutuhan=3, bezetting=1 → selisih = -2
        $this->assertEquals(-2, $pengelola['selisih']);
    }

    #[Test]
    public function it_handles_empty_data()
    {
        // Delete in FK-safe order
        Sotk::query()->delete();
        KebutuhanPegawai::query()->delete();
        PenempatanPegawai::query()->delete();
        Pegawai::query()->delete();
        Jabatan::query()->delete();
        // Delete child UNORs first (FK self-ref)
        Unor::whereNotNull('parent_id')->delete();
        Unor::whereNull('parent_id')->delete();

        $tree = $this->service->buildFlatTree();
        $this->assertIsArray($tree);
        $this->assertCount(0, $tree);
    }

    #[Test]
    public function it_filters_by_unor()
    {
        $dikbud = Unor::where('kode_unor', 'DT')->first();
        $tree = $this->service->buildFlatTree(unorId: $dikbud->id);

        // Should only contain Dinas Test and its descendants
        $names = array_column($tree, 'nama_jabatan');
        $this->assertContains('Dinas Test', $names);
        $this->assertContains('Kepala Dinas Test', $names);
        $this->assertContains('Pengelola Keuangan', $names);
    }
}
