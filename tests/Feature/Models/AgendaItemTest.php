<?php

use App\Models\AgendaItem;
use App\Models\Speaker;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('derives the session format from tags, title and speakers', function (string $title, array $tags, int $speakerCount, string $expected) {
    $item = AgendaItem::factory()->create(['title' => $title]);
    $item->tags()->attach(collect($tags)->map(fn (string $name) => Tag::factory()->create(['name' => $name])->id));
    $item->speakers()->attach(Speaker::factory()->count($speakerCount)->create());

    expect($item->fresh()->format)->toBe($expected);
})->with([
    'panel tag' => ['The CTO Playbook', ['panel'], 2, 'Panel'],
    'three or more speakers' => ['The AI Reality Check', [], 3, 'Panel'],
    'workshop tag' => ['Agentic coding', ['AI', 'workshop'], 1, 'Workshop'],
    'podcast title' => ['Podcast TBD', [], 1, 'Podcast'],
    'AMA title' => ['Ask Me Anything', [], 1, 'AMA'],
    'no speakers' => ['Opening', [], 0, 'Special'],
    'single speaker talk' => ['Modern Java', ['Engineering'], 1, 'Talk'],
]);
