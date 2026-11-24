<?php

use App\Models\AgendaItem;
use App\Models\Speaker;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    Gate::define('viewNova', fn () => true);
});

it('creates an agenda item on a stage', function () {
    $stage = Stage::factory()->create();

    $response = $this->actingAs(User::factory()->create())->postJson('/nova-api/agenda-items', [
        'title' => 'Opening',
        'stage' => $stage->id,
        'starts_at' => '2026-11-26 08:00:00',
        'ends_at' => '2026-11-26 08:25:00',
        'language' => 'en',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('agenda_items', [
        'title' => 'Opening',
        'stage_id' => $stage->id,
        'starts_at' => '2026-11-26 08:00:00',
    ]);
});

it('rejects an agenda item that ends before it starts', function () {
    $response = $this->actingAs(User::factory()->create())->postJson('/nova-api/agenda-items', [
        'title' => 'Opening',
        'starts_at' => '2026-11-26 09:00:00',
        'ends_at' => '2026-11-26 08:30:00',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['ends_at' => 'The Ends At field must be a date after Starts At.']);
    $this->assertDatabaseCount('agenda_items', 0);
});

it('lists agenda items by start time', function () {
    $late = AgendaItem::factory()->create(['starts_at' => '2026-11-27 16:00', 'ends_at' => '2026-11-27 16:45']);
    $early = AgendaItem::factory()->create(['starts_at' => '2026-11-26 09:00', 'ends_at' => '2026-11-26 09:25']);

    $response = $this->actingAs(User::factory()->create())->getJson('/nova-api/agenda-items');

    $response->assertOk();
    expect(collect($response->json('resources'))->pluck('id.value')->all())->toBe([$early->id, $late->id]);
});

it('shows the format badge on the index for every session format', function (string $title, array $tags, int $speakerCount, string $format) {
    $item = AgendaItem::factory()->create(['title' => $title]);
    $item->tags()->attach(collect($tags)->map(fn (string $name) => Tag::factory()->create(['name' => $name])->id));
    $item->speakers()->attach(Speaker::factory()->count($speakerCount)->create());

    $response = $this->actingAs(User::factory()->create())->getJson('/nova-api/agenda-items');

    $response->assertOk();
    expect(collect($response->json('resources.0.fields'))->firstWhere('name', 'Format')['label'])->toBe($format);
})->with([
    'talk' => ['Modern Java', [], 1, 'Talk'],
    'panel' => ['The CTO Playbook', [], 3, 'Panel'],
    'workshop' => ['Agentic coding', ['workshop'], 1, 'Workshop'],
    'podcast' => ['Podcast TBD', [], 1, 'Podcast'],
    'AMA' => ['Ask Me Anything', [], 1, 'AMA'],
    'special' => ['Opening', [], 0, 'Special'],
]);

it('escapes the stage name in the index slot column', function () {
    AgendaItem::factory()->for(Stage::factory()->create(['name' => '<b>MAIN']))->create();

    $response = $this->actingAs(User::factory()->create())->getJson('/nova-api/agenda-items');

    $response->assertOk()
        ->assertSee('&lt;b&gt;MAIN', false)
        ->assertDontSee('<b>MAIN', false);
});
