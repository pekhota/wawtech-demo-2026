<?php

use App\Enums\PartnerTier;
use App\Models\AgendaItem;
use App\Models\Partner;
use App\Models\Speaker;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Gate::define('viewNova', fn () => true);
});

describe('headline counts', function () {
    it('shows the total of each model', function (string $uriKey, Closure $create, int $expected) {
        $create();

        $response = $this->actingAs(User::factory()->create())
            ->getJson("/nova-api/dashboards/cards/main/metrics/{$uriKey}");

        $response->assertOk()->assertJsonPath('value.value', $expected);
    })->with([
        'speakers' => ['speakers', fn () => Speaker::factory()->count(3)->create(), 3],
        'sessions' => ['sessions', fn () => AgendaItem::factory()->count(2)->create(), 2],
        'partners' => ['partners', fn () => Partner::factory()->create(), 1],
    ]);
});

describe('sessions per stage', function () {
    it('labels each slice with the stage name and groups unassigned sessions', function () {
        $stage = Stage::factory()->create(['name' => 'MAIN']);
        AgendaItem::factory()->count(2)->for($stage)->create();
        AgendaItem::factory()->create(['stage_id' => null]);

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/nova-api/dashboards/cards/main/metrics/sessions-per-stage');

        $response->assertOk();
        expect(collect($response->json('value.value'))->pluck('value', 'label')->all())
            ->toEqualCanonicalizing(['MAIN' => 2, 'No stage' => 1]);
    });

    it('colours each slice with its stage colour and unassigned sessions grey', function () {
        AgendaItem::factory()->for(Stage::factory()->create(['name' => 'GROWTH', 'color' => '#02FEB9']))->create();
        AgendaItem::factory()->create(['stage_id' => null]);

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/nova-api/dashboards/cards/main/metrics/sessions-per-stage');

        $response->assertOk();
        expect(collect($response->json('value.value'))->pluck('color', 'label')->all())
            ->toEqualCanonicalizing(['GROWTH' => '#02FEB9', 'No stage' => '#94A3B8']);
    });
});

describe('sessions per hour', function () {
    it('counts sessions per Warsaw hour and keeps quiet hours as zero', function () {
        // Stored in UTC; Warsaw is UTC+1 in November.
        AgendaItem::factory()->forEachSequence(
            ['starts_at' => '2026-11-26 09:00'],
            ['starts_at' => '2026-11-26 09:30'],
            ['starts_at' => '2026-11-26 11:15'],
            ['starts_at' => '2026-11-27 08:00'],
        )->create();

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/nova-api/dashboards/cards/main/metrics/sessions-per-hour');

        $response->assertOk()
            ->assertJsonPath('value.value', 4)
            ->assertJsonPath('value.trend', ['Thu 10:00' => 2, 'Thu 11:00' => 0, 'Thu 12:00' => 1, 'Fri 09:00' => 1]);
    });

    it('offers both days and each conference day as ranges', function () {
        AgendaItem::factory()->forEachSequence(['starts_at' => '2026-11-26 09:00'], ['starts_at' => '2026-11-27 08:00'])->create();

        $response = $this->actingAs(User::factory()->create())->getJson('/nova-api/dashboards/main');

        $response->assertOk();
        expect(collect($response->json('cards'))->firstWhere('uriKey', 'sessions-per-hour')['ranges'])->toBe([
            ['label' => 'Both days', 'value' => 'all'],
            ['label' => 'Thursday, 26 Nov', 'value' => '2026-11-26'],
            ['label' => 'Friday, 27 Nov', 'value' => '2026-11-27'],
        ]);
    });

    it('shows only the picked day', function () {
        AgendaItem::factory()->forEachSequence(['starts_at' => '2026-11-26 09:00'], ['starts_at' => '2026-11-27 08:00'])->create();

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/nova-api/dashboards/cards/main/metrics/sessions-per-hour?range=2026-11-27');

        $response->assertOk()
            ->assertJsonPath('value.value', 1)
            ->assertJsonPath('value.trend', ['Fri 09:00' => 1]);
    });
});

describe('session formats', function () {
    it('counts sessions by format', function () {
        AgendaItem::factory()->count(2)->hasAttached(Speaker::factory(), [], 'speakers')->create(['title' => 'Modern Java']);
        AgendaItem::factory()->create(['title' => 'Opening']);

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/nova-api/dashboards/cards/main/metrics/session-formats');

        $response->assertOk();
        expect(collect($response->json('value.value'))->pluck('value', 'label')->all())
            ->toEqualCanonicalizing(['Talk' => 2, 'Special' => 1]);
    });
});

describe('partners per tier', function () {
    it('labels each slice with the tier name', function () {
        Partner::factory()->count(2)->create(['tier' => PartnerTier::Gold]);
        Partner::factory()->create(['tier' => PartnerTier::Community]);

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/nova-api/dashboards/cards/main/metrics/partners-per-tier');

        $response->assertOk();
        expect(collect($response->json('value.value'))->pluck('value', 'label')->all())
            ->toEqualCanonicalizing(['Gold Partner' => 2, 'Community Partner' => 1]);
    });
});

describe('featured speakers', function () {
    it('shows featured speakers in homepage order with photo, role and first session', function () {
        $stage = Stage::factory()->create(['name' => 'MAIN', 'color' => '#9859FF']);
        $second = Speaker::factory()->create(['name' => 'José Valim', 'position' => 'Creator of Elixir', 'company' => null, 'photo' => null, 'is_featured' => true, 'sort_order' => 1]);
        $first = Speaker::factory()->create(['name' => 'Daniel Stenberg', 'position' => 'Creator of cURL', 'company' => 'wolfSSL', 'photo' => 'speakers/daniel.webp', 'is_featured' => true, 'sort_order' => 0]);
        Speaker::factory()->create(['is_featured' => false]);
        // Stored in UTC; the earlier one (Thu 16:00 in Warsaw) is the session shown.
        $first->agendaItems()->attach([
            AgendaItem::factory()->for($stage)->create(['starts_at' => '2026-11-27 09:00'])->id,
            AgendaItem::factory()->for($stage)->create(['starts_at' => '2026-11-26 15:00'])->id,
        ]);

        $response = $this->actingAs(User::factory()->create())->getJson('/nova-api/dashboards/main');

        $response->assertOk();
        $card = collect($response->json('cards'))->firstWhere('component', 'speaker-spotlight-card');
        expect($card['totalSpeakers'])->toBe(3)
            ->and($card['speakers'])->toBe([
                [
                    'id' => $first->id,
                    'name' => 'Daniel Stenberg',
                    'role' => 'Creator of cURL @ wolfSSL',
                    'photo' => Storage::disk('public')->url('speakers/daniel.webp'),
                    'path' => "/resources/speakers/{$first->id}",
                    'session' => ['stage' => 'MAIN', 'color' => '#9859FF', 'time' => 'Thu 16:00'],
                ],
                [
                    'id' => $second->id,
                    'name' => 'José Valim',
                    'role' => 'Creator of Elixir',
                    'photo' => null,
                    'path' => "/resources/speakers/{$second->id}",
                    'session' => null,
                ],
            ]);
    });
});
