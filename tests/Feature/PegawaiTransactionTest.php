<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\MasterTugasTambahan;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Sotk;
use App\Models\TugasTambahanPegawai;
use App\Models\Unor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Operasi tulis multi-langkah pada pegawai harus atomik.
 *
 * Setiap test memaksa langkah terakhir gagal, lalu memastikan langkah
 * sebelumnya ikut dibatalkan. Tanpa DB::transaction, data tertinggal separuh
 * jalan: pegawai tanpa penempatan, atau jabatan berubah tetapi penempatan
 * lama sudah dinonaktifkan.
 */
class PegawaiTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Unor $unitA;
    private Unor $unitB;
    private Jabatan $jabatanA;
    private Jabatan $jabatanB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('role', 'admin')->firstOrFail();
        $root = Unor::whereNull('parent_id')->firstOrFail();

        $opd = Unor::create([
            'nama_unor' => 'Dinas Transaksi', 'kode_unor' => 'TRA', 'parent_id' => $root->id,
        ]);
        $this->unitA = Unor::create([
            'nama_unor' => 'Unit A', 'kode_unor' => 'TRA-A', 'parent_id' => $opd->id,
        ]);
        $this->unitB = Unor::create([
            'nama_unor' => 'Unit B', 'kode_unor' => 'TRA-B', 'parent_id' => $opd->id,
        ]);

        $this->jabatanA = $this->buatJabatan('Jabatan A', 'TRA-JA', $this->unitA);
        $this->jabatanB = $this->buatJabatan('Jabatan B', 'TRA-JB', $this->unitB);
    }

    #[Test]
    public function store_membatalkan_pegawai_jika_penempatan_gagal_dibuat()
    {
        $nip = '200001012025011201';

        PenempatanPegawai::creating(function () {
            throw new RuntimeException('gagal membuat penempatan');
        });

        try {
            $this->withoutExceptionHandling()
                ->actingAs($this->admin)
                ->post(route('admin.pegawai.store'), $this->payloadPegawai($this->jabatanA, $nip));
            $this->fail('Penyimpanan seharusnya melempar exception.');
        } catch (RuntimeException $e) {
            $this->assertSame('gagal membuat penempatan', $e->getMessage());
        } finally {
            PenempatanPegawai::flushEventListeners();
        }

        // Pegawai harus dibatalkan karena penempatannya gagal dibuat.
        $this->assertDatabaseMissing('pegawai', ['nip' => $nip]);
    }

    #[Test]
    public function update_membatalkan_perubahan_jika_penempatan_baru_gagal_dibuat()
    {
        $pegawai = $this->pegawaiDenganPenempatan($this->jabatanA, '200001012025011202');

        PenempatanPegawai::creating(function () {
            throw new RuntimeException('gagal membuat penempatan baru');
        });

        try {
            $this->withoutExceptionHandling()
                ->actingAs($this->admin)
                ->put(route('admin.pegawai.update', $pegawai), $this->payloadPegawai($this->jabatanB, $pegawai->nip));
            $this->fail('Pembaruan seharusnya melempar exception.');
        } catch (RuntimeException $e) {
            $this->assertSame('gagal membuat penempatan baru', $e->getMessage());
        } finally {
            PenempatanPegawai::flushEventListeners();
        }

        $this->assertSame($this->jabatanA->id, $pegawai->fresh()->jabatan_id,
            'Perubahan jabatan harus dibatalkan.');
        $this->assertSame($this->unitA->id, $pegawai->fresh()->penempatanAktif?->unor_id,
            'Penempatan lama harus tetap aktif setelah rollback.');
    }

    #[Test]
    public function store_tugas_tambahan_membatalkan_penonaktifan_jika_pembuatan_gagal()
    {
        $pegawai = $this->pegawaiDenganPenempatan($this->jabatanA, '200001012025011203');
        $tugas = MasterTugasTambahan::create(['nama_tugas' => 'Kepala Sekolah Uji']);

        $lama = TugasTambahanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'tugas_tambahan_id' => $tugas->id,
            'unor_id' => $this->unitA->id,
            'tanggal_mulai' => '2020-01-01',
            'is_active' => true,
        ]);

        TugasTambahanPegawai::creating(function () {
            throw new RuntimeException('gagal membuat tugas baru');
        });

        try {
            $this->withoutExceptionHandling()
                ->actingAs($this->admin)
                ->post(route('admin.pegawai.tugas-tambahan.store', $pegawai), [
                    'tugas_tambahan_id' => $tugas->id,
                    'unor_id' => $this->unitA->id,
                    'tanggal_mulai' => now()->toDateString(),
                ]);
            $this->fail('Penyimpanan seharusnya melempar exception.');
        } catch (RuntimeException $e) {
            $this->assertSame('gagal membuat tugas baru', $e->getMessage());
        } finally {
            TugasTambahanPegawai::flushEventListeners();
        }

        $this->assertTrue($lama->fresh()->is_active,
            'Tugas lama harus tetap aktif setelah rollback.');
        $this->assertSame(1, TugasTambahanPegawai::where('pegawai_id', $pegawai->id)->count(),
            'Tidak boleh ada baris tugas baru yang tertinggal.');
    }

    // ────────────────────────────── bantuan ──────────────────────────────

    private function buatJabatan(string $nama, string $kode, Unor $unor): Jabatan
    {
        $jabatan = Jabatan::create([
            'nama_jabatan' => $nama,
            'kode_jabatan' => $kode,
            'jenis_jabatan' => 'Pelaksana',
            'kelas_jabatan' => 6,
            'jenjang' => 'Pelaksana',
        ]);

        Sotk::create(['unor_id' => $unor->id, 'jabatan_id' => $jabatan->id]);

        return $jabatan;
    }

    private function pegawaiDenganPenempatan(Jabatan $jabatan, string $nip): Pegawai
    {
        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Transaksi',
            'nip' => $nip,
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1990-01-01',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'D4/S1',
            'jabatan_id' => $jabatan->id,
        ]);

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unor_id' => $jabatan->sotkEntries()->first()->unor_id,
            'jabatan_id' => $jabatan->id,
            'tanggal_mulai' => '2020-01-01',
            'is_active' => true,
        ]);

        return $pegawai;
    }

    private function payloadPegawai(Jabatan $jabatan, string $nip): array
    {
        return [
            'nip' => $nip,
            'nama' => 'Pegawai Transaksi',
            'gelar_depan' => null,
            'gelar_belakang' => null,
            'jenis_kepegawaian' => 'PNS',
            'tanggal_lahir' => '1990-01-01',
            'golongan_pangkat' => 'III/a',
            'pendidikan' => 'D4/S1',
            'kualifikasi_pendidikan' => null,
            'jabatan_id' => $jabatan->id,
        ];
    }
}
