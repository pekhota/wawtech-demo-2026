<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('forbids Nova to users missing from the viewNova gate outside local', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/nova-api/speakers');

    $response->assertForbidden();
});
