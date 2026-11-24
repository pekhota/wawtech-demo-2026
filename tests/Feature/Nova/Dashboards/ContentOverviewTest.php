<?php

use App\Models\Speaker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    Gate::define('viewNova', fn () => true);
});

function contentOverviewMetric(string $uriKey): TestResponse
{
    return test()->actingAs(User::factory()->create())
        ->getJson("/nova-api/dashboards/cards/content-overview/metrics/{$uriKey}")
        ->assertOk();
}

it('counts distinct companies, ignoring speakers without one', function () {
    Speaker::factory()->forEachSequence(
        ['company' => 'Allegro'],
        ['company' => 'Allegro'],
        ['company' => 'Google'],
        ['company' => ''],
        ['company' => null],
    )->create();

    contentOverviewMetric('companies')->assertJsonPath('value.value', 2);
});

it('lists featured speakers in homepage order', function () {
    Speaker::factory()->create(['name' => 'José Valim', 'position' => 'Creator of Elixir', 'company' => null, 'is_featured' => true, 'sort_order' => 2]);
    Speaker::factory()->create(['name' => 'Daniel Stenberg', 'position' => 'Creator of cURL', 'company' => 'wolfSSL', 'is_featured' => true, 'sort_order' => 1]);
    Speaker::factory()->create(['is_featured' => false]);

    $response = contentOverviewMetric('featured-speakers');

    expect(collect($response->json('value'))->map(fn (array $row) => Arr::only($row, ['title', 'subtitle']))->all())->toBe([
        ['title' => 'Daniel Stenberg', 'subtitle' => 'Creator of cURL @ wolfSSL'],
        ['title' => 'José Valim', 'subtitle' => 'Creator of Elixir'],
    ]);
});

it('lists companies with several speakers, most speakers first', function () {
    Speaker::factory()->count(2)->create(['company' => 'Waymo']);
    Speaker::factory()->count(2)->create(['company' => 'Oracle']);
    Speaker::factory()->count(3)->create(['company' => 'Allegro']);
    Speaker::factory()->create(['company' => 'Solo Inc']);

    $response = contentOverviewMetric('most-represented-companies');

    expect(collect($response->json('value'))->pluck('title')->all())->toBe(['Allegro', 'Oracle', 'Waymo'])
        ->and($response->json('value.0.subtitle'))->toStartWith('3 speakers: ');
});

it('groups speakers by seniority from their job title', function (string $position, string $role) {
    Speaker::factory()->create(['position' => $position]);

    $response = contentOverviewMetric('whos-speaking');

    expect(collect($response->json('value.value'))->pluck('value', 'label')->all())->toBe([$role => 1]);
})->with([
    ['Founder & CEO', 'Founders & C-level'],
    ['Co-founder', 'Founders & C-level'],
    ['ex-VP Engineering', 'VPs, directors & heads'],
    ['Director of Engineering', 'VPs, directors & heads'],
    ['Head of Security Engineering', 'VPs, directors & heads'],
    ['Staff Software Engineer', 'Engineers & scientists'],
    ['Java Developer Advocate', 'Engineers & scientists'],
    ['Indie Dev', 'Engineers & scientists'],
    ['Product Manager', 'Product, people & more'],
]);
