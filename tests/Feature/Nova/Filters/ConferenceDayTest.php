<?php

use App\Models\AgendaItem;
use App\Models\User;
use App\Nova\Filters\ConferenceDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    Gate::define('viewNova', fn () => true);
});

function conferenceDayFilter(string $day): string
{
    return base64_encode(json_encode([[ConferenceDay::class => $day]]));
}

it('limits sessions to the selected day in Warsaw time', function () {
    $morning = AgendaItem::factory()->create(['starts_at' => '2026-11-26 08:00:00', 'ends_at' => '2026-11-26 08:25:00']);
    $afterMidnightInWarsaw = AgendaItem::factory()->create(['starts_at' => '2026-11-26 23:30:00', 'ends_at' => '2026-11-26 23:55:00']);
    $nextDay = AgendaItem::factory()->create(['starts_at' => '2026-11-27 08:00:00', 'ends_at' => '2026-11-27 08:25:00']);

    $response = $this->actingAs(User::factory()->create())
        ->getJson('/nova-api/agenda-items?filters='.conferenceDayFilter('2026-11-27'));

    $response->assertOk();
    expect(collect($response->json('resources'))->pluck('id.value')->all())->toBe([$afterMidnightInWarsaw->id, $nextDay->id])
        ->not->toContain($morning->id);
});

it('offers one option per conference day', function () {
    AgendaItem::factory()->create(['starts_at' => '2026-11-26 08:00:00', 'ends_at' => '2026-11-26 08:25:00']);
    AgendaItem::factory()->create(['starts_at' => '2026-11-26 12:00:00', 'ends_at' => '2026-11-26 12:25:00']);
    AgendaItem::factory()->create(['starts_at' => '2026-11-27 08:00:00', 'ends_at' => '2026-11-27 08:25:00']);

    $response = $this->actingAs(User::factory()->create())->getJson('/nova-api/agenda-items/filters');

    $response->assertOk();
    $filter = collect($response->json())->firstWhere('class', ConferenceDay::class);
    expect(collect($filter['options'])->pluck('value', 'label')->all())->toBe([
        'Thursday, 26 Nov' => '2026-11-26',
        'Friday, 27 Nov' => '2026-11-27',
    ]);
});
