<?php

namespace App\Nova\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Auth\Actions\UpdateUserPassword as NovaUpdateUserPassword;

/**
 * Nova's "User Security" password change, except for read-only accounts:
 * the public demo login is shared, so nobody may change its password.
 */
class UpdateUserPassword extends NovaUpdateUserPassword
{
    /**
     * @param  Authenticatable&User  $user
     * @param  array<string, string>  $input
     */
    public function update(Authenticatable $user, #[\SensitiveParameter] array $input): void
    {
        if ($user->is_read_only) {
            throw ValidationException::withMessages([
                'password' => __('The demo account is shared, so its password can\'t be changed.'),
            ]);
        }

        parent::update($user, $input);
    }
}
