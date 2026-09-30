<?php

namespace Tests\Unit;

use App\Models\Unor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Label jalur UNOR.
 *
 * Ini yang membuat UNOR bernama sama di cabang berbeda bisa dibedakan —
 * mis. "Sekretariat" milik kecamatan dan milik kelurahan.
 */
class UnorPathLabelTest extends TestCase
{
    use RefreshDatabase;

    private Unor $pemkot;
    private Unor $kecamatan;
    private Unor $kelurahan;
    private Unor $sekretariatKelurahan;
    private Unor $sekretariatKecamatan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pemkot = Unor::create(['nama_unor' => 'Pemerintah Kota Palu', 'kode_unor' => 'PEMKOT', 'parent_id' => null]);
        $this->kecamatan = Unor::create(['nama_unor' => 'Kecamatan', 'kode_unor' => 'KEC', 'parent_id' => $this->pemkot->id]);
        $this->kelurahan = Unor::create(['nama_unor' => 'Kelurahan', 'kode_unor' => 'KEL', 'parent_id' => $this->kecamatan->id]);
        $this->sekretariatKelurahan = Unor::create(['nama_unor' => 'Sekretariat', 'kode_unor' => 'SEK-KEL', 'parent_id' => $this->kelurahan->id]);
        $this->sekretariatKecamatan = Unor::create(['nama_unor' => 'Sekretariat', 'kode_unor' => 'SEK-KEC', 'parent_id' => $this->kecamatan->id]);
    }

    private function allUnor()
    {
        return Unor::with('parent')->get()->keyBy('id');
    }

    #[Test]
    public function absolute_path_lists_every_ancestor_below_the_root()
    {
        $all = $this->allUnor();

        $this->assertSame('Kecamatan » Kelurahan » Sekretariat', $this->sekretariatKelurahan->pathLabel($all));
        $this->assertSame('Kecamatan » Sekretariat', $this->sekretariatKecamatan->pathLabel($all));
    }

    #[Test]
    public function absolute_path_excludes_the_root()
    {
        $all = $this->allUnor();

        $this->assertSame('Kecamatan', $this->kecamatan->pathLabel($all));
        // Root itu sendiri tetap tampil sebagai namanya saja.
        $this->assertSame('Pemerintah Kota Palu', $this->pemkot->pathLabel($all));
    }

    #[Test]
    public function relative_path_stops_above_the_given_unor()
    {
        $all = $this->allUnor();

        // Induk yang sedang dipilih tidak diulang di label.
        $this->assertSame('Kelurahan » Sekretariat', $this->sekretariatKelurahan->pathLabel($all, $this->kecamatan->id));
        $this->assertSame('Sekretariat', $this->sekretariatKecamatan->pathLabel($all, $this->kecamatan->id));
        $this->assertSame('Kelurahan', $this->kelurahan->pathLabel($all, $this->kecamatan->id));
    }

    #[Test]
    public function relative_path_of_the_unor_itself_is_just_its_name()
    {
        $all = $this->allUnor();

        $this->assertSame('Kecamatan', $this->kecamatan->pathLabel($all, $this->kecamatan->id));
    }

    #[Test]
    public function homonymous_unor_get_distinct_relative_labels()
    {
        $all = $this->allUnor();

        // Inilah inti perbaikannya: dua "Sekretariat" tidak lagi berlabel sama.
        $this->assertNotSame(
            $this->sekretariatKecamatan->pathLabel($all, $this->kecamatan->id),
            $this->sekretariatKelurahan->pathLabel($all, $this->kecamatan->id),
        );
    }
}
