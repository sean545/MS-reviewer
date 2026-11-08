<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

/**
 * Only admins manage degree programs.
 */
class ProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Program $program): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Program $program): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isAdmin();
    }
}
