<?php

namespace Tests\Unit;

use App\Models\Jabatan;
use App\Models\KebutuhanPegawai;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Sotk;
use App\Models\Unor;
use App\Services\FlattenedTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BezettingCalculationTest extends TestCase
{
    use RefreshDatabase;

    private FlattenedTreeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $unor = Unor::create(['nama_unor' => 'Dinas Test', 'kode_unor' => 'DT', 'parent_id' => null]);
        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Pengelola Keuangan', 'kode_jabatan' => 'DT-001',
            'jenis_jabatan' => 'Pelaksana', 'kelas_jabatan' => 6,
            'jenjang' => 'Pelaksana',
        ]);
        Sotk::create(['unor_id' => $unor->id, 'jabatan_id' => $jabatan->id]);

        KebutuhanPegawai::create([
            'unor_id' => $unor->id, 'jabatan_id' => $jabatan->id,
            'tahun' => null, 'jumlah' => 3,
        ]);

        // 2 pegawai aktif
        for ($i = 1; $i <= 2; $i++) {
            $p = Pegawai::create([
                'nama' => "Pegawai $i", 'nip' => "20000101202501100{$i}",
                'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => '2000-01-01',
                'golongan_pangkat' => 'III/a', 'pendidikan' => 'S1',
                'jenjang' => 'Pelaksana', 'jabatan_id' => $jabatan->id,
            ]);
            PenempatanPegawai::create([
                'pegawai_id' => $p->id, 'unor_id' => $unor->id, 'jabatan_id' => $jabatan->id,
                'tanggal_mulai' => '2020-01-01', 'is_active' => true,
            ]);
        }

        // 1 pegawai NON-aktif (seharusnya tidak dihitung)
        $pInactive = Pegawai::create([
            'nama' => 'Pegawai Nonaktif', 'nip' => '200001012025011099',
            'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => '2000-01-01',
            'golongan_pangkat' => 'III/a', 'pendidikan' => 'S1',
            'jenjang' => 'Pelaksana', 'jabatan_id' => $jabatan->id,
        ]);
        PenempatanPegawai::create([
            'pegawai_id' => $pInactive->id, 'unor_id' => $unor->id, 'jabatan_id' => $jabatan->id,
            'tanggal_mulai' => '2018-01-01', 'tanggal_selesai' => '2019-12-31', 'is_active' => false,
        ]);

        $this->service = app(FlattenedTreeService::class);
    }

    #[Test]
    public function bezetting_equals_active_pegawai_count()
    {
        $tree = $this->service->buildFlatTree(includeRoot: false);

        $jabatanRow = collect($tree)->first(fn($r) => $r['type'] === 'jabatan');
        $this->assertNotNull($jabatanRow);
        $this->assertEquals(2, $jabatanRow['bezetting'], 'Bezetting should count only active pegawai (2), not inactive (1)');
    }

    #[Test]
    public function selisih_negatif_means_shortage()
    {
        $tree = $this->service->buildFlatTree(includeRoot: false);

        $jabatanRow = collect($tree)->first(fn($r) => $r['type'] === 'jabatan');
        // Kebutuhan=3, Bezetting=2 → Selisih = -1
        $this->assertEquals(3, $jabatanRow['kebutuhan']);
        $this->assertEquals(2, $jabatanRow['bezetting']);
        $this->assertEquals(-1, $jabatanRow['selisih']);
    }

    #[Test]
    public function selisih_positif_means_surplus()
    {
        // Tambah pegawai ke-3 (aktif) → Bezetting=3, Selisih=0
        $unor = Unor::first();
        $jabatan = Jabatan::first();
        $p3 = Pegawai::create([
            'nama' => 'Pegawai 3', 'nip' => '200001012025011003',
            'jenis_kepegawaian' => 'PPPK', 'tanggal_lahir' => '2000-01-01',
            'golongan_pangkat' => 'III/a', 'pendidikan' => 'S1',
            'jenjang' => 'Pelaksana', 'jabatan_id' => $jabatan->id,
        ]);
        PenempatanPegawai::create([
            'pegawai_id' => $p3->id, 'unor_id' => $unor->id, 'jabatan_id' => $jabatan->id,
            'tanggal_mulai' => '2020-01-01', 'is_active' => true,
        ]);

        $tree = $this->service->buildFlatTree(includeRoot: false);
        $jabatanRow = collect($tree)->first(fn($r) => $r['type'] === 'jabatan');

        $this->assertEquals(3, $jabatanRow['bezetting']);
        $this->assertEquals(0, $jabatanRow['selisih']); // 3 - 3 = 0
    }

    #[Test]
    public function bezetting_zero_when_no_active_penempatan()
    {
        // Nonaktifkan semua penempatan
        PenempatanPegawai::query()->update(['is_active' => false, 'tanggal_selesai' => now()->toDateString()]);

        $tree = $this->service->buildFlatTree(includeRoot: false);
        $jabatanRow = collect($tree)->first(fn($r) => $r['type'] === 'jabatan');

        $this->assertEquals(0, $jabatanRow['bezetting']);
        $this->assertEquals(-3, $jabatanRow['selisih']); // 0 - 3 = -3
    }
}
