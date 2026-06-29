<?php

namespace App\Policies;

use App\Models\Pegawai;
use App\Models\User;

class PegawaiPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Pegawai $pegawai): bool
    {
        if ($user->isBkd()) return true;
        return $user->opd_id === $pegawai->opd_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Pegawai $pegawai): bool
    {
        if ($user->isBkd()) return true;
        return $user->opd_id === $pegawai->opd_id;
    }

    public function delete(User $user, Pegawai $pegawai): bool
    {
        if ($user->isBkd()) return true;
        return $user->opd_id === $pegawai->opd_id;
    }
}
