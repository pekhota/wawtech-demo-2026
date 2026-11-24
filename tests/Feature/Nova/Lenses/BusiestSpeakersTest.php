<?php

use App\Models\AgendaItem;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    Gate::define('viewNova', fn () => true);
});

it('lists speakers with more than one session, busiest first', function () {
    $twoSessions = Speaker::factory()->hasAttached(AgendaItem::factory()->count(2))->create();
    $threeSessions = Speaker::factory()->hasAttached(AgendaItem::factory()->count(3))->create();
    Speaker::factory()->hasAttached(AgendaItem::factory())->create();
    Speaker::factory()->create();

    $response = $this->actingAs(User::factory()->create())->getJson('/nova-api/speakers/lens/busiest-speakers');

    $response->assertOk();
    expect(collect($response->json('resources'))->pluck('id.value')->all())->toBe([$threeSessions->id, $twoSessions->id]);
});
