<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\KebutuhanPegawai;
use App\Models\ReferensiJabatan;
use App\Models\Sotk;
use App\Models\Unor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Kebutuhan harus ikut pindah saat jabatan dipindahkan ke UNOR lain.
 *
 * Kebutuhan disimpan per (UNOR + jabatan + tahun). Tanpa memindahkan baris
 * lama, perpindahan UNOR akan membuat baris baru sekaligus menyisakan baris
 * lama, sehingga total kebutuhan menghitung jabatan yang sama dua kali.
 */
class JabatanKebutuhanMoveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Unor $opd;
    private Unor $unitA;
    private Unor $unitB;
    private Jabatan $jabatan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('role', 'admin')->firstOrFail();
        $root = Unor::whereNull('parent_id')->firstOrFail();

        $this->opd = Unor::create([
            'nama_unor' => 'Dinas Kebutuhan', 'kode_unor' => 'KEBUTUHAN', 'parent_id' => $root->id,
        ]);
        $this->unitA = Unor::create([
            'nama_unor' => 'Unit Asal', 'kode_unor' => 'KEBUTUHAN-A', 'parent_id' => $this->opd->id,
        ]);
        $this->unitB = Unor::create([
            'nama_unor' => 'Unit Tujuan', 'kode_unor' => 'KEBUTUHAN-B', 'parent_id' => $this->opd->id,
        ]);

        ReferensiJabatan::create([
            'nama_jabatan' => 'Jabatan Uji Kebutuhan',
            'jenis_jabatan' => 'Pelaksana',
        ]);

        $this->jabatan = Jabatan::create([
            'nama_jabatan' => 'Jabatan Uji Kebutuhan',
            'kode_jabatan' => 'UJI-KEBUTUHAN',
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 6,
            'jenjang' => 'Pelaksana',
        ]);

        Sotk::create([
            'unor_id' => $this->unitA->id,
            'jabatan_id' => $this->jabatan->id,
        ]);
    }

    #[Test]
    public function memindahkan_jabatan_ikut_memindahkan_kebutuhan()
    {
        $this->kebutuhanDiUnitA(3);
        $totalSebelum = (int) KebutuhanPegawai::whereNull('tahun')->sum('jumlah');

        $this->pindahKe($this->unitB, 3)->assertSessionHas('success');

        $this->assertSame(1, $this->jumlahBarisKebutuhan());
        $this->assertDatabaseHas('kebutuhan_pegawai', [
            'unor_id' => $this->unitB->id,
            'jabatan_id' => $this->jabatan->id,
            'tahun' => null,
            'jumlah' => 3,
        ]);
        $this->assertDatabaseMissing('kebutuhan_pegawai', [
            'unor_id' => $this->unitA->id,
            'jabatan_id' => $this->jabatan->id,
        ]);
        $this->assertSame($totalSebelum, (int) KebutuhanPegawai::whereNull('tahun')->sum('jumlah'),
            'Total kebutuhan tidak boleh bertambah saat jabatan dipindah.');
    }

    #[Test]
    public function field_kebutuhan_kosong_tetap_membawa_nilai_lama()
    {
        $this->kebutuhanDiUnitA(4);

        $this->pindahKe($this->unitB, null)->assertSessionHas('success');

        $this->assertSame(1, $this->jumlahBarisKebutuhan());
        $this->assertDatabaseHas('kebutuhan_pegawai', [
            'unor_id' => $this->unitB->id,
            'jabatan_id' => $this->jabatan->id,
            'jumlah' => 4,
        ]);
        $this->assertDatabaseMissing('kebutuhan_pegawai', [
            'unor_id' => $this->unitA->id,
            'jabatan_id' => $this->jabatan->id,
        ]);
    }

    #[Test]
    public function memindahkan_jabatan_tanpa_kebutuhan_tidak_membuat_baris()
    {
        $this->pindahKe($this->unitB, null)->assertSessionHas('success');

        $this->assertSame(0, $this->jumlahBarisKebutuhan());
    }

    #[Test]
    public function mengubah_kebutuhan_tanpa_pindah_unor_tetap_di_tempat_yang_sama()
    {
        $this->kebutuhanDiUnitA(3);

        $this->pindahKe($this->unitA, 5)->assertSessionHas('success');

        $this->assertSame(1, $this->jumlahBarisKebutuhan());
        $this->assertDatabaseHas('kebutuhan_pegawai', [
            'unor_id' => $this->unitA->id,
            'jabatan_id' => $this->jabatan->id,
            'jumlah' => 5,
        ]);
    }

    #[Test]
    public function baris_yang_sudah_ada_di_unor_tujuan_menjadi_acuan()
    {
        $this->kebutuhanDiUnitA(3);
        KebutuhanPegawai::create([
            'unor_id' => $this->unitB->id,
            'jabatan_id' => $this->jabatan->id,
            'tahun' => null,
            'jumlah' => 2,
        ]);

        $this->pindahKe($this->unitB, null)->assertSessionHas('success');

        $this->assertSame(1, $this->jumlahBarisKebutuhan());
        $this->assertDatabaseHas('kebutuhan_pegawai', [
            'unor_id' => $this->unitB->id,
            'jabatan_id' => $this->jabatan->id,
            'jumlah' => 2,
        ]);
        $this->assertDatabaseMissing('kebutuhan_pegawai', [
            'unor_id' => $this->unitA->id,
            'jabatan_id' => $this->jabatan->id,
        ]);
    }

    // ────────────────────────────── bantuan ──────────────────────────────

    private function kebutuhanDiUnitA(int $jumlah): void
    {
        KebutuhanPegawai::create([
            'unor_id' => $this->unitA->id,
            'jabatan_id' => $this->jabatan->id,
            'tahun' => null,
            'jumlah' => $jumlah,
        ]);
    }

    private function jumlahBarisKebutuhan(): int
    {
        return KebutuhanPegawai::where('jabatan_id', $this->jabatan->id)
            ->whereNull('tahun')
            ->count();
    }

    private function pindahKe(Unor $unor, ?int $kebutuhan)
    {
        return $this->actingAs($this->admin)->put(
            route('admin.jabatan.update', $this->jabatan),
            [
                'nama_jabatan' => 'Jabatan Uji Kebutuhan',
                'jenis_jabatan' => 'Pelaksana',
                'kelas_jabatan' => 6,
                'jenjang' => 'Pelaksana',
                'kebutuhan' => $kebutuhan,
                'induk_id' => $this->opd->id,
                'unor_id' => $unor->id,
            ],
        );
    }
}
