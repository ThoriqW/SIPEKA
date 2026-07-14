<?php

namespace Tests\Unit;

use App\Models\Jabatan;
use App\Models\Opd;
use App\Services\KodeJabatanGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KodeJabatanGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private KodeJabatanGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        Opd::create(['nama_opd' => 'Dinas Test', 'kode_opd' => 'DIKBUD']);
        Opd::create(['nama_opd' => 'Dinas Kesehatan', 'kode_opd' => 'DINKES']);

        $this->generator = app(KodeJabatanGenerator::class);
    }

    #[Test]
    public function it_generates_first_code_for_opd_and_jenis()
    {
        $kode = $this->generator->generate('DIKBUD', 'Struktural');

        $this->assertEquals('DIKBUD-STR-001', $kode);
    }

    #[Test]
    public function it_increments_sequence_for_same_opd_and_jenis()
    {
        Jabatan::create([
            'nama_jabatan' => 'Kepala',
            'kode_jabatan' => 'DIKBUD-STR-001',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 15,
            'jenjang' => 'Pimpinan Tinggi Pratama',
            'kebutuhan' => 1,
            'opd_id' => 1,
        ]);

        $kode = $this->generator->generate('DIKBUD', 'Struktural');

        $this->assertEquals('DIKBUD-STR-002', $kode);
    }

    #[Test]
    public function it_resets_sequence_for_different_jenis()
    {
        Jabatan::create([
            'nama_jabatan' => 'Kepala',
            'kode_jabatan' => 'DIKBUD-STR-001',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 15,
            'jenjang' => 'Pimpinan Tinggi Pratama',
            'kebutuhan' => 1,
            'opd_id' => 1,
        ]);

        $kode = $this->generator->generate('DIKBUD', 'Fungsional');

        $this->assertEquals('DIKBUD-FNG-001', $kode);
    }

    #[Test]
    public function it_resets_sequence_for_different_opd()
    {
        Jabatan::create([
            'nama_jabatan' => 'Kepala Dikbud',
            'kode_jabatan' => 'DIKBUD-STR-001',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 15,
            'jenjang' => 'Pimpinan Tinggi Pratama',
            'kebutuhan' => 1,
            'opd_id' => 1,
        ]);

        $kode = $this->generator->generate('DINKES', 'Struktural');

        $this->assertEquals('DINKES-STR-001', $kode);
    }

    #[Test]
    public function it_produces_plk_for_pelaksana()
    {
        $kode = $this->generator->generate('DIKBUD', 'Pelaksana');

        $this->assertEquals('DIKBUD-PLK-001', $kode);
    }

    #[Test]
    public function it_does_not_skip_number_for_existing_non_matching_format()
    {
        // Existing data with old format — should NOT affect new sequence
        Jabatan::create([
            'nama_jabatan' => 'Old Format',
            'kode_jabatan' => 'DIKBUD-001',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 10,
            'jenjang' => 'Pengawas',
            'kebutuhan' => 1,
            'opd_id' => 1,
        ]);

        $kode = $this->generator->generate('DIKBUD', 'Struktural');

        // DIKBUD-001 is old format (no jenis segment), should NOT be counted
        $this->assertEquals('DIKBUD-STR-001', $kode);
    }

    #[Test]
    public function it_handles_high_sequence_numbers()
    {
        Jabatan::create([
            'nama_jabatan' => 'Max',
            'kode_jabatan' => 'DIKBUD-STR-999',
            'jenis_jabatan' => 'Struktural',
            'kelas_jabatan' => 10,
            'jenjang' => 'Pengawas',
            'kebutuhan' => 1,
            'opd_id' => 1,
        ]);

        $kode = $this->generator->generate('DIKBUD', 'Struktural');

        $this->assertEquals('DIKBUD-STR-1000', $kode);
    }
}
