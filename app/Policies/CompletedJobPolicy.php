<?php

namespace App\Policies;

use App\Models\CompletedJob;
use App\Models\User;

class CompletedJobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessSystemResources();
    }

    public function view(User $user, CompletedJob $completedJob): bool
    {
        return $user->canAccessSystemResources();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CompletedJob $completedJob): bool
    {
        return false;
    }

    public function delete(User $user, CompletedJob $completedJob): bool
    {
        return $user->canAccessSystemResources();
    }

    public function restore(User $user, CompletedJob $completedJob): bool
    {
        return false;
    }

    public function forceDelete(User $user, CompletedJob $completedJob): bool
    {
        return $user->canAccessSystemResources();
    }
}
