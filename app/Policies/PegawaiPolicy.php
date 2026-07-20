<?php

namespace App\Policies;

use App\Models\Pegawai;
use App\Models\User;

class PegawaiPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Pegawai $pegawai): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Pegawai $pegawai): bool { return true; }
    public function delete(User $user, Pegawai $pegawai): bool { return true; }
}
