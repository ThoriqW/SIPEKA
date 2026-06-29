<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin BKD',
            'email' => 'admin@bkd.palu.go.id',
            'password' => Hash::make('password'),
            'role' => 'bkd',
            'opd_id' => 4, // BKPSDM
        ]);

        User::create([
            'name' => 'Admin Dikbud',
            'email' => 'admin@dikbud.palu.go.id',
            'password' => Hash::make('password'),
            'role' => 'admin_opd',
            'opd_id' => 1, // DIKBUD
        ]);

        User::create([
            'name' => 'Admin Dinkes',
            'email' => 'admin@dinkes.palu.go.id',
            'password' => Hash::make('password'),
            'role' => 'admin_opd',
            'opd_id' => 2, // DINKES
        ]);
    }
}
