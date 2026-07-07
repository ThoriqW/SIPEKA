<?php

namespace Tests\Unit;

use App\Models\Jabatan;
use App\Models\Opd;
use App\Models\Pegawai;
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

        // Buat OPD
        $opd = Opd::create(['nama_opd' => 'Dinas Test', 'kode_opd' => 'DT-001']);

        // Buat hierarki jabatan:
        // Level 1: Kepala OPD (Struktural, induk=null)
        // Level 2: Sekretariat (Struktural, induk=Kepala)
        // Level 3: Sub Bagian (Struktural, induk=Sekretariat)
        // Level 4: Pelaksana (Pelaksana, induk=Sub Bagian)
        $kepala = Jabatan::create([
            'nama_jabatan' => 'Kepala Dinas Test',
            'kode_jabatan' => 'DT-001',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 15,
            'jenjang' => 'Pimpinan Tinggi Pratama',
            'kebutuhan' => 1,
            'opd_id' => $opd->id,
            'induk_jabatan_id' => null,
        ]);

        $sekretariat = Jabatan::create([
            'nama_jabatan' => 'Sekretariat',
            'kode_jabatan' => 'DT-002',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 13,
            'jenjang' => 'Ahli Madya',
            'kebutuhan' => 1,
            'opd_id' => $opd->id,
            'induk_jabatan_id' => $kepala->id,
        ]);

        $subbag = Jabatan::create([
            'nama_jabatan' => 'Sub Bagian Keuangan',
            'kode_jabatan' => 'DT-003',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 10,
            'jenjang' => 'Ahli Pertama',
            'kebutuhan' => 1,
            'opd_id' => $opd->id,
            'induk_jabatan_id' => $sekretariat->id,
        ]);

        Jabatan::create([
            'nama_jabatan' => 'Pengelola Keuangan',
            'kode_jabatan' => 'DT-004',
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 6,
            'jenjang' => 'Pelaksana',
            'kebutuhan' => 3,
            'opd_id' => $opd->id,
            'induk_jabatan_id' => $subbag->id,
        ]);

        // Tambahkan pegawai pada jabatan Pelaksana
        Pegawai::create([
            'nama' => 'Test Pegawai',
            'nip' => '199001012020011001',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1990-01-01',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'S1',
            'jenjang' => 'Pelaksana',
            'opd_id' => $opd->id,
            'jabatan_id' => 4, // Pengelola Keuangan
        ]);

        $this->service = app(FlattenedTreeService::class);
    }

    #[Test]
    public function it_builds_flat_tree_with_correct_number_of_rows()
    {
        $tree = $this->service->buildFlatTree(opdId: 1);

        $this->assertCount(4, $tree, 'Should have 4 jabatan rows');
    }

    #[Test]
    public function it_assigns_correct_levels()
    {
        $tree = $this->service->buildFlatTree(opdId: 1);

        $levels = array_column($tree, 'level');
        $this->assertEquals([1, 2, 3, 4], $levels, 'Levels should be 1,2,3,4 in depth-first order');
    }

    #[Test]
    public function it_orders_depth_first()
    {
        $tree = $this->service->buildFlatTree(opdId: 1);

        $names = array_column($tree, 'nama_jabatan');
        $this->assertEquals('Kepala Dinas Test', $names[0]);
        $this->assertEquals('Sekretariat', $names[1]);
        $this->assertEquals('Sub Bagian Keuangan', $names[2]);
        $this->assertEquals('Pengelola Keuangan', $names[3]);
    }

    #[Test]
    public function it_includes_root_when_requested()
    {
        $tree = $this->service->buildFlatTree(opdId: 1, includeRoot: true);

        $this->assertCount(5, $tree);
        $this->assertEquals(0, $tree[0]['level']);
        $this->assertEquals('Pemerintah Kota Palu', $tree[0]['nama_jabatan']);
        $this->assertEquals(0, $tree[0]['id']);
    }

    #[Test]
    public function it_sets_has_children_correctly()
    {
        $tree = $this->service->buildFlatTree(opdId: 1);

        $kepala = $tree[0];
        $pelaksana = $tree[3];

        $this->assertTrue($kepala['has_children'], 'Kepala should have children');
        $this->assertFalse($pelaksana['has_children'], 'Pelaksana should not have children');
    }

    #[Test]
    public function it_sets_parent_id_correctly()
    {
        $tree = $this->service->buildFlatTree(opdId: 1);

        // Level 1 (Kepala): parent_id = null (no root)
        $this->assertNull($tree[0]['parent_id']);
        // Level 2: parent_id = Kepala's id
        $this->assertEquals(1, $tree[1]['parent_id']);
    }

    #[Test]
    public function it_computes_bezetting()
    {
        $tree = $this->service->buildFlatTree(opdId: 1);

        $pelaksana = $tree[3];
        $this->assertEquals(1, $pelaksana['bezetting']);
        $this->assertEquals(3, $pelaksana['kebutuhan']);
        $this->assertEquals(-2, $pelaksana['selisih']);
    }

    #[Test]
    public function it_computes_projections_when_requested()
    {
        $tree = $this->service->buildFlatTree(opdId: 1, withProjections: true);

        $pelaksana = $tree[3];
        $this->assertArrayHasKey('kebutuhan_proyeksi', $pelaksana);
        $this->assertArrayHasKey('pensiun_proyeksi', $pelaksana);
        $this->assertCount(5, $pelaksana['kebutuhan_proyeksi']);

        // Kebutuhan=3, Bezetting=1 → shortfall=2
        // Kebutuhan Thn 1 = max(3-1, 0) + Pensiun Thn 1 = 2 + Pensiun Thn 1
        $this->assertGreaterThanOrEqual(2, $pelaksana['kebutuhan_proyeksi'][1]);
    }

    #[Test]
    public function it_handles_empty_opd()
    {
        $tree = $this->service->buildFlatTree(opdId: 9999);

        $this->assertIsArray($tree);
        $this->assertCount(0, $tree);
    }

    #[Test]
    public function struktural_has_kebutuhan_one_and_selisih_computed()
    {
        $tree = $this->service->buildFlatTree(opdId: 1);

        $kepala = $tree[0]; // Level 1 Kepala = Struktural
        $this->assertEquals(1, $kepala['kebutuhan']);
        $this->assertNotNull($kepala['selisih']);
    }

    #[Test]
    public function it_computes_projections_per_jabatan_not_per_opd()
    {
        // Create another jabatan in same OPD with an OLD employee (near retirement)
        $opd = Opd::first();

        $jabatanTua = Jabatan::create([
            'nama_jabatan' => 'Jabatan Pegawai Tua',
            'kode_jabatan' => 'DT-TUA',
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 6,
            'jenjang' => 'Pelaksana',
            'kebutuhan' => 2,
            'opd_id' => $opd->id,
            'induk_jabatan_id' => null,
        ]);

        // Pegawai tua: lahir 1970, Pelaksana → BUP 58 → pensiun 2028 = Tahun ke-3 (T=2026)
        Pegawai::create([
            'nama' => 'Pegawai Tua',
            'nip' => '197001012020011001',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1970-01-01',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'S1',
            'jenjang' => 'Pelaksana',
            'opd_id' => $opd->id,
            'jabatan_id' => $jabatanTua->id,
        ]);

        // Re-run with projections
        $tree = $this->service->buildFlatTree(opdId: $opd->id, withProjections: true);

        // Find rows by jabatan name
        $rowTua = collect($tree)->first(fn($r) => $r['nama_jabatan'] === 'Jabatan Pegawai Tua');
        $rowMuda = collect($tree)->first(fn($r) => $r['nama_jabatan'] === 'Pengelola Keuangan');

        $this->assertNotNull($rowTua, 'Jabatan Tua should be in tree');
        $this->assertNotNull($rowMuda, 'Pengelola Keuangan should be in tree');

        // Pegawai Tua (1970) → pensiun 2028 → proyeksi tahun ke-3 = 1
        $this->assertEquals(1, $rowTua['pensiun_proyeksi'][3], 'Old employee should retire in year 3');
        $this->assertEquals(1, $rowTua['kebutuhan_proyeksi'][3], 'Jabatan Tua should need replacement in year 3');

        // Pegawai Muda (1990) → pensiun 2048 → TIDAK ada di 5 tahun proyeksi
        $this->assertEquals(0, $rowMuda['pensiun_proyeksi'][3], 'Young employee should NOT retire in year 3');
        $this->assertEquals(0, $rowMuda['kebutuhan_proyeksi'][3], 'Jabatan Muda should NOT have need in year 3');
    }
}
