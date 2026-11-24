<?php

use App\Models\Speaker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Gate::define('viewNova', fn () => true);
});

it('features the selected speakers on the homepage', function () {
    [$selected, $other] = Speaker::factory()->count(2)->create(['is_featured' => false]);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/nova-api/speakers/action?action=feature-on-homepage', ['resources' => [$selected->id]]);

    $response->assertOk();
    expect($selected->fresh()->is_featured)->toBeTrue()
        ->and($other->fresh()->is_featured)->toBeFalse();
});

it('removes the selected speakers from the homepage', function () {
    $speaker = Speaker::factory()->create(['is_featured' => true]);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/nova-api/speakers/action?action=remove-from-homepage', ['resources' => [$speaker->id]]);

    $response->assertOk();
    expect($speaker->fresh()->is_featured)->toBeFalse();
});

it('rejects a slug that another speaker already uses', function () {
    Speaker::factory()->create(['slug' => 'daniel-stenberg']);

    $response = $this->actingAs(User::factory()->create())->postJson('/nova-api/speakers', [
        'name' => 'Daniel Stenberg',
        'slug' => 'daniel-stenberg',
        'sort_order' => 0,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['slug' => 'The Slug has already been taken.']);
    $this->assertDatabaseCount('speakers', 1);
});

it('puts a new speaker at the end of the list', function () {
    Speaker::factory()->forEachSequence(['sort_order' => 0], ['sort_order' => 1])->create();

    $response = $this->actingAs(User::factory()->create())->postJson('/nova-api/speakers', [
        'name' => 'Taylor Otwell',
        'slug' => 'taylor-otwell',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('speakers', ['slug' => 'taylor-otwell', 'sort_order' => 2]);
});

it('gives the reorder card each speaker with photo and featured flag, in order', function () {
    $later = Speaker::factory()->create(['name' => 'Later Speaker', 'photo' => null, 'sort_order' => 1]);
    $sooner = Speaker::factory()->create(['name' => 'Sooner Speaker', 'photo' => 'speakers/sooner.webp', 'is_featured' => true, 'sort_order' => 0]);

    $response = $this->actingAs(User::factory()->create())->getJson('/nova-api/speakers/cards');

    $response->assertOk();
    $card = collect($response->json())->firstWhere('component', 'sortable-card');
    expect($card['items'])->toBe([
        ['id' => $sooner->id, 'label' => 'Sooner Speaker', 'image' => Storage::disk('public')->url('speakers/sooner.webp'), 'highlight' => true],
        ['id' => $later->id, 'label' => 'Later Speaker', 'image' => null, 'highlight' => false],
    ])->and($card['layout'])->toBe('grid')
        ->and($card['saveUrl'])->toBe('/nova-vendor/speakers/reorder');
});
