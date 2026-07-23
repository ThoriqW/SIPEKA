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
            MasterJabatanSeeder::class,
            JabatanAsnSeeder::class,        // BARU: jabatan kepegawaian
            NodeOrganisasiSeeder::class,    // BARU: struktur organisasi (UNIT + POSISI)
            JabatanSeeder::class,           // DEPRECATED: backward compat
            PegawaiSeeder::class,
        ]);
    }
}
