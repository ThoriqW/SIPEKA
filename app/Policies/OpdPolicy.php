<?php

namespace App\Policies;

use App\Models\Opd;
use App\Models\User;

class OpdPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Opd $opd): bool
    {
        if ($user->isBkd()) return true;
        return $user->opd_id === $opd->id;
    }

    public function create(User $user): bool
    {
        return $user->isBkd();
    }

    public function update(User $user, Opd $opd): bool
    {
        return $user->isBkd();
    }

    public function delete(User $user, Opd $opd): bool
    {
        return $user->isBkd();
    }
}
