<?php

namespace App\Policies;

use App\Models\Unor;
use App\Models\User;

class UnorPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Unor $opd): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Unor $opd): bool { return true; }
    public function delete(User $user, Unor $opd): bool { return true; }
}
