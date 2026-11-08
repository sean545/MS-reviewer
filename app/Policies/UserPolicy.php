<?php

namespace App\Policies;

use App\Models\User;

/**
 * Only admins can see and manage accounts and their roles.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins can remove other accounts, but never their own.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
