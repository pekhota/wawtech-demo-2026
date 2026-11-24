<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['nova.demo_login' => ['email' => 'demo@wawtech.test', 'password' => 'demo-password']]);
});

it('creates the read-only demo account from the demo login settings', function () {
    $this->artisan('demo:account')->assertSuccessful();

    $user = User::where('email', 'demo@wawtech.test')->sole();
    expect($user->is_read_only)->toBeTrue()
        ->and(Hash::check('demo-password', $user->password))->toBeTrue();
});

it('updates the password of an existing demo account', function () {
    User::factory()->readOnly()->create(['email' => 'demo@wawtech.test', 'password' => 'old-password']);

    $this->artisan('demo:account')->assertSuccessful();

    expect(Hash::check('demo-password', User::where('email', 'demo@wawtech.test')->sole()->password))->toBeTrue();
});

it('refuses to turn a regular account into the demo account', function () {
    $admin = User::factory()->create(['email' => 'demo@wawtech.test', 'password' => 'admin-password']);

    $this->artisan('demo:account')->assertFailed();

    expect($admin->fresh()->is_read_only)->toBeFalse()
        ->and(Hash::check('admin-password', $admin->fresh()->password))->toBeTrue();
});

it('fails when the demo login settings are missing', function () {
    config(['nova.demo_login.password' => null]);

    $this->artisan('demo:account')->assertFailed();

    $this->assertDatabaseCount('users', 0);
});
