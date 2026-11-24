<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:account')]
#[Description('Create or update the read-only demo account from NOVA_DEMO_EMAIL and NOVA_DEMO_PASSWORD')]
class CreateDemoAccount extends Command
{
    public function handle(): int
    {
        ['email' => $email, 'password' => $password] = config('nova.demo_login');

        if (blank($email) || blank($password)) {
            $this->components->error('Set NOVA_DEMO_EMAIL and NOVA_DEMO_PASSWORD first.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);

        // Never turn someone's real account into the public, read-only one.
        if ($user->exists && ! $user->is_read_only) {
            $this->components->error("{$email} is a regular account. Use a different email for the demo account.");

            return self::FAILURE;
        }

        $user->forceFill([
            'name' => $user->name ?? 'Demo Visitor',
            'password' => $password,
            'is_read_only' => true,
        ])->save();

        $this->components->info("The read-only demo account {$email} is ready.");

        return self::SUCCESS;
    }
}
