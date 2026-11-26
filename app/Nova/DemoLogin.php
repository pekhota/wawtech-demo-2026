<?php

namespace App\Nova;

use App\Models\User;

/**
 * The account pre-filled on Nova's login page (NOVA_DEMO_EMAIL / NOVA_DEMO_PASSWORD),
 * see resources/views/vendor/nova/partials/meta.blade.php.
 */
class DemoLogin
{
    /**
     * The password ends up in the page source, so outside the local environment
     * the form is only filled in for an existing read-only account.
     *
     * @return array{email: string, password: string}|null
     */
    public static function credentials(): ?array
    {
        $email = config('nova.demo_login.email');

        if (blank($email)) {
            return null;
        }

        if (! app()->isLocal() && ! User::where('email', $email)->where('is_read_only', true)->exists()) {
            return null;
        }

        return ['email' => $email, 'password' => (string) config('nova.demo_login.password')];
    }
}
