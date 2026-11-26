<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('forbids Nova to users missing from the viewNova gate outside local', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/nova-api/speakers');

    $response->assertForbidden();
});

it('admits users listed in NOVA_ADMIN_EMAILS outside local, ignoring case', function () {
    config(['nova.admin_emails' => ['admin@wawtech.test']]);
    $user = User::factory()->create(['email' => 'Admin@WAWTech.test']);

    $response = $this->actingAs($user)->getJson('/nova-api/speakers');

    $response->assertOk();
});

it('offers no password reset by email', function (string $method, string $path) {
    $response = $this->json($method, $path, ['email' => 'admin@wawtech.test']);

    $response->assertNotFound();
})->with([
    'the forgot password page' => ['GET', '/nova/password/reset'],
    'requesting a reset link' => ['POST', '/nova/password/email'],
    'setting a new password' => ['POST', '/nova/password/reset'],
]);
