<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['nova.demo_login' => ['email' => 'demo@example.com', 'password' => 'demo-password']]);
});

it('fills the login form with any demo account when running locally', function () {
    app()->detectEnvironment(fn () => 'local');

    $this->get('/nova/login')
        ->assertOk()
        ->assertSee('{"email":"demo@example.com","password":"demo-password"}', false);
});

it('fills the login form outside local only for a read-only account', function (string $environment) {
    app()->detectEnvironment(fn () => $environment);
    User::factory()->readOnly()->create(['email' => 'demo@example.com']);

    $this->get('/nova/login')
        ->assertOk()
        ->assertSee('{"email":"demo@example.com","password":"demo-password"}', false);
})->with(['production', 'staging']);

it('never shows a regular or unknown account outside local', function (Closure $account) {
    app()->detectEnvironment(fn () => 'production');
    $account();

    $this->get('/nova/login')->assertOk()->assertDontSee('demo-password');
})->with([
    'regular account' => fn () => fn () => User::factory()->create(['email' => 'demo@example.com']),
    'no account' => fn () => fn () => null,
]);

it('adds nothing when no demo email is set', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['nova.demo_login.email' => null]);

    $this->get('/nova/login')->assertOk()->assertDontSee('demoLogin');
});

it('only fills the login page', function () {
    app()->detectEnvironment(fn () => 'local');

    $this->actingAs(User::factory()->create())
        ->get('/nova/dashboards/main')
        ->assertOk()
        ->assertDontSee('demo-password');
});
