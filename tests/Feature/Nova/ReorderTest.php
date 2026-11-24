<?php

use App\Models\Faq;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

dataset('reorderable lists', [
    'faqs' => ['faqs', Faq::class],
    'speakers' => ['speakers', Speaker::class],
]);

describe('with Nova access', function () {
    beforeEach(function () {
        Gate::define('viewNova', fn () => true);
    });

    it('saves the dragged order as positions 0, 1, 2…', function (string $list, string $model) {
        [$first, $second, $third] = $model::factory()->forEachSequence(['sort_order' => 0], ['sort_order' => 1], ['sort_order' => 2])->create();

        $response = $this->actingAs(User::factory()->create())
            ->postJson(route('nova.reorder', $list), ['ids' => [$third->id, $first->id, $second->id]]);

        $response->assertNoContent();
        expect($model::orderBy('sort_order')->pluck('id')->all())->toBe([$third->id, $first->id, $second->id]);
    })->with('reorderable lists');

    it('rejects an order that is not every record exactly once', function (string $list, string $model, Closure $order) {
        $records = $model::factory()->forEachSequence(['sort_order' => 0], ['sort_order' => 1], ['sort_order' => 2])->create();

        $response = $this->actingAs(User::factory()->create())
            ->postJson(route('nova.reorder', $list), ['ids' => $order($records->pluck('id'))]);

        $response->assertUnprocessable();
        expect($model::orderBy('sort_order')->pluck('id')->all())->toBe($records->pluck('id')->all());
    })->with('reorderable lists')->with([
        'one left out' => fn () => fn ($ids) => [$ids[2], $ids[0]],
        'one listed twice' => fn () => fn ($ids) => [$ids[2], $ids[2], $ids[0]],
        'an unknown id' => fn () => fn ($ids) => [$ids[2], $ids[0], 999],
    ]);

    it('only reorders the lists it knows', function () {
        $this->actingAs(User::factory()->create())
            ->postJson('/nova-vendor/partners/reorder', ['ids' => [1]])
            ->assertNotFound();
    });
});

it('rejects users who cannot open Nova', function (string $list, string $model) {
    $record = $model::factory()->create();

    $this->postJson(route('nova.reorder', $list), ['ids' => [$record->id]])->assertUnauthorized();
    $this->actingAs(User::factory()->create())
        ->postJson(route('nova.reorder', $list), ['ids' => [$record->id]])
        ->assertForbidden();
})->with('reorderable lists');
