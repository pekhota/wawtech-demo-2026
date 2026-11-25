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
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Outside local, Nova admits read-only accounts without listing them in NOVA_ADMIN_EMAILS.
    $this->visitor = User::factory()->readOnly()->create();
});

/**
 * Nova only shows a message sent by the server for a 5xx response, so that is how the visitor is told.
 */
function assertRejectedAsReadOnly(TestResponse $response): void
{
    $response->assertInternalServerError()
        ->assertExactJson(['message' => 'This is a read-only demo account, so your changes were not saved.']);
}

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

describe('shows a read-only account what an editor sees', function () {
    it('with the create, edit and delete buttons', function () {
        Faq::factory()->create();

        $response = $this->actingAs($this->visitor)->getJson('/nova-api/faqs');

        $response->assertOk()
            ->assertJsonPath('resources.0.authorizedToCreate', true)
            ->assertJsonPath('resources.0.authorizedToUpdate', true)
            ->assertJsonPath('resources.0.authorizedToDelete', true);
    });

    it('with the create and edit forms', function () {
        $faq = Faq::factory()->create();

        $this->actingAs($this->visitor)->getJson('/nova-api/faqs/creation-fields')->assertOk();
        $this->actingAs($this->visitor)->getJson("/nova-api/faqs/{$faq->id}/update-fields")->assertOk();
    });

    it('with the actions', function (string $uriKey, string $action) {
        $response = $this->actingAs($this->visitor)->getJson("/nova-api/{$uriKey}/actions");

        $response->assertOk();
        expect($response->json('actions.*.uriKey'))->toContain($action);
    })->with([
        'speakers' => ['speakers', 'feature-on-homepage'],
        'agenda items' => ['agenda-items', 'move-to-stage'],
    ]);

    it('with the reorder card', function () {
        Faq::factory()->create();

        $response = $this->actingAs($this->visitor)->getJson('/nova-api/faqs/cards');

        $response->assertOk();
        expect($response->json('*.component'))->toContain('sortable-card');
    });
});

describe('saves nothing a read-only account submits and says why', function () {
    it('when creating', function () {
        $response = $this->actingAs($this->visitor)
            ->postJson('/nova-api/faqs', ['question' => 'Can I change this?', 'answer' => '<p>No.</p>']);

        assertRejectedAsReadOnly($response);
        $this->assertDatabaseCount('faqs', 0);
    });

    it('when updating', function () {
        $faq = Faq::factory()->create(['question' => 'Original?']);

        $response = $this->actingAs($this->visitor)
            ->putJson("/nova-api/faqs/{$faq->id}", ['question' => 'Changed?', 'answer' => '<p>Changed.</p>']);

        assertRejectedAsReadOnly($response);
        expect($faq->fresh()->question)->toBe('Original?');
    });

    it('when deleting', function () {
        $partner = Partner::factory()->create();

        $response = $this->actingAs($this->visitor)->deleteJson("/nova-api/partners?resources[]={$partner->id}");

        assertRejectedAsReadOnly($response);
        $this->assertNotSoftDeleted($partner);
    });

    it('when restoring or force deleting', function (string $method, string $endpoint) {
        $partner = Partner::factory()->trashed()->create();

        $response = $this->actingAs($this->visitor)->json($method, "/nova-api/partners/{$endpoint}?resources[]={$partner->id}");

        assertRejectedAsReadOnly($response);
        $this->assertSoftDeleted($partner);
    })->with([
        'restoring' => ['PUT', 'restore'],
        'force deleting' => ['DELETE', 'force'],
    ]);

    it('when attaching a speaker to a session, which an admin can do', function () {
        $session = AgendaItem::factory()->create();
        $speaker = Speaker::factory()->create();
        $attach = fn (User $user) => $this->actingAs($user)->postJson("/nova-api/agenda-items/{$session->id}/attach/speakers", [
            'speakers' => $speaker->id,
            'viaRelationship' => 'speakers',
            'is_moderator' => false,
            'sort_order' => 0,
        ]);

        assertRejectedAsReadOnly($attach($this->visitor));
        $this->assertDatabaseCount('agenda_item_speaker', 0);

        config(['nova.admin_emails' => ['admin@wawtech.test']]);
        $attach(User::factory()->create(['email' => 'admin@wawtech.test']))->assertOk();
        $this->assertDatabaseCount('agenda_item_speaker', 1);
    });

    it('when detaching a speaker from a session', function () {
        $session = AgendaItem::factory()->hasAttached(Speaker::factory())->create();
        $speaker = $session->speakers()->sole();

        $response = $this->actingAs($this->visitor)->deleteJson(
            "/nova-api/speakers/detach?viaResource=agenda-items&viaResourceId={$session->id}&viaRelationship=speakers&resources[]={$speaker->id}"
        );

        assertRejectedAsReadOnly($response);
        $this->assertDatabaseCount('agenda_item_speaker', 1);
    });

    it('when running an action', function () {
        $speaker = Speaker::factory()->create(['is_featured' => false]);

        $response = $this->actingAs($this->visitor)
            ->postJson('/nova-api/speakers/action?action=feature-on-homepage', ['resources' => [$speaker->id]]);

        assertRejectedAsReadOnly($response);
        expect($speaker->fresh()->is_featured)->toBeFalse();
    });

    it('when reordering', function () {
        [$first, $second] = Faq::factory()->forEachSequence(['sort_order' => 0], ['sort_order' => 1])->create();

        $response = $this->actingAs($this->visitor)
            ->postJson(route('nova.reorder', 'faqs'), ['ids' => [$second->id, $first->id]]);

        assertRejectedAsReadOnly($response);
        expect(Faq::orderBy('sort_order')->pluck('id')->all())->toBe([$first->id, $second->id]);
    });
});

