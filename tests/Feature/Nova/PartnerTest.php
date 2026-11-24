<?php

use App\Enums\PartnerTier;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    Gate::define('viewNova', fn () => true);
});

it('creates a partner in a tier', function () {
    $response = $this->actingAs(User::factory()->create())->postJson('/nova-api/partners', [
        'name' => 'Laravel',
        'tier' => 'gold',
        'url' => 'https://laravel.com',
        'sort_order' => 3,
    ]);

    $response->assertCreated();
    expect(Partner::sole())->name->toBe('Laravel')->tier->toBe(PartnerTier::Gold);
});

it('moves a deleted partner to the trash instead of removing it', function () {
    $partner = Partner::factory()->create();

    $response = $this->actingAs(User::factory()->create())->deleteJson("/nova-api/partners?resources[]={$partner->id}");

    $response->assertOk();
    $this->assertSoftDeleted($partner);
});

it('rejects a tier that does not exist', function () {
    $response = $this->actingAs(User::factory()->create())->postJson('/nova-api/partners', [
        'name' => 'Laravel',
        'tier' => 'platinum',
        'sort_order' => 0,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['tier' => 'The selected Tier is invalid.']);
    $this->assertDatabaseCount('partners', 0);
});

it('shows the tier badge on the index for every tier', function (PartnerTier $tier) {
    Partner::factory()->create(['tier' => $tier]);

    $response = $this->actingAs(User::factory()->create())->getJson('/nova-api/partners');

    $response->assertOk();
    expect(collect($response->json('resources.0.fields'))->firstWhere('name', 'Tier')['label'])->toBe($tier->label());
})->with(PartnerTier::cases());
