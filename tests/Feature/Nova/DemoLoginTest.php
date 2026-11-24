<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['nova.demo_login' => ['email' => 'demo@example.com', 'password' => 'demo-password']]);
});

it('fills the login form with the demo account when running locally', function () {
    app()->detectEnvironment(fn () => 'local');

    $this->get('/nova/login')
        ->assertOk()
        ->assertSee('{"email":"demo@example.com","password":"demo-password"}', false);
});

it('never adds the demo account outside the local environment', function (string $environment) {
    app()->detectEnvironment(fn () => $environment);

    $this->get('/nova/login')->assertOk()->assertDontSee('demo@example.com');
})->with(['production', 'staging', 'testing']);

it('adds nothing locally when no demo email is set', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['nova.demo_login.email' => null]);

    $this->get('/nova/login')->assertOk()->assertDontSee('demoLogin');
});
