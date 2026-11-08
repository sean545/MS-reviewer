<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

/**
 * Admins manage every course; professors see and edit only the courses they handle.
 */
class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Course $course): bool
    {
        return $user->isAdmin() || $course->isHandledBy($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Course $course): bool
    {
        return $user->isAdmin() || $course->isHandledBy($user);
    }

    public function delete(User $user, Course $course): bool
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
