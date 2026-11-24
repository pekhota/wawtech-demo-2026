<?php

use App\Models\AgendaItem;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Speaker;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Outside local, Nova admits read-only accounts without listing them in NOVA_ADMIN_EMAILS.
    $this->visitor = User::factory()->readOnly()->create();
});

it('lets a read-only account browse every content list', function (string $uriKey, string $model) {
    $record = $model::factory()->create();

    $this->actingAs($this->visitor)->getJson("/nova-api/{$uriKey}")->assertOk();
    $this->actingAs($this->visitor)->getJson("/nova-api/{$uriKey}/{$record->id}")->assertOk();
})->with([
    'speakers' => ['speakers', Speaker::class],
    'agenda items' => ['agenda-items', AgendaItem::class],
    'partners' => ['partners', Partner::class],
    'faqs' => ['faqs', Faq::class],
    'stages' => ['stages', Stage::class],
    'tags' => ['tags', Tag::class],
]);

describe('stops a read-only account from changing content', function () {
    it('when creating', function () {
        $this->actingAs($this->visitor)
            ->postJson('/nova-api/faqs', ['question' => 'Can I change this?', 'answer' => '<p>No.</p>'])
            ->assertForbidden();

        $this->assertDatabaseCount('faqs', 0);
    });

    it('when updating', function () {
        $faq = Faq::factory()->create(['question' => 'Original?']);

        $this->actingAs($this->visitor)
            ->putJson("/nova-api/faqs/{$faq->id}", ['question' => 'Changed?', 'answer' => '<p>Changed.</p>'])
            ->assertForbidden();

        expect($faq->fresh()->question)->toBe('Original?');
    });

    it('when deleting', function () {
        $partner = Partner::factory()->create();

        $this->actingAs($this->visitor)->deleteJson("/nova-api/partners?resources[]={$partner->id}");

        $this->assertNotSoftDeleted($partner);
    });

    it('when attaching a speaker to a session, which an admin can do', function () {
        $session = AgendaItem::factory()->create();
        $speaker = Speaker::factory()->create();
        $attach = fn (User $user) => $this->actingAs($user)->postJson("/nova-api/agenda-items/{$session->id}/attach/speakers", [
            'speakers' => $speaker->id,
            'viaRelationship' => 'speakers',
            'is_moderator' => false,
            'sort_order' => 0,
        ]);

        // Nova reports a relation the user may not attach as a validation error.
        $attach($this->visitor)->assertJsonValidationErrors('speakers');
        $this->assertDatabaseCount('agenda_item_speaker', 0);

        config(['nova.admin_emails' => ['admin@wawtech.test']]);
        $attach(User::factory()->create(['email' => 'admin@wawtech.test']))->assertOk();
        $this->assertDatabaseCount('agenda_item_speaker', 1);
    });

    it('when running an action', function () {
        $speaker = Speaker::factory()->create(['is_featured' => false]);

        $this->actingAs($this->visitor)
            ->postJson('/nova-api/speakers/action?action=feature-on-homepage', ['resources' => [$speaker->id]]);

        expect($speaker->fresh()->is_featured)->toBeFalse();
    });

    it('when reordering', function () {
        [$first, $second] = Faq::factory()->forEachSequence(['sort_order' => 0], ['sort_order' => 1])->create();

        $this->actingAs($this->visitor)
            ->postJson(route('nova.reorder', 'faqs'), ['ids' => [$second->id, $first->id]])
            ->assertForbidden();

        expect(Faq::orderBy('sort_order')->pluck('id')->all())->toBe([$first->id, $second->id]);
    });
});

it('offers a read-only account no actions', function (string $uriKey) {
    $actions = $this->actingAs($this->visitor)->getJson("/nova-api/{$uriKey}/actions")->assertOk()->json('actions');

    expect($actions)->toBe([]);
})->with(['speakers', 'agenda-items']);

it('hides the user list and the reorder cards from a read-only account', function () {
    Faq::factory()->create();

    $this->actingAs($this->visitor)->getJson('/nova-api/users')->assertForbidden();

    $cards = $this->actingAs($this->visitor)->getJson('/nova-api/faqs/cards')->assertOk()->json();
    expect(collect($cards)->pluck('component'))->not->toContain('sortable-card');
});

it('keeps the shared password of a read-only account unchanged', function () {
    $visitor = User::factory()->readOnly()->create(['password' => 'demo-password']);

    $this->actingAs($visitor)->putJson('/nova/user-security/password', [
        'current_password' => 'demo-password',
        'password' => 'taken-over-password',
        'password_confirmation' => 'taken-over-password',
    ])->assertUnprocessable();

    expect(Hash::check('demo-password', $visitor->fresh()->password))->toBeTrue();
});

it('still lets a regular account change its own password', function () {
    config(['nova.admin_emails' => ['admin@wawtech.test']]);
    $admin = User::factory()->create(['email' => 'admin@wawtech.test', 'password' => 'old-password']);

    $this->actingAs($admin)->putJson('/nova/user-security/password', [
        'current_password' => 'old-password',
        'password' => 'new-strong-password-2026',
        'password_confirmation' => 'new-strong-password-2026',
    ])->assertOk();

    expect(Hash::check('new-strong-password-2026', $admin->fresh()->password))->toBeTrue();
});

it('does not let a read-only account impersonate anyone', function () {
    expect($this->visitor->canImpersonate())->toBeFalse();
});
