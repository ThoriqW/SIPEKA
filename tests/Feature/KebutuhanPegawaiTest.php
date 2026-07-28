<?php

namespace Tests\Feature;

use App\Models\KebutuhanPegawai;
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
        $user = User::where('role', 'bkd')->first();
        $unor = Unor::where('kode_unor', 'DIKBUD')->first();

        $response = $this->actingAs($user)->post(route('admin.jabatan.store'), [
            'nama_jabatan' => 'Analis Kebijakan',
            'jenis_jabatan' => 'Fungsional',
            'kelas_jabatan' => 8,
            'jenjang' => 'Ahli Muda',
            'kebutuhan' => 5,
            'opd_id' => $unor->id,
        ]);

        $response->assertRedirect();

        $jabatan = Jabatan::where('nama_jabatan', 'Analis Kebijakan')->first();
        $this->assertNotNull($jabatan);

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
        $user = User::where('role', 'bkd')->first();
        $jabatan = Jabatan::where('kode_jabatan', 'DIKBUD-005')->first(); // Pengelola Keuangan

        $this->actingAs($user)->put(route('admin.jabatan.update', $jabatan), [
            'nama_jabatan' => $jabatan->nama_jabatan,
            'jenis_jabatan' => $jabatan->jenis_jabatan,
            'kelas_jabatan' => $jabatan->kelas_jabatan,
            'jenjang' => $jabatan->jenjang,
            'kebutuhan' => 10,
            'opd_id' => $jabatan->opd_id,
        ]);

        $kebutuhan = KebutuhanPegawai::where('unor_id', $jabatan->opd_id)
            ->where('jabatan_id', $jabatan->id)
            ->whereNull('tahun')
            ->first();

        $this->assertNotNull($kebutuhan);
        $this->assertEquals(10, $kebutuhan->jumlah);
    }

    #[Test]
    public function kebutuhan_zero_is_valid()
    {
        $user = User::where('role', 'bkd')->first();
        $jabatan = Jabatan::where('kode_jabatan', 'DIKBUD-005')->first();

        $this->actingAs($user)->put(route('admin.jabatan.update', $jabatan), [
            'nama_jabatan' => $jabatan->nama_jabatan,
            'jenis_jabatan' => $jabatan->jenis_jabatan,
            'kelas_jabatan' => $jabatan->kelas_jabatan,
            'jenjang' => $jabatan->jenjang,
            'kebutuhan' => 0,
            'opd_id' => $jabatan->opd_id,
        ]);

        $kebutuhan = KebutuhanPegawai::where('unor_id', $jabatan->opd_id)
            ->where('jabatan_id', $jabatan->id)
            ->whereNull('tahun')
            ->first();

        $this->assertNotNull($kebutuhan);
        $this->assertEquals(0, $kebutuhan->jumlah);
    }

    #[Test]
    public function kebutuhan_not_duplicated_on_multiple_updates()
    {
        $user = User::where('role', 'bkd')->first();
        $jabatan = Jabatan::where('kode_jabatan', 'DIKBUD-005')->first();

        $this->actingAs($user)->put(route('admin.jabatan.update', $jabatan), [
            'nama_jabatan' => $jabatan->nama_jabatan,
            'jenis_jabatan' => $jabatan->jenis_jabatan,
            'kelas_jabatan' => $jabatan->kelas_jabatan,
            'jenjang' => $jabatan->jenjang,
            'kebutuhan' => 3,
            'opd_id' => $jabatan->opd_id,
        ]);
        $this->actingAs($user)->put(route('admin.jabatan.update', $jabatan), [
            'nama_jabatan' => $jabatan->nama_jabatan,
            'jenis_jabatan' => $jabatan->jenis_jabatan,
            'kelas_jabatan' => $jabatan->kelas_jabatan,
            'jenjang' => $jabatan->jenjang,
            'kebutuhan' => 7,
            'opd_id' => $jabatan->opd_id,
        ]);

        $count = KebutuhanPegawai::where('unor_id', $jabatan->opd_id)
            ->where('jabatan_id', $jabatan->id)
            ->whereNull('tahun')
            ->count();

        $this->assertEquals(1, $count, 'Should be exactly 1 record');
    }
}
