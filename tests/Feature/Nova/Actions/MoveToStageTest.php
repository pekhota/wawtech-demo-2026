<?php

use App\Models\AgendaItem;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    Gate::define('viewNova', fn () => true);
});

it('moves the selected sessions to the chosen stage', function () {
    $studio = Stage::factory()->create(['name' => 'STUDIO']);
    [$first, $second, $untouched] = AgendaItem::factory()->count(3)->create();

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/nova-api/agenda-items/action?action=move-to-stage', [
            'resources' => [$first->id, $second->id],
            'stage' => $studio->id,
        ]);

    $response->assertOk()->assertJsonPath('message', 'Moved 2 session(s) to STUDIO.');
    expect($first->fresh()->stage_id)->toBe($studio->id)
        ->and($second->fresh()->stage_id)->toBe($studio->id)
        ->and($untouched->fresh()->stage_id)->not->toBe($studio->id);
});

it('rejects the action when no stage is chosen', function () {
    $item = AgendaItem::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/nova-api/agenda-items/action?action=move-to-stage', ['resources' => [$item->id]]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['stage' => 'The Stage field is required.']);
    expect($item->fresh()->stage_id)->toBe($item->stage_id);
});
