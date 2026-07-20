<?php

namespace App\Policies;

use App\Models\Opd;
use App\Models\User;

class OpdPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Opd $opd): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Opd $opd): bool { return true; }
    public function delete(User $user, Opd $opd): bool { return true; }
}
