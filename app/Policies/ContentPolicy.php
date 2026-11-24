<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared by the content models: everyone allowed into Nova may look, but
 * read-only accounts (the public demo login) may not change anything.
 *
 * Nova allows any ability a policy doesn't define (attach…, detach…, add…),
 * so instead of listing them, every ability except viewing goes through
 * __call and is denied to read-only accounts.
 *
 * Laravel asks this policy about any ability checked against a content model,
 * including ones made with Gate::define, so don't define gates that take one.
 */
class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return true;
    }

    /**
     * Every other ability: create, update, delete, restore, forceDelete, replicate,
     * runAction, runDestructiveAction, attachAny…, attach…, detach… and add….
     *
     * @param  array{0: User}  $arguments  The user, then any models the ability is checked against.
     */
    public function __call(string $ability, array $arguments): bool
    {
        return ! $arguments[0]->is_read_only;
    }
}
