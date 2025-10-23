<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessSystemResources();
    }

    public function view(User $user, Job $job): bool
    {
        return $user->canAccessSystemResources();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Job $job): bool
    {
        return false;
    }

    public function delete(User $user, Job $job): bool
    {
        return $user->canAccessSystemResources();
    }

    public function restore(User $user, Job $job): bool
    {
        return false;
    }

    public function forceDelete(User $user, Job $job): bool
    {
        return $user->canAccessSystemResources();
    }
}
