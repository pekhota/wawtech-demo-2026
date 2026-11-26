<?php

namespace App\Policies;

use App\Models\User;

/**
 * Read-only accounts (the public demo login) can't see the user list at all,
 * so the demo doesn't expose the admins' email addresses. Everything else stays
 * visible to them; RejectChangesFromReadOnlyAccounts turns their changes away.
 *
 * Every ability is spelled out: Nova checks the viewNova gate with the user as
 * an argument, and a catch-all __call here would answer that check instead of
 * the gate.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageUsers($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->canManageUsers($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageUsers($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->canManageUsers($user);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->canManageUsers($user);
    }

    public function restore(User $user, User $model): bool
    {
        return $this->canManageUsers($user);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->canManageUsers($user);
    }

    public function replicate(User $user, User $model): bool
    {
        return $this->canManageUsers($user);
    }

    private function canManageUsers(User $user): bool
    {
        return ! $user->is_read_only;
    }
}
