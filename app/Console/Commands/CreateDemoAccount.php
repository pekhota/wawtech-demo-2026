<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('demo:account
    {--email= : The demo account\'s email; defaults to NOVA_DEMO_EMAIL}
    {--password= : The demo account\'s password; defaults to NOVA_DEMO_PASSWORD}')]
#[Description('Create or update the read-only demo account')]
class CreateDemoAccount extends Command
{
    public function handle(): int
    {
        $email = $this->option('email') ?? config('nova.demo_login.email');
        $password = $this->option('password') ?? config('nova.demo_login.password');

        if (blank($email) || blank($password)) {
            $this->components->error('Pass --email and --password, or set NOVA_DEMO_EMAIL and NOVA_DEMO_PASSWORD first.');

            return self::FAILURE;
        }

        if (Validator::make(['email' => $email], ['email' => 'email'])->fails()) {
            $this->components->error("{$email} is not an email address.");

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

        if ($email !== config('nova.demo_login.email') || $password !== config('nova.demo_login.password')) {
            $this->components->warn('The login page is only pre-filled when NOVA_DEMO_EMAIL and NOVA_DEMO_PASSWORD are set to the same email and password.');
        }

        return self::SUCCESS;
    }
}