describe('still answers the requests a form sends before it is saved', function () {
    it('when recalculating dependent fields', function () {
        $response = $this->actingAs($this->visitor)
            ->patchJson('/nova-api/agenda-items/creation-fields?editing=true&editMode=create', ['starts_at' => '2026-11-26 10:00:00']);

        $response->assertOk();
    });

    it('when previewing Markdown', function () {
        $response = $this->actingAs($this->visitor)
            ->postJson('/nova-api/speakers/field/bio/preview', ['value' => '**Keynote** speaker']);

        $response->assertOk()->assertJsonPath('preview', "<p><strong>Keynote</strong> speaker</p>\n");
    });
});

it('lets an admin who impersonates a read-only account switch back', function () {
    config(['nova.admin_emails' => ['admin@wawtech.test']]);
    $admin = User::factory()->create(['email' => 'admin@wawtech.test']);
    $this->actingAs($admin)
        ->postJson('/nova-api/impersonate', ['resource' => 'users', 'resourceId' => $this->visitor->id])
        ->assertOk();
    $this->assertAuthenticatedAs($this->visitor);

    $response = $this->deleteJson('/nova-api/impersonate');

    $response->assertOk();
    $this->assertAuthenticatedAs($admin);
});

it('hides the user list from a read-only account', function () {
    $this->actingAs($this->visitor)->getJson('/nova-api/users')->assertForbidden();
});

it('hides the details of every user from a read-only account', function (string $page) {
    $admin = User::factory()->create();

    $response = $this->actingAs($this->visitor)->getJson("/nova-api/users/{$admin->id}{$page}");

    $response->assertForbidden();
})->with([
    'the detail page' => [''],
    'the preview' => ['/preview'],
    'the peek popover' => ['/peek'],
]);

it('keeps the shared password of a read-only account unchanged', function () {
    $visitor = User::factory()->readOnly()->create(['password' => 'demo-password']);

    $response = $this->actingAs($visitor)->putJson('/nova/user-security/password', [
        'current_password' => 'demo-password',
        'password' => 'taken-over-password',
        'password_confirmation' => 'taken-over-password',
    ]);

    assertRejectedAsReadOnly($response);
    expect(Hash::check('demo-password', $visitor->fresh()->password))->toBeTrue();
});

it('lets a read-only account log out', function () {
    $response = $this->actingAs($this->visitor)->postJson('/nova/logout');

    $response->assertSuccessful();
    $this->assertGuest();
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
