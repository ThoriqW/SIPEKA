<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Lebar box notifikasi flash harus mengikuti lebar kontainer halaman.
 *
 * Halaman tabel memakai max-w-full; form dibatasi max-w-7xl. Layout tidak
 * boleh mematok satu lebar untuk semua halaman, karena di layar lebar
 * (>1536px) notifikasi akan lebih sempit daripada tabel di bawahnya.
 */
class FlashContainerWidthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function notifikasi_di_halaman_tabel_mengikuti_lebar_halaman()
    {
        $halaman = [
            'pegawai.index' => route('admin.pegawai.index'),
            'jabatan.index' => route('admin.jabatan.index'),
            'bezetting.index' => route('admin.bezetting.index'),
            'kebutuhan.index' => route('admin.kebutuhan.index'),
        ];

        foreach ($halaman as $nama => $url) {
            $html = $this->withSession(['success' => 'Berhasil.'])
                ->actingAs($this->admin())
                ->get($url)
                ->assertStatus(200)
                ->getContent();

            $this->assertStringContainsString(
                'class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 mt-4"',
                $html,
                "Notifikasi di {$nama} tidak mengikuti lebar tabel halaman.",
            );
        }
    }

    #[Test]
    public function notifikasi_di_halaman_form_tetap_dibatasi()
    {
        $halaman = [
            'pegawai.create' => route('admin.pegawai.create'),
            'jabatan.create' => route('admin.jabatan.create'),
        ];

        foreach ($halaman as $nama => $url) {
            $html = $this->withSession(['error' => 'Gagal.'])
                ->actingAs($this->admin())
                ->get($url)
                ->assertStatus(200)
                ->getContent();

            $this->assertStringContainsString(
                'class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4"',
                $html,
                "Notifikasi di {$nama} seharusnya tetap dibatasi selebar form.",
            );
        }
    }

    private function admin(): User
    {
        return User::where('role', 'admin')->firstOrFail();
    }
}
