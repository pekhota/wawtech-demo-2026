<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('includes the theme on the login page', function () {
    $this->get('/nova/login')->assertOk()->assertSee('--wt-gradient', false);
});

it('includes the theme for signed-in users', function () {
    Gate::define('viewNova', fn () => true);

    $this->actingAs(User::factory()->create())
        ->get('/nova/dashboards/main')
        ->assertOk()
        ->assertSee('--wt-gradient', false);
});
