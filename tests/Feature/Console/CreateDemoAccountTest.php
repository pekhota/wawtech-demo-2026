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

it('creates the demo account from the email and password given on the command line', function () {
    config(['nova.demo_login' => ['email' => null, 'password' => null]]);

    $this->artisan('demo:account', ['--email' => 'visitor@wawtech.test', '--password' => 'inline-password'])
        ->assertSuccessful();

    $user = User::where('email', 'visitor@wawtech.test')->sole();
    expect($user->is_read_only)->toBeTrue()
        ->and(Hash::check('inline-password', $user->password))->toBeTrue();
});

it('prefers the command line over the demo login settings', function () {
    $this->artisan('demo:account', ['--email' => 'visitor@wawtech.test', '--password' => 'inline-password'])
        ->assertSuccessful();

    $this->assertDatabaseMissing('users', ['email' => 'demo@wawtech.test']);
    expect(Hash::check('inline-password', User::where('email', 'visitor@wawtech.test')->sole()->password))->toBeTrue();
});

it('warns that the login page is not pre-filled when the account differs from the demo login settings', function (array $options) {
    $this->artisan('demo:account', $options)
        ->expectsOutputToContain('NOVA_DEMO_EMAIL and NOVA_DEMO_PASSWORD')
        ->assertSuccessful();
})->with([
    'another email' => [['--email' => 'visitor@wawtech.test']],
    'another password' => [['--password' => 'inline-password']],
]);

it('gives no pre-fill warning when the account matches the demo login settings', function () {
    $this->artisan('demo:account', ['--email' => 'demo@wawtech.test', '--password' => 'demo-password'])
        ->doesntExpectOutputToContain('NOVA_DEMO_EMAIL and NOVA_DEMO_PASSWORD')
        ->assertSuccessful();
});

it('refuses an email given on the command line that is not an email address', function () {
    $this->artisan('demo:account', ['--email' => 'not-an-email', '--password' => 'inline-password'])
        ->expectsOutputToContain('not-an-email is not an email address.')
        ->assertFailed();

    $this->assertDatabaseCount('users', 0);
});
