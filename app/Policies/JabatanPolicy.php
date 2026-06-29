<?php

namespace App\Policies;

use App\Models\Jabatan;
use App\Models\User;

class JabatanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Jabatan $jabatan): bool
    {
        if ($user->isBkd()) return true;
        return $user->opd_id === $jabatan->opd_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Jabatan $jabatan): bool
    {
        if ($user->isBkd()) return true;
        return $user->opd_id === $jabatan->opd_id;
    }

    public function delete(User $user, Jabatan $jabatan): bool
    {
        if ($user->isBkd()) return true;
        return $user->opd_id === $jabatan->opd_id;
    }
}
