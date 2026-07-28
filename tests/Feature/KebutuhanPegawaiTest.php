<?php

namespace Tests\Feature;

use App\Models\KebutuhanPegawai;
use App\Models\User;
use App\Models\Unor;
use App\Models\Jabatan;
use App\Models\Sotk;
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
    public function bkd_can_update_kebutuhan()
    {
        $user = User::where('role', 'bkd')->first();
        $unor = Unor::where('kode_unor', 'DIKBUD')->first();
        $jabatan = Jabatan::where('kode_jabatan', 'DIKBUD-005')->first(); // Pengelola Keuangan

        $response = $this->actingAs($user)->put(route('admin.opd.update-kebutuhan', $unor), [
            'jabatan_id' => $jabatan->id,
            'jumlah' => 5,
        ]);

        $response->assertRedirect();

        $kebutuhan = KebutuhanPegawai::where('unor_id', $unor->id)
            ->where('jabatan_id', $jabatan->id)
            ->whereNull('tahun')
            ->first();

        $this->assertNotNull($kebutuhan);
        $this->assertEquals(5, $kebutuhan->jumlah);
    }

    #[Test]
    public function kebutuhan_zero_is_valid()
    {
        $user = User::where('role', 'bkd')->first();
        $unor = Unor::where('kode_unor', 'DIKBUD')->first();
        $jabatan = Jabatan::where('kode_jabatan', 'DIKBUD-005')->first();

        $response = $this->actingAs($user)->put(route('admin.opd.update-kebutuhan', $unor), [
            'jabatan_id' => $jabatan->id,
            'jumlah' => 0,
        ]);

        $response->assertRedirect();

        $kebutuhan = KebutuhanPegawai::where('unor_id', $unor->id)
            ->where('jabatan_id', $jabatan->id)
            ->whereNull('tahun')
            ->first();

        $this->assertNotNull($kebutuhan);
        $this->assertEquals(0, $kebutuhan->jumlah);
    }

    #[Test]
    public function kebutuhan_negative_is_rejected()
    {
        $user = User::where('role', 'bkd')->first();
        $unor = Unor::where('kode_unor', 'DIKBUD')->first();
        $jabatan = Jabatan::where('kode_jabatan', 'DIKBUD-005')->first();

        $response = $this->actingAs($user)->put(route('admin.opd.update-kebutuhan', $unor), [
            'jabatan_id' => $jabatan->id,
            'jumlah' => -1,
        ]);

        $response->assertSessionHasErrors('jumlah');
    }

    #[Test]
    public function kebutuhan_uses_update_or_create_not_duplicate()
    {
        $user = User::where('role', 'bkd')->first();
        $unor = Unor::where('kode_unor', 'DIKBUD')->first();
        $jabatan = Jabatan::where('kode_jabatan', 'DIKBUD-005')->first();

        // Update dua kali — tidak boleh duplicate
        $this->actingAs($user)->put(route('admin.opd.update-kebutuhan', $unor), [
            'jabatan_id' => $jabatan->id, 'jumlah' => 3,
        ]);
        $this->actingAs($user)->put(route('admin.opd.update-kebutuhan', $unor), [
            'jabatan_id' => $jabatan->id, 'jumlah' => 7,
        ]);

        $count = KebutuhanPegawai::where('unor_id', $unor->id)
            ->where('jabatan_id', $jabatan->id)
            ->whereNull('tahun')
            ->count();

        $this->assertEquals(1, $count, 'Should be exactly 1 record, not duplicated');

        $kebutuhan = KebutuhanPegawai::where('unor_id', $unor->id)
            ->where('jabatan_id', $jabatan->id)
            ->whereNull('tahun')
            ->first();

        $this->assertEquals(7, $kebutuhan->jumlah, 'Should update to latest value');
    }
}
