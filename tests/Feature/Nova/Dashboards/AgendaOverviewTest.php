<?php

use App\Models\AgendaItem;
use App\Models\Speaker;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    Gate::define('viewNova', fn () => true);
});

function agendaOverviewMetric(string $uriKey): TestResponse
{
    return test()->actingAs(User::factory()->create())
        ->getJson("/nova-api/dashboards/cards/agenda-overview/metrics/{$uriKey}")
        ->assertOk();
}

it('rounds the total session length to whole hours', function () {
    AgendaItem::factory()->forEachSequence(
        ['starts_at' => '2026-11-26 09:00', 'ends_at' => '2026-11-26 09:50'],
        ['starts_at' => '2026-11-26 10:00', 'ends_at' => '2026-11-26 10:50'],
    )->create();

    agendaOverviewMetric('hours-of-content')->assertJsonPath('value.value', 2);
});

describe('topics', function () {
    it('counts sessions per tag, leaving out tags that mark the format', function () {
        $ai = Tag::factory()->create(['name' => 'AI']);
        $workshop = Tag::factory()->create(['name' => 'workshop']);
        AgendaItem::factory()->count(2)->hasAttached($ai)->create();
        AgendaItem::factory()->hasAttached([$ai, $workshop])->create();
        AgendaItem::factory()->hasAttached(Tag::factory()->state(['name' => 'dev']))->create();

        $response = agendaOverviewMetric('topics');

        expect(collect($response->json('value.value'))->pluck('value', 'label')->all())
            ->toEqualCanonicalizing(['AI' => 3, 'Dev' => 1]);
    });

    it('groups everything after the top six topics as Other', function () {
        foreach (['AI', 'Dev', 'Product', 'Business', 'Security', 'Future', 'Leadership', 'Robotics'] as $index => $name) {
            AgendaItem::factory()->count(8 - $index)->hasAttached(Tag::factory()->create(['name' => $name]))->create();
        }

        $response = agendaOverviewMetric('topics');

        expect(collect($response->json('value.value'))->pluck('value', 'label')->all())
            ->toBe(['AI' => 8, 'Dev' => 7, 'Product' => 6, 'Business' => 5, 'Security' => 4, 'Future' => 3, 'Other' => 3])
            ->and(collect($response->json('value.value'))->firstWhere('label', 'Other')['color'])->toBe('#94A3B8');
    });
});

it('buckets sessions by length, in length order', function () {
    AgendaItem::factory()->forEachSequence(
        ['starts_at' => '2026-11-26 09:00', 'ends_at' => '2026-11-26 10:31'],
        ['starts_at' => '2026-11-26 09:00', 'ends_at' => '2026-11-26 10:30'],
        ['starts_at' => '2026-11-26 09:00', 'ends_at' => '2026-11-26 09:31'],
        ['starts_at' => '2026-11-26 09:00', 'ends_at' => '2026-11-26 09:30'],
        ['starts_at' => '2026-11-26 10:00', 'ends_at' => '2026-11-26 10:25'],
    )->create();

    $response = agendaOverviewMetric('session-lengths');

    expect(collect($response->json('value.value'))->pluck('value', 'label')->all())
        ->toBe(['Up to 30 min' => 2, '31–50 min' => 1, '51–90 min' => 1, 'Over 90 min' => 1]);
});

describe('panel discussions', function () {
    it('lists panels with their moderator, panelist count, stage and Warsaw start time', function () {
        $panel = AgendaItem::factory()
            ->for(Stage::factory()->create(['name' => 'MAIN']))
            ->hasAttached(Tag::factory()->state(['name' => 'panel']))
            ->create(['title' => 'The CTO Playbook', 'starts_at' => '2026-11-26 10:00']);
        $panel->speakers()->attach(Speaker::factory()->create(['name' => 'Jane Host']), ['is_moderator' => true]);
        $panel->speakers()->attach(Speaker::factory()->count(2)->create());

        $response = agendaOverviewMetric('panel-discussions');

        $response->assertJsonCount(1, 'value')
            ->assertJsonPath('value.0.title', 'The CTO Playbook')
            ->assertJsonPath('value.0.subtitle', 'Moderated by Jane Host · 2 panelists · MAIN · Thu 11:00');
    });

    it('leaves out talks and panels without an announced line-up', function () {
        AgendaItem::factory()->hasAttached(Speaker::factory())->create(['title' => 'Modern Java']);
        AgendaItem::factory()->hasAttached(Tag::factory()->state(['name' => 'panel']))->create(['title' => 'TBD']);

        agendaOverviewMetric('panel-discussions')->assertJsonCount(0, 'value');
    });
});
