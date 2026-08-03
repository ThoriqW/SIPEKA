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
            KebutuhanSeeder::class,
            PegawaiSeeder::class,
            MasterTugasTambahanSeeder::class,
            TugasTambahanSeeder::class,
        ]);
    }
}
