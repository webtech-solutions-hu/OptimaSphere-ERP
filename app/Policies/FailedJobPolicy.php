<?php

namespace App\Policies;

use App\Models\FailedJob;
use App\Models\User;

class FailedJobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessSystemResources();
    }

    public function view(User $user, FailedJob $failedJob): bool
    {
        return $user->canAccessSystemResources();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, FailedJob $failedJob): bool
    {
        return false;
    }

    public function delete(User $user, FailedJob $failedJob): bool
    {
        return $user->canAccessSystemResources();
    }

    public function restore(User $user, FailedJob $failedJob): bool
    {
        return false;
    }

    public function forceDelete(User $user, FailedJob $failedJob): bool
    {
        return $user->canAccessSystemResources();
    }
}
