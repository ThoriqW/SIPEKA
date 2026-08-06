<?php

namespace Tests\Feature;

use App\Models\KebutuhanPegawai;
use App\Models\Sotk;
use App\Models\User;
use App\Models\Unor;
use App\Models\Jabatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KebutuhanPegawaiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function kebutuhan_saved_when_creating_jabatan()
    {
        $user = User::where('role', 'admin')->first();
        $unor = Unor::where('kode_unor', 'DIKBUD')->first();

        $response = $this->actingAs($user)->post(route('admin.jabatan.store'), [
            'nama_jabatan' => 'Statistisi',
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang' => 'Ahli Muda',
            'kebutuhan' => 5,
            'induk_id' => $unor->id,
            'unor_id' => $unor->id,
        ]);

        $response->assertRedirect(route('admin.jabatan.index'));

        $jabatan = Jabatan::where('nama_jabatan', 'Statistisi')
            ->whereHas('sotkEntries', fn($q) => $q->where('unor_id', $unor->id))
            ->first();
        $this->assertNotNull($jabatan, 'Jabatan should be created after valid POST');
        $unorId = $jabatan->sotkEntries()->first()?->unor_id;

        $kebutuhan = KebutuhanPegawai::where('unor_id', $unor->id)
            ->where('jabatan_id', $jabatan->id)
            ->whereNull('tahun')
            ->first();

        $this->assertNotNull($kebutuhan);
        $this->assertEquals(5, $kebutuhan->jumlah);
    }

    #[Test]
    public function kebutuhan_updated_when_updating_jabatan()
    {
        $user = User::where('role', 'admin')->first();
        $jabatan = Jabatan::where('nama_jabatan', 'Pengelola Keuangan')->first();
        $unorId = $jabatan->sotkEntries()->first()?->unor_id;
        $indukId = Unor::where('kode_unor', 'BKPSDM')->first()->id;

        $this->actingAs($user)->put(route('admin.jabatan.update', $jabatan), [
            'nama_jabatan' => $jabatan->nama_jabatan,
            'jenis_jabatan' => $jabatan->jenis_jabatan,
            'kelas_jabatan' => $jabatan->kelas_jabatan,
            'jenjang' => $jabatan->jenjang,
            'induk_id' => $indukId,
            'kebutuhan' => 10,
            'unor_id' => $unorId,
        ]);

        $kebutuhan = KebutuhanPegawai::where('unor_id', $unorId)
            ->where('jabatan_id', $jabatan->id)
            ->whereNull('tahun')
            ->first();

        $this->assertNotNull($kebutuhan);
        $this->assertEquals(10, $kebutuhan->jumlah);
    }

    #[Test]
    public function kebutuhan_zero_is_valid()
    {
        $user = User::where('role', 'admin')->first();
        $jabatan = Jabatan::where('nama_jabatan', 'Pengelola Keuangan')->first();
        $unorId = $jabatan->sotkEntries()->first()?->unor_id;
        $indukId = Unor::where('kode_unor', 'BKPSDM')->first()->id;

        $this->actingAs($user)->put(route('admin.jabatan.update', $jabatan), [
            'nama_jabatan' => $jabatan->nama_jabatan,
            'jenis_jabatan' => $jabatan->jenis_jabatan,
            'kelas_jabatan' => $jabatan->kelas_jabatan,
            'jenjang' => $jabatan->jenjang,
            'induk_id' => $indukId,
            'kebutuhan' => 0,
            'unor_id' => $unorId,
        ]);

        $kebutuhan = KebutuhanPegawai::where('unor_id', $unorId)
            ->where('jabatan_id', $jabatan->id)
            ->whereNull('tahun')
            ->first();

        $this->assertNotNull($kebutuhan);
        $this->assertEquals(0, $kebutuhan->jumlah);
    }

    #[Test]
    public function kebutuhan_not_duplicated_on_multiple_updates()
    {
        $user = User::where('role', 'admin')->first();
        $jabatan = Jabatan::where('nama_jabatan', 'Pengelola Keuangan')->first();
        $unorId = $jabatan->sotkEntries()->first()?->unor_id;
        $indukId = Unor::where('kode_unor', 'BKPSDM')->first()->id;

        $this->actingAs($user)->put(route('admin.jabatan.update', $jabatan), [
            'nama_jabatan' => $jabatan->nama_jabatan,
            'jenis_jabatan' => $jabatan->jenis_jabatan,
            'kelas_jabatan' => $jabatan->kelas_jabatan,
            'jenjang' => $jabatan->jenjang,
            'induk_id' => $indukId,
            'kebutuhan' => 3,
            'unor_id' => $unorId,
        ]);
        $this->actingAs($user)->put(route('admin.jabatan.update', $jabatan), [
            'nama_jabatan' => $jabatan->nama_jabatan,
            'jenis_jabatan' => $jabatan->jenis_jabatan,
            'kelas_jabatan' => $jabatan->kelas_jabatan,
            'jenjang' => $jabatan->jenjang,
            'kebutuhan' => 7,
            'unor_id' => $unorId,
        ]);

        $count = KebutuhanPegawai::where('unor_id', $unorId)
            ->where('jabatan_id', $jabatan->id)
            ->whereNull('tahun')
            ->count();

        $this->assertEquals(1, $count, 'Should be exactly 1 record');
    }
}
