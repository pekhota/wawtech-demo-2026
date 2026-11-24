<?php

use App\Models\Faq;
use App\Models\Partner;
use App\Models\Speaker;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    Gate::define('viewNova', fn () => true);
});

it('lists records by sort order rather than newest first', function (string $uriKey, string $model) {
    $second = $model::factory()->create(['sort_order' => 2]);
    $first = $model::factory()->create(['sort_order' => 1]);
    $third = $model::factory()->create(['sort_order' => 3]);

    $response = $this->actingAs(User::factory()->create())->getJson("/nova-api/{$uriKey}");

    $response->assertOk();
    expect(collect($response->json('resources'))->pluck('id.value')->all())->toBe([$first->id, $second->id, $third->id]);
})->with([
    'speakers' => ['speakers', Speaker::class],
    'partners' => ['partners', Partner::class],
    'faqs' => ['faqs', Faq::class],
    'stages' => ['stages', Stage::class],
]);
