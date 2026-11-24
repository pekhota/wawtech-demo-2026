<?php

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    Gate::define('viewNova', fn () => true);
});

it('puts a new FAQ at the end of the list', function () {
    Faq::factory()->forEachSequence(['sort_order' => 0], ['sort_order' => 1])->create();

    $response = $this->actingAs(User::factory()->create())->postJson('/nova-api/faqs', [
        'question' => 'Is there Wi-Fi at the venue?',
        'answer' => '<p>Yes.</p>',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('faqs', ['question' => 'Is there Wi-Fi at the venue?', 'sort_order' => 2]);
});

it('gives the reorder card the FAQs in their current order', function () {
    $later = Faq::factory()->create(['question' => 'Later?', 'sort_order' => 1]);
    $sooner = Faq::factory()->create(['question' => 'Sooner?', 'sort_order' => 0]);

    $response = $this->actingAs(User::factory()->create())->getJson('/nova-api/faqs/cards');

    $response->assertOk();
    $card = collect($response->json())->firstWhere('component', 'sortable-card');
    expect($card['items'])->toBe([
        ['id' => $sooner->id, 'label' => 'Sooner?'],
        ['id' => $later->id, 'label' => 'Later?'],
    ])->and($card['layout'])->toBe('list')
        ->and($card['saveUrl'])->toBe('/nova-vendor/faqs/reorder');
});
