<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnorSeeder::class,
            UserSeeder::class,
            ReferensiJabatanSeeder::class,
            JabatanSeeder::class,
            SotkSeeder::class,
            KebutuhanSeeder::class,
            PegawaiSeeder::class,
            PenempatanSeeder::class,
            MasterTugasTambahanSeeder::class,
            TugasTambahanSeeder::class,
        ]);
    }
}
