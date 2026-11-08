<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

/**
 * Who may open a lesson PDF: admins, the course's professors, and anyone signed in once it is published.
 */
class MaterialPolicy
{
    public function view(User $user, Material $material): bool
    {
        if ($user->isAdmin() || $material->topic->course->isHandledBy($user)) {
            return true;
        }

        return $material->isVisibleToStudents();
    }
}
