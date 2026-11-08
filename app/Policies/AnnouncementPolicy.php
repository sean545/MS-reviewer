<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

/**
 * Admins manage every announcement; professors manage those for the courses they handle.
 */
class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $this->manages($user, $announcement);
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $this->manages($user, $announcement);
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->manages($user, $announcement);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isStaff();
    }

    private function manages(User $user, Announcement $announcement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $announcement->course !== null && $announcement->course->isHandledBy($user);
    }
}
