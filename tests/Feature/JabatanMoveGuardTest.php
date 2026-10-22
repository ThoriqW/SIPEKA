<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\ReferensiJabatan;
use App\Models\Sotk;
use App\Models\Unor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Perpindahan jabatan ke UNOR lain hanya boleh dilakukan saat jabatan kosong.
 *
 * Penempatan pegawai tidak ikut pindah saat SOTK dipindahkan. Jika perpindahan
 * dibiarkan, SOTK dan penempatan saling bertentangan: pegawai tetap tercatat
 * di unit lama, sedangkan jabatannya berada di unit baru — Bezetting dan
 * proyeksi pegawai itu tidak lagi tampil di pohon.
 */
class JabatanMoveGuardTest extends TestCase
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
            'nama_unor' => 'Dinas Pindah', 'kode_unor' => 'PINDAH', 'parent_id' => $root->id,
        ]);
        $this->unitA = Unor::create([
            'nama_unor' => 'Unit Asal', 'kode_unor' => 'PINDAH-A', 'parent_id' => $this->opd->id,
        ]);
        $this->unitB = Unor::create([
            'nama_unor' => 'Unit Tujuan', 'kode_unor' => 'PINDAH-B', 'parent_id' => $this->opd->id,
        ]);

        ReferensiJabatan::create([
            'nama_jabatan' => 'Jabatan Uji Pindah',
            'jenis_jabatan' => 'Pelaksana',
        ]);

        $this->jabatan = Jabatan::create([
            'nama_jabatan' => 'Jabatan Uji Pindah',
            'kode_jabatan' => 'UJI-PINDAH',
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
    public function memindahkan_jabatan_yang_masih_dipegang_pegawai_ditolak()
    {
        $this->pegawaiDiUnitA();

        $response = $this->actingAs($this->admin)->put(
            route('admin.jabatan.update', $this->jabatan),
            $this->payload($this->unitB, 7),
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // SOTK tidak boleh berpindah.
        $this->assertDatabaseHas('sotk', [
            'unor_id' => $this->unitA->id,
            'jabatan_id' => $this->jabatan->id,
        ]);
        $this->assertDatabaseMissing('sotk', [
            'unor_id' => $this->unitB->id,
            'jabatan_id' => $this->jabatan->id,
        ]);

        // Tidak ada perubahan jabatan yang tersimpan.
        $this->assertSame(6, $this->jabatan->fresh()->kelas_jabatan,
            'Perubahan lain tidak boleh ikut tersimpan saat perpindahan ditolak.');
    }

    #[Test]
    public function memindahkan_jabatan_kosong_berhasil()
    {
        $response = $this->actingAs($this->admin)->put(
            route('admin.jabatan.update', $this->jabatan),
            $this->payload($this->unitB, 6),
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('sotk', [
            'unor_id' => $this->unitA->id,
            'jabatan_id' => $this->jabatan->id,
        ]);
        $this->assertDatabaseHas('sotk', [
            'unor_id' => $this->unitB->id,
            'jabatan_id' => $this->jabatan->id,
        ]);
    }

    #[Test]
    public function mengubah_jabatan_yang_dipegang_tanpa_memindah_unor_tetap_boleh()
    {
        $this->pegawaiDiUnitA();

        $response = $this->actingAs($this->admin)->put(
            route('admin.jabatan.update', $this->jabatan),
            $this->payload($this->unitA, 8),
        );

        $response->assertSessionHas('success');
        $this->assertSame(8, $this->jabatan->fresh()->kelas_jabatan);
    }

    // ────────────────────────────── bantuan ──────────────────────────────

    private function pegawaiDiUnitA(): Pegawai
    {
        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Uji Pindah',
            'nip' => '200001012025011301',
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1990-01-01',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'D4/S1',
            'jabatan_id' => $this->jabatan->id,
        ]);

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unor_id' => $this->unitA->id,
            'jabatan_id' => $this->jabatan->id,
            'tanggal_mulai' => '2020-01-01',
            'is_active' => true,
        ]);

        return $pegawai;
    }

    private function payload(Unor $unor, int $kelas): array
    {
        return [
            'nama_jabatan' => 'Jabatan Uji Pindah',
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => $kelas,
            'jenjang' => 'Pelaksana',
            'kebutuhan' => null,
            'induk_id' => $this->opd->id,
            'unor_id' => $unor->id,
        ];
    }
}
