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

class ProjectionCalculationTest extends TestCase
{
    use RefreshDatabase;

    private FlattenedTreeService $service;
    private int $baseYear;

    protected function setUp(): void
    {
        parent::setUp();

        $t = (int) date('Y');

        $unor = Unor::create(['nama_unor' => 'Dinas Test', 'kode_unor' => 'DT', 'parent_id' => null]);
        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Analis', 'kode_jabatan' => 'DT-001',
            'jenis_jabatan' => 'Fungsional', 'kelas_jabatan' => 8,
            'jenjang' => 'Ahli Muda',
        ]);
        Sotk::create(['unor_id' => $unor->id, 'jabatan_id' => $jabatan->id]);

        KebutuhanPegawai::create([
            'unor_id' => $unor->id, 'jabatan_id' => $jabatan->id,
            'tahun' => null, 'jumlah' => 3,
        ]);

        // Pegawai 1: lahir supaya pensiun TAHUN INI (T+0)
        // Ahli Muda BUP=58 → lahir = T - 58
        $birthYear1 = $t - 58;
        $p1 = Pegawai::create([
            'nama' => 'Pegawai Pensiun Thn Ini', 'nip' => '196001012025011001',
            'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => "{$birthYear1}-06-15",
            'golongan_pangkat' => 'III/d', 'pendidikan' => 'S2',
            'jenjang' => 'Ahli Muda', 'jabatan_id' => $jabatan->id,
        ]);
        PenempatanPegawai::create([
            'pegawai_id' => $p1->id, 'unor_id' => $unor->id, 'jabatan_id' => $jabatan->id,
            'tanggal_mulai' => '2010-01-01', 'is_active' => true,
        ]);

        // Pegawai 2: lahir supaya pensiun T+2 (dua tahun dari sekarang)
        // Ahli Muda BUP=58 → lahir = (T + 2) - 58 = T - 56
        $birthYear2 = $t - 56;
        $p2 = Pegawai::create([
            'nama' => 'Pegawai Pensiun T+2', 'nip' => '196201012025011002',
            'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => "{$birthYear2}-03-20",
            'golongan_pangkat' => 'III/d', 'pendidikan' => 'S2',
            'jenjang' => 'Ahli Muda', 'jabatan_id' => $jabatan->id,
        ]);
        PenempatanPegawai::create([
            'pegawai_id' => $p2->id, 'unor_id' => $unor->id, 'jabatan_id' => $jabatan->id,
            'tanggal_mulai' => '2012-01-01', 'is_active' => true,
        ]);

        // Pegawai 3: MUDA — lahir supaya pensiun JAUH di luar 5 tahun
        // lahir = T - 20 (baru umur 20, BUP 58 → pensiun T+38)
        $p3 = Pegawai::create([
            'nama' => 'Pegawai Muda', 'nip' => '199501012025011003',
            'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => ($t - 20) . '-01-01',
            'golongan_pangkat' => 'III/a', 'pendidikan' => 'S1',
            'jenjang' => 'Ahli Muda', 'jabatan_id' => $jabatan->id,
        ]);
        PenempatanPegawai::create([
            'pegawai_id' => $p3->id, 'unor_id' => $unor->id, 'jabatan_id' => $jabatan->id,
            'tanggal_mulai' => '2020-01-01', 'is_active' => true,
        ]);

        $this->service = app(FlattenedTreeService::class);
    }

    #[Test]
    public function projection_counts_retiring_employees_per_year()
    {
        $tree = $this->service->buildFlatTree(includeRoot: false, withProjections: true);

        $jabatanRow = collect($tree)->first(fn($r) => $r['type'] === 'jabatan');
        $this->assertNotNull($jabatanRow);

        // Pegawai 1 (1966) pensiun tahun ini → proyeksi[1] = 1
        $this->assertEquals(1, $jabatanRow['pensiun_proyeksi'][1]);
        // Pegawai 2 (1968) pensiun T+2 → proyeksi[3] = 1
        $this->assertEquals(1, $jabatanRow['pensiun_proyeksi'][3]);
        // Tahun tanpa pensiun = 0
        $this->assertEquals(0, $jabatanRow['pensiun_proyeksi'][2]);
        $this->assertEquals(0, $jabatanRow['pensiun_proyeksi'][4]);
        $this->assertEquals(0, $jabatanRow['pensiun_proyeksi'][5]);
    }

    #[Test]
    public function projection_is_one_to_one_replacement()
    {
        $tree = $this->service->buildFlatTree(includeRoot: false, withProjections: true);

        $jabatanRow = collect($tree)->first(fn($r) => $r['type'] === 'jabatan');

        // Kebutuhan proyeksi = pensiun proyeksi (1:1 replacement)
        $this->assertEquals(
            $jabatanRow['pensiun_proyeksi'][1],
            $jabatanRow['kebutuhan_proyeksi'][1]
        );
    }

    #[Test]
    public function projection_is_not_cumulative()
    {
        $tree = $this->service->buildFlatTree(includeRoot: false, withProjections: true);

        $jabatanRow = collect($tree)->first(fn($r) => $r['type'] === 'jabatan');

        // Tahun 3 hanya 1 (pegawai 1968), BUKAN 2 (akumulasi tahun 1 + 3)
        $this->assertEquals(1, $jabatanRow['pensiun_proyeksi'][3],
            'Year 3 should be 1 (only the 1968 retiree), NOT 2 (cumulative)');
    }

    #[Test]
    public function projection_is_not_remaining_pegawai_count()
    {
        $tree = $this->service->buildFlatTree(includeRoot: false, withProjections: true);

        $jabatanRow = collect($tree)->first(fn($r) => $r['type'] === 'jabatan');

        // Bezetting = 3 pegawai aktif. Proyeksi tahun 1 = 1 (pensiun), BUKAN 2 (pegawai tersisa)
        $this->assertEquals(3, $jabatanRow['bezetting'], 'Bezetting = 3 active employees');
        $this->assertEquals(1, $jabatanRow['pensiun_proyeksi'][1],
            'Year 1 = 1 retirement, NOT remaining employees (which would be 2)');
    }

    #[Test]
    public function young_employees_not_in_projection()
    {
        $tree = $this->service->buildFlatTree(includeRoot: false, withProjections: true);

        $jabatanRow = collect($tree)->first(fn($r) => $r['type'] === 'jabatan');

        // Pegawai muda (1995) tidak muncul di proyeksi
        $totalPensiun5Tahun = array_sum($jabatanRow['pensiun_proyeksi']);
        $this->assertEquals(2, $totalPensiun5Tahun,
            'Only 2 retirees in 5 years (1966 + 1968). Young employee (1995) not counted.');
    }
}
