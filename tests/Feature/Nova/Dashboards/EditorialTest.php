<?php

use App\Models\AgendaItem;
use App\Models\Speaker;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    Gate::define('viewNova', fn () => true);
});

describe('agenda finalized', function () {
    it('counts sessions whose title is not a TBD / TBA placeholder', function () {
        AgendaItem::factory()->sequence(
            ['title' => 'TBD'],
            ['title' => 'Podcast TBD'],
            ['title' => 'TBA'],
            ['title' => 'Tabular AI: In-Context Learning'],
            ['title' => 'Opening'],
        )->count(5)->create();

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/nova-api/dashboards/cards/editorial/metrics/agenda-finalized');

        $response->assertOk()
            ->assertJsonPath('value.value', 2)
            ->assertJsonPath('value.target', 5);
    });
});

describe('speakers scheduled', function () {
    it('counts announced speakers who have a session', function () {
        Speaker::factory()->hasAttached(AgendaItem::factory())->create();
        Speaker::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/nova-api/dashboards/cards/editorial/metrics/speakers-scheduled');

        $response->assertOk()
            ->assertJsonPath('value.value', 1)
            ->assertJsonPath('value.target', 2);
    });
});

describe('needs attention', function () {
    it('lists sessions without a stage, unscheduled speakers and placeholder sessions', function () {
        $stage = Stage::factory()->create(['name' => 'ENGINEERING']);
        $unstaged = AgendaItem::factory()->create(['title' => 'Duplicate panel', 'stage_id' => null, 'starts_at' => '2026-11-26 13:00:00']);
        $unscheduled = Speaker::factory()->create(['name' => 'Kacper Reutt']);
        $placeholder = AgendaItem::factory()->for($stage)->create(['title' => 'TBA', 'starts_at' => '2026-11-26 14:00:00']);
        $placeholder->speakers()->attach(Speaker::factory()->create(['name' => 'Anastasiia Voitova']));
        AgendaItem::factory()->for($stage)->hasAttached(Speaker::factory())->create(['title' => 'Modern Java']);

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/nova-api/dashboards/cards/editorial/metrics/needs-attention');

        $response->assertOk();
        expect(collect($response->json('value'))->map(fn (array $row) => [$row['title'], $row['subtitle'], $row['actions'][0]['path']])->all())->toBe([
            ['Duplicate panel', 'No stage assigned · Thu 26 Nov, 14:00', "/nova/resources/agenda-items/{$unstaged->id}/edit"],
            ['Kacper Reutt', 'Announced speaker without a session', "/nova/resources/speakers/{$unscheduled->id}"],
            ['Anastasiia Voitova', 'Title not announced · ENGINEERING · Thu 26 Nov, 15:00', "/nova/resources/agenda-items/{$placeholder->id}/edit"],
        ]);
    });
});
