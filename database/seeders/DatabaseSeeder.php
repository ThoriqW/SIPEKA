<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OpdSeeder::class,
            UserSeeder::class,
            JabatanAsnSeeder::class,        // Jabatan kepegawaian (katalog + jenjang)
            NodeOrganisasiSeeder::class,    // Struktur organisasi (UNIT + POSISI)
            PegawaiSeeder::class,
        ]);
    }
}
