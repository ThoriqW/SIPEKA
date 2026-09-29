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

/**
 * Perilaku muat-bertahap pohon.
 *
 * Muatan awal halaman hanya berisi root + anak langsungnya; baris lebih dalam
 * diambil per node lewat buildChildrenRows(). Yang dijaga di sini: irisan
 * barisnya tepat, angkanya tidak bergeser dari buildFlatTree(), dan level
 * (indentasi) baris hasil muat-bertahap sesuai posisinya di pohon halaman.
 */
class FlattenedTreeLazyLoadTest extends TestCase
{
    use RefreshDatabase;

    private FlattenedTreeService $service;

    private Unor $pemkot;
    private Unor $dinas;
    private Unor $smpn;

    protected function setUp(): void
    {
        parent::setUp();

        $t = (int) date('Y');

        // ── Pohon: Pemkot → Dinas Test → SMP Negeri 1 ──
        $this->pemkot = Unor::create(['nama_unor' => 'Pemerintah Kota Palu', 'kode_unor' => 'PEMKOT', 'parent_id' => null]);
        $this->dinas = Unor::create(['nama_unor' => 'Dinas Test', 'kode_unor' => 'DT', 'parent_id' => $this->pemkot->id]);
        $this->smpn = Unor::create(['nama_unor' => 'SMP Negeri 1', 'kode_unor' => 'SMPN1', 'parent_id' => $this->dinas->id]);

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
            'jenis_jabatan' => 'Fungsional', 'kelas_jabatan' => 8, 'jenjang' => 'Ahli Muda',
        ]);

        Sotk::create(['unor_id' => $this->dinas->id, 'jabatan_id' => $kepala->id]);
        Sotk::create(['unor_id' => $this->dinas->id, 'jabatan_id' => $pengelola->id]);
        Sotk::create(['unor_id' => $this->smpn->id, 'jabatan_id' => $guru->id]);

        KebutuhanPegawai::create(['unor_id' => $this->dinas->id, 'jabatan_id' => $kepala->id, 'tahun' => null, 'jumlah' => 1]);
        KebutuhanPegawai::create(['unor_id' => $this->dinas->id, 'jabatan_id' => $pengelola->id, 'tahun' => null, 'jumlah' => 3]);
        KebutuhanPegawai::create(['unor_id' => $this->smpn->id, 'jabatan_id' => $guru->id, 'tahun' => null, 'jumlah' => 4]);

        // Pegawai di Dinas Test (Pengelola Keuangan)
        $p1 = Pegawai::create([
            'nama' => 'Pegawai Dinas', 'nip' => '199001012020011001',
            'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => '1990-01-01',
            'golongan_pangkat' => 'III/a', 'pendidikan' => 'S1',
            'jabatan_id' => $pengelola->id,
        ]);
        PenempatanPegawai::create([
            'pegawai_id' => $p1->id, 'unor_id' => $this->dinas->id, 'jabatan_id' => $pengelola->id,
            'tanggal_mulai' => '2020-01-01', 'is_active' => true,
        ]);

        // Pegawai di SMP Negeri 1 (Guru Matematika) yang pensiun TAHUN INI.
        // Jenjang Ahli Muda → BUP 58, jadi lahir T-58 menghasilkan pensiun di T.
        $p2 = Pegawai::create([
            'nama' => 'Pegawai Pensiun', 'nip' => '199001012020011002',
            'jenis_kepegawaian' => 'PNS', 'tanggal_lahir' => ($t - 58) . '-06-15',
            'golongan_pangkat' => 'III/d', 'pendidikan' => 'S2',
            'jabatan_id' => $guru->id,
        ]);
        PenempatanPegawai::create([
            'pegawai_id' => $p2->id, 'unor_id' => $this->smpn->id, 'jabatan_id' => $guru->id,
            'tanggal_mulai' => '2015-01-01', 'is_active' => true,
        ]);

        $this->service = app(FlattenedTreeService::class);
    }

    // ─────────────────────────── Muatan awal ───────────────────────────

    #[Test]
    public function initial_rows_only_contain_root_and_its_direct_children()
    {
        $rows = $this->service->buildInitialRows();

        // Hanya Pemkot (level 0) dan Dinas Test (level 1). Jabatan milik
        // Dinas Test dan seluruh isi SMP Negeri 1 belum ikut dikirim.
        $this->assertSame(
            ['Pemerintah Kota Palu', 'Dinas Test'],
            array_column($rows, 'nama_jabatan')
        );
    }

    #[Test]
    public function initial_rows_are_numbered_from_one_with_root_expanded()
    {
        $rows = $this->service->buildInitialRows();

        $this->assertSame([1, 2], array_column($rows, 'no'));
        $this->assertTrue($rows[0]['expanded'], 'Root harus dirender dalam keadaan terbuka.');
        $this->assertFalse($rows[1]['expanded']);
    }

    #[Test]
    public function initial_rows_carry_the_same_numbers_as_the_full_tree()
    {
        $index = $this->indexFullTree($this->service->buildFlatTree(withProjections: true));

        // Muatan awal tidak digeser level-nya, jadi level ikut dibandingkan.
        $this->assertRowsMatchFullTree(
            $this->service->buildInitialRows(withProjections: true),
            $index,
            'muatan awal',
            compareLevel: true,
        );
    }

    // ────────────────────── Pemuaian per node ──────────────────────

    #[Test]
    public function children_rows_contain_only_direct_children()
    {
        $rows = $this->service->buildChildrenRows($this->dinas->id);

        // Dua jabatan milik Dinas Test + satu child UNOR. Guru Matematika
        // berada di bawah SMP Negeri 1, jadi belum ikut.
        $this->assertSame(
            ['Kepala Dinas Test', 'Pengelola Keuangan', 'SMP Negeri 1'],
            array_column($rows, 'nama_jabatan')
        );
    }

    #[Test]
    public function children_rows_are_numbered_from_one_per_fragment()
    {
        $rows = $this->service->buildChildrenRows($this->dinas->id);

        $this->assertSame([1, 2, 3], array_column($rows, 'no'));
    }

    #[Test]
    public function children_rows_are_leveled_relative_to_the_page_root()
    {
        // Tanpa filter: Dinas Test ada di level 1, jadi anaknya level 2.
        $this->assertSame(
            [2, 2, 2],
            array_column($this->service->buildChildrenRows($this->dinas->id), 'level')
        );

        // Difilter ke Dinas Test: dia menjadi root halaman, jadi anaknya level 1.
        $this->assertSame(
            [1, 1, 1],
            array_column($this->service->buildChildrenRows($this->dinas->id, false, $this->dinas->id), 'level')
        );

        // Cucu: SMP Negeri 1 ada di level 2, jadi jabatannya level 3.
        $this->assertSame(
            [3],
            array_column($this->service->buildChildrenRows($this->smpn->id), 'level')
        );
    }

    #[Test]
    public function children_rows_are_empty_for_unor_without_children()
    {
        $kosong = Unor::create([
            'nama_unor' => 'Unit Kosong', 'kode_unor' => 'KOSONG',
            'parent_id' => $this->dinas->id,
        ]);

        $this->assertSame([], $this->service->buildChildrenRows($kosong->id));
    }

    #[Test]
    public function children_rows_include_rolled_up_totals_for_child_unor()
    {
        $dinas = collect($this->service->buildChildrenRows($this->pemkot->id))
            ->firstWhere('nama_jabatan', 'Dinas Test');

        // Kebutuhan 1 + 3 + 4 = 8 (termasuk milik SMP Negeri 1)
        $this->assertSame(8, $dinas['kebutuhan']);
        // Bezetting 1 di Dinas Test + 1 di SMP Negeri 1
        $this->assertSame(2, $dinas['bezetting']);
        $this->assertSame(-6, $dinas['selisih']);
        $this->assertTrue($dinas['has_children']);
    }

    #[Test]
    public function children_rows_include_pegawai_for_jabatan_rows()
    {
        $rows = collect($this->service->buildChildrenRows($this->dinas->id));

        $pengelola = $rows->firstWhere('nama_jabatan', 'Pengelola Keuangan');
        $this->assertCount(1, $pengelola['pegawai']);
        $this->assertSame('Pegawai Dinas', $pengelola['pegawai'][0]['nama']);

        // SMP Negeri 1 adalah baris UNOR — daftar pegawai hanya di baris jabatan.
        $this->assertSame([], $rows->firstWhere('nama_jabatan', 'SMP Negeri 1')['pegawai']);
    }

    // ─────────────────────── Proyeksi pensiun ───────────────────────

    #[Test]
    public function expanded_node_includes_projections_from_descendant_unor()
    {
        // Pensiun pegawai SMP Negeri 1 harus terlihat pada baris anak UNOR itu
        // saat Dinas Test dibuka — bukan hanya pada baris jabatan langsungnya.
        $smpn = collect($this->service->buildChildrenRows($this->dinas->id, withProjections: true))
            ->firstWhere('nama_jabatan', 'SMP Negeri 1');

        $this->assertSame(1, $smpn['pensiun_proyeksi'][1]);
        $this->assertSame(1, $smpn['kebutuhan_proyeksi'][1]);
    }

    #[Test]
    public function filtered_tree_includes_projections_from_descendant_unor()
    {
        $tree = $this->service->buildFlatTree(unorId: $this->dinas->id, withProjections: true);

        $dinas = collect($tree)->first(fn ($r) => $r['type'] === 'unor' && $r['level'] === 0);
        $smpn = collect($tree)->firstWhere('nama_jabatan', 'SMP Negeri 1');

        $this->assertSame(1, $smpn['pensiun_proyeksi'][1],
            'Pensiun pegawai di UNOR turunan harus ikut terhitung saat pohon difilter.');
        $this->assertSame(1, $dinas['pensiun_proyeksi'][1],
            'Agregat UNOR induk harus menjumlahkan proyeksi turunannya.');
    }

    // ─────────── Kesetaraan baris hasil muat-bertahap dengan pohon penuh ───────────

    /**
     * Invarian inti muat-bertahap: memuat sebagian baris tidak boleh mengubah
     * satu angka pun. Setiap baris yang disajikan jalur muat-bertahap harus
     * sama persis dengan baris yang sama pada buildFlatTree() penuh.
     */

    #[Test]
    public function children_rows_match_the_full_tree_for_every_unor()
    {
        $index = $this->indexFullTree($this->service->buildFlatTree(withProjections: true));

        foreach (Unor::pluck('id') as $unorId) {
            $this->assertRowsMatchFullTree(
                $this->service->buildChildrenRows($unorId, withProjections: true),
                $index,
                "anak UNOR {$unorId}",
            );
        }
    }

    #[Test]
    public function children_rows_match_the_full_tree_when_a_jabatan_is_shared_between_unor()
    {
        // Satu jabatan yang sama tersedia di dua UNOR — id-nya muncul lebih
        // dari sekali di pohon dengan angka yang berbeda per UNOR.
        $penelaah = Jabatan::create([
            'nama_jabatan' => 'Penelaah Teknis Kebijakan', 'kode_jabatan' => 'UMUM-001',
            'jenis_jabatan' => 'Pelaksana', 'kelas_jabatan' => 6,
        ]);
        Sotk::create(['unor_id' => $this->dinas->id, 'jabatan_id' => $penelaah->id]);
        Sotk::create(['unor_id' => $this->smpn->id, 'jabatan_id' => $penelaah->id]);

        $tree = $this->service->buildFlatTree(withProjections: true);
        $this->assertCount(
            2,
            array_filter($tree, fn (array $row) => $row['id'] === $penelaah->id),
            'Fixture harus benar-benar membuat satu id jabatan muncul di dua UNOR.'
        );

        $index = $this->indexFullTree($tree);

        foreach (Unor::pluck('id') as $unorId) {
            $this->assertRowsMatchFullTree(
                $this->service->buildChildrenRows($unorId, withProjections: true),
                $index,
                "anak UNOR {$unorId} (jabatan berbagi)",
            );
        }
    }

    /**
     * Indeks baris pohon penuh untuk pembanding.
     *
     * Kuncinya gabungan parent_id + id, bukan id saja: satu jabatan bisa
     * tersedia di beberapa UNOR, sehingga id-nya muncul lebih dari sekali
     * dengan angka yang berbeda.
     */
    private function indexFullTree(array $full): array
    {
        $index = [];

        foreach ($full as $row) {
            $index[$this->rowKey($row)] = $row;
        }

        return $index;
    }

    private function rowKey(array $row): string
    {
        return ($row['parent_id'] ?? '') . '|' . $row['id'];
    }

    /**
     * Bandingkan setiap baris terhadap baris padanannya di pohon penuh.
     *
     * `level` hanya dibandingkan bila $compareLevel true — untuk baris anak,
     * buildChildrenRows() memang menggeser level agar sesuai posisi node di
     * pohon halaman, dan itu urusan tampilan, bukan angka.
     *
     * Daftar pegawai dibandingkan sebagai himpunan (diurutkan dulu), karena
     * urutan baris hasil query tidak dijamin sama antara pencarian yang
     * dibatasi per UNOR dan yang tidak.
     */
    private function assertRowsMatchFullTree(
        array $rows,
        array $index,
        string $context,
        bool $compareLevel = false,
    ): void {
        $columns = [
            'nama_jabatan', 'type', 'parent_id', 'has_children',
            'kebutuhan', 'bezetting', 'selisih',
            'pensiun_proyeksi', 'kebutuhan_proyeksi',
        ];

        if ($compareLevel) {
            $columns[] = 'level';
        }

        foreach ($rows as $row) {
            $key = $this->rowKey($row);
            $this->assertArrayHasKey(
                $key,
                $index,
                "Baris '{$row['nama_jabatan']}' tidak ada di pohon penuh ({$context})."
            );

            $expected = $index[$key];

            foreach ($columns as $column) {
                $this->assertSame(
                    $expected[$column],
                    $row[$column],
                    "Kolom '{$column}' baris '{$row['nama_jabatan']}' berbeda dari pohon penuh ({$context})."
                );
            }

            $this->assertSame(
                $this->sortedByNip($expected['pegawai']),
                $this->sortedByNip($row['pegawai']),
                "Daftar pegawai baris '{$row['nama_jabatan']}' berbeda dari pohon penuh ({$context})."
            );

            $this->assertSame(
                $this->sortedByNip($expected['pegawai_pensiun']),
                $this->sortedByNip($row['pegawai_pensiun']),
                "Daftar pegawai pensiun baris '{$row['nama_jabatan']}' berbeda dari pohon penuh ({$context})."
            );
        }
    }

    private function sortedByNip(array $rows): array
    {
        usort($rows, fn (array $a, array $b) => strcmp($a['nip'], $b['nip']));

        return $rows;
    }
}
