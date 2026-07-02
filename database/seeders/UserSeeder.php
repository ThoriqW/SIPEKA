<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $opdBkpsdm = Opd::where('kode_opd', 'BKPSDM')->first();
        $opdDikbud = Opd::where('kode_opd', 'DIKBUD')->first();
        $opdDinkes = Opd::where('kode_opd', 'DINKES')->first();

        User::create([
            'name' => 'Admin BKD',
            'email' => 'admin@bkd.palu.go.id',
            'password' => Hash::make('password'),
            'role' => 'bkd',
            'opd_id' => $opdBkpsdm->id,
        ]);

        User::create([
            'name' => 'Admin Dikbud',
            'email' => 'admin@dikbud.palu.go.id',
            'password' => Hash::make('password'),
            'role' => 'admin_opd',
            'opd_id' => $opdDikbud->id,
        ]);

        User::create([
            'name' => 'Admin Dinkes',
            'email' => 'admin@dinkes.palu.go.id',
            'password' => Hash::make('password'),
            'role' => 'admin_opd',
            'opd_id' => $opdDinkes->id,
        ]);
    }
}
